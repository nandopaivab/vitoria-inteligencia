<?php
/**
 * VITÓRIA INTELIGÊNCIA — Front Controller Principal (index.php)
 * Gerencia todas as rotas autenticadas, CRUDs com PDO Prepared Statements, proteção CSRF e exportações.
 */

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Security;
use App\Services\BackupService;
use App\Services\ExportService;
use App\Services\OpenAIService;
use App\Services\RadarService;
use App\Services\SocialService;

// Redirecionar automaticamente para o instalador Plesk caso ainda não instalado
if (!file_exists(CONFIG_FILE)) {
    header('Location: install/index.php');
    exit;
}

$config = require CONFIG_FILE;
date_default_timezone_set($config['timezone'] ?? 'America/Sao_Paulo');

Security::init($config['app_key'] ?? '');
Security::startSecureSession($config['session_name'] ?? 'VITORIA_INTEL_SESS');
Database::init($config);
ensure_initial_seed_data();

$route = $_GET['route'] ?? 'dashboard';

// Proteger todas as rotas POST com validação CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
}

// =============================================================================
// ROTAS PÚBLICAS (LOGIN / LOGOUT)
// =============================================================================
if ($route === 'login') {
    if (Auth::check()) {
        header('Location: index.php?route=dashboard');
        exit;
    }
    require BASE_PATH . '/views/auth/login.php';
    exit;
}

if ($route === 'login_submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (Auth::attempt($email, $password)) {
        header('Location: index.php?route=dashboard');
        exit;
    }
    flash('error', 'E-mail ou senha inválidos. Todas as tentativas são registradas na auditoria.');
    header('Location: index.php?route=login');
    exit;
}

if ($route === 'logout') {
    Auth::logout();
    header('Location: index.php?route=login');
    exit;
}

// =============================================================================
// EXIGIR AUTENTICAÇÃO PARA TODAS AS DEMAIS ROTAS
// =============================================================================
Auth::requireLogin();

function render_view(string $viewFile, array $data = []): void
{
    extract($data);
    ob_start();
    require BASE_PATH . '/views/' . $viewFile;
    $contentHtml = ob_get_clean();
    require BASE_PATH . '/views/layouts/main.php';
    exit;
}

// =============================================================================
// NOTIFICAÇÕES
// =============================================================================
if ($route === 'mark_notifications_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Database::execute("UPDATE notifications SET is_read = 1");
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?route=dashboard'));
    exit;
}

// =============================================================================
// 1. DASHBOARD CENTRAL (MÓDULO 3)
// =============================================================================
if ($route === 'dashboard') {
    $kpis = [
        'consented_contacts'      => (int)Database::fetchColumn("SELECT COUNT(*) FROM contacts WHERE consent_lgpd = 1"),
        'volunteers_count'        => (int)Database::fetchColumn("SELECT COUNT(*) FROM contacts WHERE profile_type = 'Voluntário' AND consent_lgpd = 1"),
        'total_demands'           => (int)Database::fetchColumn("SELECT COUNT(*) FROM demands"),
        'open_demands'            => (int)Database::fetchColumn("SELECT COUNT(*) FROM demands WHERE status != 'Concluída' AND status != 'Arquivada'"),
        'resolved_demands'        => (int)Database::fetchColumn("SELECT COUNT(*) FROM demands WHERE status = 'Concluída'"),
        'monitored_neighborhoods' => (int)Database::fetchColumn("SELECT COUNT(*) FROM neighborhoods WHERE monitored = 1"),
        'completed_events'        => (int)Database::fetchColumn("SELECT COUNT(*) FROM agenda_events WHERE status = 'Realizado'"),
        'scheduled_meetings'      => (int)Database::fetchColumn("SELECT COUNT(*) FROM agenda_events WHERE status IN ('Agendado', 'Confirmado')"),
        'monitored_news'          => (int)Database::fetchColumn("SELECT COUNT(*) FROM radar_items"),
        'social_posts_count'      => (int)Database::fetchColumn("SELECT COUNT(*) FROM social_posts"),
        'social_approved_queue'   => (int)Database::fetchColumn("SELECT COUNT(*) FROM social_posts WHERE approval_status = 'Aprovado'"),
        'avg_engagement_rate'     => (float)Database::fetchColumn("SELECT AVG(engagement_rate) FROM social_accounts"),
        'total_reach'             => (int)Database::fetchColumn("SELECT COALESCE(SUM(reach_metric), 0) FROM social_posts"),
    ];

    // Evolução Mensal (Últimos 6 meses)
    $monthsList = ['2026-06', '2026-07', '2026-08', '2026-09', '2026-10', '2026-11'];
    $chartMonthly = ['labels' => ['Jun/26', 'Jul/26', 'Ago/26', 'Set/26', 'Out/26', 'Nov/26'], 'demands' => [], 'contacts' => []];
    foreach ($monthsList as $m) {
        $chartMonthly['demands'][]  = (int)Database::fetchColumn("SELECT COUNT(*) FROM demands WHERE SUBSTR(demand_date, 1, 7) = :m", [':m' => $m]);
        $chartMonthly['contacts'][] = (int)Database::fetchColumn("SELECT COUNT(*) FROM contacts WHERE SUBSTR(registration_date, 1, 7) = :m", [':m' => $m]);
    }

    // Comparação Territorial por Região Administrativa
    $regionRows = Database::fetchAll(
        "SELECT n.region_name, COUNT(d.id) as total
         FROM neighborhoods n
         LEFT JOIN demands d ON d.neighborhood_id = n.id
         GROUP BY n.region_id, n.region_name
         ORDER BY n.region_id ASC"
    );
    $chartRegions = [
        'labels' => array_column($regionRows, 'region_name'),
        'counts' => array_map('intval', array_column($regionRows, 'total')),
    ];

    $recentDemands = Database::fetchAll(
        "SELECT d.*, n.name as neighborhood_name
         FROM demands d
         LEFT JOIN neighborhoods n ON n.id = d.neighborhood_id
         ORDER BY d.demand_date DESC, d.id DESC LIMIT 6"
    );

    $recentRadar = Database::fetchAll("SELECT * FROM radar_items ORDER BY published_at DESC LIMIT 5");

    render_view('dashboard/index.php', [
        'pageTitle'     => 'Dashboard de Inteligência Municipal',
        'pageSubtitle'  => 'Visão Consolidada dos 80 Bairros Oficiais de Vitória/ES',
        'kpis'          => $kpis,
        'chartMonthly'  => $chartMonthly,
        'chartRegions'  => $chartRegions,
        'recentDemands' => $recentDemands,
        'recentRadar'   => $recentRadar,
    ]);
}

// =============================================================================
// 2. MAPA INTERATIVO DE VITÓRIA (MÓDULO 4)
// =============================================================================
if ($route === 'map') {
    $neighborhoodsStats = Database::fetchAll(
        "SELECT n.*,
                (SELECT COUNT(*) FROM demands d WHERE d.neighborhood_id = n.id) as demands_count,
                (SELECT COUNT(*) FROM demands d WHERE d.neighborhood_id = n.id AND d.status != 'Concluída') as open_demands_count,
                (SELECT COUNT(*) FROM demands d WHERE d.neighborhood_id = n.id AND d.status = 'Concluída') as resolved_demands_count,
                (SELECT COUNT(*) FROM contacts c WHERE c.neighborhood_id = n.id AND c.consent_lgpd = 1) as contacts_count,
                (SELECT COUNT(*) FROM public_equipments pe WHERE pe.neighborhood_id = n.id) as equipments_count
         FROM neighborhoods n
         ORDER BY n.name ASC"
    );

    $equipments = Database::fetchAll("SELECT * FROM public_equipments ORDER BY type ASC, name ASC");
    $demands    = Database::fetchAll(
        "SELECT d.*, n.name as neighborhood_name
         FROM demands d
         LEFT JOIN neighborhoods n ON n.id = d.neighborhood_id
         ORDER BY d.demand_date DESC"
    );
    $totalConsentedContacts = (int)Database::fetchColumn("SELECT COUNT(*) FROM contacts WHERE consent_lgpd = 1");

    render_view('map/index.php', [
        'pageTitle'              => 'Mapa Interativo GIS de Vitória/ES',
        'pageSubtitle'           => '80 Bairros Oficiais (Lei 8.611/2013), Equipamentos Públicos e Mapa de Calor de Demandas',
        'neighborhoodsStats'     => $neighborhoodsStats,
        'equipments'             => $equipments,
        'demands'                => $demands,
        'totalConsentedContacts' => $totalConsentedContacts,
    ]);
}

// =============================================================================
// 3. GESTÃO COMUNITÁRIA & LGPD (MÓDULO 5)
// =============================================================================
if ($route === 'contacts') {
    $where  = ['1=1'];
    $params = [];

    if (!empty($_GET['q'])) {
        $where[]      = "(c.name LIKE :q OR c.email LIKE :q OR c.phone LIKE :q)";
        $params[':q'] = '%' . trim($_GET['q']) . '%';
    }
    if (!empty($_GET['neighborhood_id'])) {
        $where[]        = "c.neighborhood_id = :nid";
        $params[':nid'] = (int)$_GET['neighborhood_id'];
    }
    if (!empty($_GET['profile_type'])) {
        $where[]       = "c.profile_type = :pt";
        $params[':pt'] = $_GET['profile_type'];
    }

    $sql = "SELECT c.*, n.name as neighborhood_name
            FROM contacts c
            LEFT JOIN neighborhoods n ON n.id = c.neighborhood_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY c.registration_date DESC, c.id DESC";

    $contacts      = Database::fetchAll($sql, $params);
    $neighborhoods = Database::fetchAll("SELECT id, name, region_name FROM neighborhoods ORDER BY name ASC");

    $allInteractions = Database::fetchAll("SELECT * FROM contact_interactions ORDER BY interaction_date DESC");
    $interactionsByContact = [];
    foreach ($allInteractions as $inter) {
        $interactionsByContact[$inter['contact_id']][] = $inter;
    }

    render_view('contacts/index.php', [
        'pageTitle'             => 'Gestão Comunitária & Voluntariado (LGPD)',
        'pageSubtitle'          => 'Cadastro de Voluntários, Colaboradores e Participantes com Consentimento Verificado',
        'contacts'              => $contacts,
        'neighborhoods'         => $neighborhoods,
        'interactionsByContact' => $interactionsByContact,
    ]);
}

if ($route === 'contact_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id              = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $name            = Security::cleanInput($_POST['name'] ?? '');
    $phone           = Security::cleanInput($_POST['phone'] ?? '');
    $email           = Security::cleanInput($_POST['email'] ?? '');
    $profileType     = Security::cleanInput($_POST['profile_type'] ?? 'Voluntário');
    $neighborhoodId  = (int)($_POST['neighborhood_id'] ?? 1);
    $regDate         = Security::cleanInput($_POST['registration_date'] ?? date('Y-m-d'));
    $commPref        = Security::cleanInput($_POST['communication_pref'] ?? 'WhatsApp');
    $responsibleName = Security::cleanInput($_POST['responsible_name'] ?? 'Coordenação');
    $consentLgpd     = isset($_POST['consent_lgpd']) && (int)$_POST['consent_lgpd'] === 1 ? 1 : 0;
    $summary         = Security::cleanInput($_POST['relationship_summary'] ?? '');

    if ($name === '' || $phone === '') {
        flash('error', 'Nome e telefone são obrigatórios.');
        header('Location: index.php?route=contacts');
        exit;
    }

    if ($id) {
        Database::execute(
            "UPDATE contacts
             SET name = :n, phone = :p, email = :e, profile_type = :pt, neighborhood_id = :nid,
                 registration_date = :rd, consent_lgpd = :lgpd, communication_pref = :cp,
                 responsible_name = :rn, relationship_summary = :sum, updated_at = :dt
             WHERE id = :id",
            [
                ':n'    => $name,
                ':p'    => $phone,
                ':e'    => $email,
                ':pt'   => $profileType,
                ':nid'  => $neighborhoodId,
                ':rd'   => $regDate,
                ':lgpd' => $consentLgpd,
                ':cp'   => $commPref,
                ':rn'   => $responsibleName,
                ':sum'  => $summary,
                ':dt'   => date('Y-m-d H:i:s'),
                ':id'   => $id,
            ]
        );
        Logger::audit("Contato comunitário #{$id} atualizado", 'Gestão Comunitária', "Nome: {$name}");
        flash('success', 'Cadastro comunitário atualizado com sucesso.');
    } else {
        $newId = Database::insert(
            "INSERT INTO contacts (name, phone, email, profile_type, neighborhood_id, registration_date, consent_lgpd, consent_date, communication_pref, responsible_user_id, responsible_name, relationship_summary, created_at, updated_at)
             VALUES (:n, :p, :e, :pt, :nid, :rd, :lgpd, :dt, :cp, :uid, :rn, :sum, :dt, :dt)",
            [
                ':n'    => $name,
                ':p'    => $phone,
                ':e'    => $email,
                ':pt'   => $profileType,
                ':nid'  => $neighborhoodId,
                ':rd'   => $regDate,
                ':lgpd' => $consentLgpd,
                ':dt'   => date('Y-m-d H:i:s'),
                ':cp'   => $commPref,
                ':uid'  => Auth::id(),
                ':rn'   => $responsibleName,
                ':sum'  => $summary,
            ]
        );
        Logger::audit("Novo contato comunitário #{$newId} cadastrado com consentimento LGPD", 'Gestão Comunitária', "Nome: {$name}");
        flash('success', 'Contato comunitário cadastrado com sucesso sob proteção LGPD.');
    }

    header('Location: index.php?route=contacts');
    exit;
}

if ($route === 'contact_interaction_add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $contactId = (int)($_POST['contact_id'] ?? 0);
    $type      = Security::cleanInput($_POST['interaction_type'] ?? 'Atendimento Presencial');
    $desc      = Security::cleanInput($_POST['description'] ?? '');

    if ($contactId > 0 && $desc !== '') {
        Database::insert(
            "INSERT INTO contact_interactions (contact_id, user_id, user_name, interaction_type, description, interaction_date)
             VALUES (:cid, :uid, :uname, :type, :desc, :dt)",
            [
                ':cid'   => $contactId,
                ':uid'   => Auth::id(),
                ':uname' => Auth::user()['name'] ?? 'Sistema',
                ':type'  => $type,
                ':desc'  => $desc,
                ':dt'    => date('Y-m-d H:i:s'),
            ]
        );
        Logger::audit("Interação adicionada ao contato #{$contactId}", 'Gestão Comunitária', $type);
        flash('success', 'Interação adicionada ao histórico de relacionamento.');
    }
    header('Location: index.php?route=contacts');
    exit;
}

if ($route === 'contact_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        Database::execute("DELETE FROM contacts WHERE id = :id", [':id' => $id]);
        Logger::audit("Contato #{$id} excluído definitivamente (Direito de Eliminação LGPD)", 'Gestão Comunitária');
        flash('success', 'Registro comunitário excluído conforme diretriz LGPD.');
    }
    header('Location: index.php?route=contacts');
    exit;
}

// =============================================================================
// 4. GESTÃO DE DEMANDAS MUNICIPAIS (MÓDULO 6)
// =============================================================================
if ($route === 'demands') {
    $where  = ['1=1'];
    $params = [];

    if (!empty($_GET['q'])) {
        $where[]      = "(d.protocol_code LIKE :q OR d.title LIKE :q OR d.address_location LIKE :q)";
        $params[':q'] = '%' . trim($_GET['q']) . '%';
    }
    if (!empty($_GET['category'])) {
        $where[]        = "d.category = :cat";
        $params[':cat'] = $_GET['category'];
    }
    if (!empty($_GET['neighborhood_id'])) {
        $where[]        = "d.neighborhood_id = :nid";
        $params[':nid'] = (int)$_GET['neighborhood_id'];
    }
    if (!empty($_GET['status'])) {
        $where[]       = "d.status = :st";
        $params[':st'] = $_GET['status'];
    }

    $demands = Database::fetchAll(
        "SELECT d.*, n.name as neighborhood_name
         FROM demands d
         LEFT JOIN neighborhoods n ON n.id = d.neighborhood_id
         WHERE " . implode(' AND ', $where) . "
         ORDER BY d.demand_date DESC, d.id DESC",
        $params
    );

    $summaryByCategory = Database::fetchAll(
        "SELECT category, COUNT(*) as total FROM demands GROUP BY category ORDER BY total DESC"
    );
    $neighborhoods = Database::fetchAll("SELECT id, name, lat, lng FROM neighborhoods ORDER BY name ASC");

    $allHistory = Database::fetchAll("SELECT * FROM demand_history ORDER BY created_at DESC");
    $historyByDemand = [];
    foreach ($allHistory as $h) {
        $historyByDemand[$h['demand_id']][] = $h;
    }

    render_view('demands/index.php', [
        'pageTitle'         => 'Gestão de Demandas Comunitárias Municipais',
        'pageSubtitle'      => 'Acompanhamento Georreferenciado nas 11 Categorias e 80 Bairros de Vitória/ES',
        'demands'           => $demands,
        'summaryByCategory' => $summaryByCategory,
        'neighborhoods'     => $neighborhoods,
        'historyByDemand'   => $historyByDemand,
    ]);
}

if ($route === 'demand_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id              = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $protocol        = Security::cleanInput($_POST['protocol_code'] ?? ('VIT-' . date('YmdHis')));
    $title           = Security::cleanInput($_POST['title'] ?? '');
    $category        = Security::cleanInput($_POST['category'] ?? 'Infraestrutura');
    $neighborhoodId  = (int)($_POST['neighborhood_id'] ?? 1);
    $demandDate      = Security::cleanInput($_POST['demand_date'] ?? date('Y-m-d'));
    $address         = Security::cleanInput($_POST['address_location'] ?? '');
    $lat             = (float)($_POST['lat'] ?? -20.3197);
    $lng             = (float)($_POST['lng'] ?? -40.3385);
    $priority        = Security::cleanInput($_POST['priority'] ?? 'Média');
    $status          = Security::cleanInput($_POST['status'] ?? 'Aberta');
    $responsibleName = Security::cleanInput($_POST['responsible_name'] ?? 'Coordenação');
    $extRef          = Security::cleanInput($_POST['external_reference'] ?? '');
    $description     = Security::cleanInput($_POST['description'] ?? '');

    if ($id) {
        $old = Database::fetchOne("SELECT status FROM demands WHERE id = :id", [':id' => $id]);
        Database::execute(
            "UPDATE demands
             SET protocol_code = :pc, title = :t, description = :desc, neighborhood_id = :nid,
                 address_location = :addr, lat = :lat, lng = :lng, category = :cat, demand_date = :dd,
                 priority = :pr, status = :st, responsible_name = :rn, external_reference = :ext, updated_at = :dt
             WHERE id = :id",
            [
                ':pc'   => $protocol,
                ':t'    => $title,
                ':desc' => $description,
                ':nid'  => $neighborhoodId,
                ':addr' => $address,
                ':lat'  => $lat,
                ':lng'  => $lng,
                ':cat'  => $category,
                ':dd'   => $demandDate,
                ':pr'   => $priority,
                ':st'   => $status,
                ':rn'   => $responsibleName,
                ':ext'  => $extRef,
                ':dt'   => date('Y-m-d H:i:s'),
                ':id'   => $id,
            ]
        );
        if ($old && $old['status'] !== $status) {
            Database::insert(
                "INSERT INTO demand_history (demand_id, previous_status, new_status, notes, user_id, user_name, created_at)
                 VALUES (:did, :prev, :new, :notes, :uid, :uname, :dt)",
                [
                    ':did'   => $id,
                    ':prev'  => $old['status'],
                    ':new'   => $status,
                    ':notes' => 'Status atualizado na edição do protocolo.',
                    ':uid'   => Auth::id(),
                    ':uname' => Auth::user()['name'] ?? 'Sistema',
                    ':dt'    => date('Y-m-d H:i:s'),
                ]
            );
        }
        Logger::audit("Demanda {$protocol} atualizada", 'Gestão de Demandas');
        flash('success', "Demanda {$protocol} atualizada com sucesso.");
    } else {
        $newId = Database::insert(
            "INSERT INTO demands (protocol_code, title, description, neighborhood_id, address_location, lat, lng, category, demand_date, priority, status, responsible_user_id, responsible_name, external_reference, created_at, updated_at)
             VALUES (:pc, :t, :desc, :nid, :addr, :lat, :lng, :cat, :dd, :pr, :st, :uid, :rn, :ext, :dt, :dt)",
            [
                ':pc'   => $protocol,
                ':t'    => $title,
                ':desc' => $description,
                ':nid'  => $neighborhoodId,
                ':addr' => $address,
                ':lat'  => $lat,
                ':lng'  => $lng,
                ':cat'  => $category,
                ':dd'   => $demandDate,
                ':pr'   => $priority,
                ':st'   => $status,
                ':uid'  => Auth::id(),
                ':rn'   => $responsibleName,
                ':ext'  => $extRef,
                ':dt'   => date('Y-m-d H:i:s'),
            ]
        );
        Database::insert(
            "INSERT INTO demand_history (demand_id, previous_status, new_status, notes, user_id, user_name, created_at)
             VALUES (:did, NULL, :new, 'Abertura inicial da demanda georreferenciada.', :uid, :uname, :dt)",
            [
                ':did'   => $newId,
                ':new'   => $status,
                ':uid'   => Auth::id(),
                ':uname' => Auth::user()['name'] ?? 'Sistema',
                ':dt'    => date('Y-m-d H:i:s'),
            ]
        );
        Logger::audit("Nova demanda {$protocol} registrada", 'Gestão de Demandas', "Bairro ID: {$neighborhoodId}");
        flash('success', "Demanda {$protocol} cadastrada e georreferenciada no mapa de Vitória/ES.");
    }
    header('Location: index.php?route=demands');
    exit;
}

if ($route === 'demand_history_add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $demandId  = (int)($_POST['demand_id'] ?? 0);
    $newStatus = Security::cleanInput($_POST['new_status'] ?? 'Em Análise');
    $notes     = Security::cleanInput($_POST['notes'] ?? '');

    if ($demandId > 0 && $notes !== '') {
        $old = Database::fetchOne("SELECT status, protocol_code FROM demands WHERE id = :id", [':id' => $demandId]);
        Database::insert(
            "INSERT INTO demand_history (demand_id, previous_status, new_status, notes, user_id, user_name, created_at)
             VALUES (:did, :prev, :new, :notes, :uid, :uname, :dt)",
            [
                ':did'   => $demandId,
                ':prev'  => $old['status'] ?? 'Aberta',
                ':new'   => $newStatus,
                ':notes' => $notes,
                ':uid'   => Auth::id(),
                ':uname' => Auth::user()['name'] ?? 'Sistema',
                ':dt'    => date('Y-m-d H:i:s'),
            ]
        );
        Database::execute(
            "UPDATE demands SET status = :st, updated_at = :dt WHERE id = :id",
            [':st' => $newStatus, ':dt' => date('Y-m-d H:i:s'), ':id' => $demandId]
        );
        Logger::audit("Tramitação adicionada à demanda #{$demandId}", 'Gestão de Demandas', "Novo status: {$newStatus}");
        flash('success', 'Despacho registrado e status da demanda atualizado.');
    }
    header('Location: index.php?route=demands');
    exit;
}

if ($route === 'demand_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        Database::execute("DELETE FROM demands WHERE id = :id", [':id' => $id]);
        Logger::audit("Demanda #{$id} excluída", 'Gestão de Demandas');
        flash('success', 'Demanda removida com sucesso.');
    }
    header('Location: index.php?route=demands');
    exit;
}

// =============================================================================
// 5. RADAR DE INTELIGÊNCIA MUNICIPAL (MÓDULO 7)
// =============================================================================
if ($route === 'radar') {
    $where  = ['1=1'];
    $params = [];

    if (!empty($_GET['q'])) {
        $where[]      = "(r.title LIKE :q OR r.content_excerpt LIKE :q OR r.source_name LIKE :q)";
        $params[':q'] = '%' . trim($_GET['q']) . '%';
    }
    if (!empty($_GET['category'])) {
        $where[]        = "r.category = :cat";
        $params[':cat'] = $_GET['category'];
    }
    if (!empty($_GET['source_type'])) {
        $where[]       = "r.source_type = :st";
        $params[':st'] = $_GET['source_type'];
    }

    $radarItems = Database::fetchAll(
        "SELECT r.*, n.name as neighborhood_name
         FROM radar_items r
         LEFT JOIN neighborhoods n ON n.id = r.neighborhood_id
         WHERE " . implode(' AND ', $where) . "
         ORDER BY r.published_at DESC, r.id DESC",
        $params
    );

    $categoryStats = Database::fetchAll(
        "SELECT category, COUNT(*) as total FROM radar_items GROUP BY category ORDER BY total DESC"
    );

    $recurringThemes = Database::fetchAll(
        "SELECT recurring_theme, category, COUNT(*) as total
         FROM radar_items
         GROUP BY recurring_theme, category
         ORDER BY total DESC LIMIT 6"
    );

    $neighborhoods = Database::fetchAll("SELECT id, name FROM neighborhoods ORDER BY name ASC");

    render_view('radar/index.php', [
        'pageTitle'       => 'Radar de Inteligência Municipal',
        'pageSubtitle'    => 'Monitoramento de Notícias, Portais Oficiais, Dados IBGE e Tendências de Assuntos Públicos',
        'radarItems'      => $radarItems,
        'categoryStats'   => $categoryStats,
        'recurringThemes' => $recurringThemes,
        'neighborhoods'   => $neighborhoods,
    ]);
}

if ($route === 'radar_sync' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = RadarService::syncPublicFeeds();
    flash('success', "Sincronização do Radar concluída: {$res['inserted']} novas publicações importadas dos feeds públicos.");
    header('Location: index.php?route=radar');
    exit;
}

if ($route === 'radar_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title          = Security::cleanInput($_POST['title'] ?? '');
    $sourceType     = Security::cleanInput($_POST['source_type'] ?? 'Notícia Jornalística');
    $sourceName     = Security::cleanInput($_POST['source_name'] ?? '');
    $category       = Security::cleanInput($_POST['category'] ?? 'Administração pública');
    $neighborhoodId = !empty($_POST['neighborhood_id']) ? (int)$_POST['neighborhood_id'] : null;
    $sourceUrl      = trim($_POST['source_url'] ?? '');
    $theme          = Security::cleanInput($_POST['recurring_theme'] ?? '');
    $excerpt        = Security::cleanInput($_POST['content_excerpt'] ?? '');

    if ($title !== '' && $sourceUrl !== '') {
        $aiSum = "Resumo Automático ({$sourceName} - {$category}): " . mb_substr($excerpt, 0, 220);
        Database::insert(
            "INSERT INTO radar_items (title, source_name, source_type, source_url, category, neighborhood_id, published_at, content_excerpt, ai_summary, recurring_theme, relevance_score, is_sample_data, created_at)
             VALUES (:t, :sn, :st, :su, :cat, :nid, :dt, :exc, :ai, :thm, 88, 0, :dt)",
            [
                ':t'   => $title,
                ':sn'  => $sourceName,
                ':st'  => $sourceType,
                ':su'  => $sourceUrl,
                ':cat' => $category,
                ':nid' => $neighborhoodId,
                ':dt'  => date('Y-m-d H:i:s'),
                ':exc' => $excerpt,
                ':ai'  => $aiSum,
                ':thm' => $theme !== '' ? $theme : $category,
            ]
        );
        Logger::audit("Item adicionado manualmente ao Radar Municipal: {$title}", 'Radar Municipal');
        flash('success', 'Registro adicionado ao Radar de Inteligência Municipal com resumo gerado.');
    }
    header('Location: index.php?route=radar');
    exit;
}

if ($route === 'radar_ai_summarize' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = (int)($_POST['id'] ?? 0);
    $item = Database::fetchOne("SELECT * FROM radar_items WHERE id = :id", [':id' => $id]);
    if ($item) {
        $summary = "Síntese Analítica (" . date('d/m/Y H:i') . " | Fonte: {$item['source_name']}): "
            . "Pauta classificada em '{$item['category']}' sob o tema '{$item['recurring_theme']}'. "
            . mb_substr((string)$item['content_excerpt'], 0, 240);
        Database::execute("UPDATE radar_items SET ai_summary = :s WHERE id = :id", [':s' => $summary, ':id' => $id]);
        flash('success', 'Resumo analítico da notícia atualizado com sucesso.');
    }
    header('Location: index.php?route=radar');
    exit;
}

// =============================================================================
// 6. CENTRAL DE REDES SOCIAIS (MÓDULO 8)
// =============================================================================
if ($route === 'social') {
    $accounts    = Database::fetchAll("SELECT * FROM social_accounts ORDER BY id ASC");
    $posts       = Database::fetchAll("SELECT * FROM social_posts ORDER BY scheduled_at DESC, id DESC");
    $mediaAssets = Database::fetchAll("SELECT * FROM social_media_assets ORDER BY created_at DESC");

    render_view('social/index.php', [
        'pageTitle'   => 'Central de Redes Sociais & Calendário Editorial',
        'pageSubtitle'=> 'Instagram, Facebook e X — APIs Oficiais, Fila de Publicação e Fluxo de Aprovação',
        'accounts'    => $accounts,
        'posts'       => $posts,
        'mediaAssets' => $mediaAssets,
    ]);
}

if ($route === 'social_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title     = Security::cleanInput($_POST['title'] ?? '');
    $platsArr  = $_POST['platforms'] ?? ['Instagram'];
    $platforms = implode(',', array_map([Security::class, 'cleanInput'], (array)$platsArr));
    $caption   = Security::cleanInput($_POST['caption'] ?? '');
    $hashtags  = Security::cleanInput($_POST['hashtags'] ?? '');
    $schedRaw  = $_POST['scheduled_at'] ?? date('Y-m-d H:i:s');
    $sched     = date('Y-m-d H:i:s', strtotime($schedRaw));
    $rec       = Security::cleanInput($_POST['recurrence'] ?? 'Nenhuma');
    $status    = Security::cleanInput($_POST['approval_status'] ?? 'Em Revisão');
    $mediaUrl  = Security::cleanInput($_POST['media_url'] ?? '');

    if ($title !== '' && $caption !== '') {
        Database::insert(
            "INSERT INTO social_posts (title, platforms, caption, hashtags, media_url, scheduled_at, recurrence, approval_status, created_by_name, created_at, updated_at)
             VALUES (:t, :p, :c, :h, :m, :s, :r, :st, :u, :dt, :dt)",
            [
                ':t'  => $title,
                ':p'  => $platforms,
                ':c'  => $caption,
                ':h'  => $hashtags,
                ':m'  => $mediaUrl,
                ':s'  => $sched,
                ':r'  => $rec,
                ':st' => $status,
                ':u'  => Auth::user()['name'] ?? 'Equipe de Comunicação',
                ':dt' => date('Y-m-d H:i:s'),
            ]
        );
        Logger::audit("Nova publicação agendada no calendário editorial: {$title}", 'Redes Sociais');
        flash('success', 'Publicação salva no calendário editorial.');
    }
    header('Location: index.php?route=social');
    exit;
}

if ($route === 'social_approve' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = (int)($_POST['id'] ?? 0);
    $decision = ($_POST['decision'] ?? '') === 'Aprovado' ? 'Aprovado' : 'Rejeitado';
    if ($id > 0) {
        Database::execute(
            "UPDATE social_posts
             SET approval_status = :st, approved_by_user_id = :uid, approved_by_name = :uname, approved_at = :dt, updated_at = :dt
             WHERE id = :id",
            [
                ':st'    => $decision,
                ':uid'   => Auth::id(),
                ':uname' => Auth::user()['name'] ?? 'Administrador',
                ':dt'    => date('Y-m-d H:i:s'),
                ':id'    => $id,
            ]
        );
        Logger::audit("Publicação social #{$id} marcada como {$decision}", 'Redes Sociais');
        flash('success', "Status de aprovação da publicação #{$id} atualizado para: {$decision}.");
    }
    header('Location: index.php?route=social');
    exit;
}

if ($route === 'social_run_queue' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = SocialService::processApprovedQueue();
    $msg = "Fila processada: {$res['published']} enviada(s) via API Oficial; {$res['blocked']} retida(s) aguardando token oficial.";
    flash('success', $msg);
    header('Location: index.php?route=social');
    exit;
}

if ($route === 'social_account_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['id'] ?? 0);
    $name   = Security::cleanInput($_POST['account_name'] ?? '');
    $handle = Security::cleanInput($_POST['account_handle'] ?? '');
    $extId  = Security::cleanInput($_POST['external_account_id'] ?? '');
    $token  = trim($_POST['access_token'] ?? '');

    if ($id > 0) {
        if ($token !== '') {
            $encToken = Security::encrypt($token);
            Database::execute(
                "UPDATE social_accounts
                 SET account_name = :n, account_handle = :h, external_account_id = :ext,
                     access_token_encrypted = :tok, status = 'Conectada (API Oficial)', updated_at = :dt
                 WHERE id = :id",
                [':n' => $name, ':h' => $handle, ':ext' => $extId, ':tok' => $encToken, ':dt' => date('Y-m-d H:i:s'), ':id' => $id]
            );
            Logger::audit("Token oficial criptografado salvo para conta social #{$id}", 'Redes Sociais');
            flash('success', 'Token da API Oficial criptografado (AES-256) e conta ativada.');
        } else {
            Database::execute(
                "UPDATE social_accounts SET account_name = :n, account_handle = :h, external_account_id = :ext, updated_at = :dt WHERE id = :id",
                [':n' => $name, ':h' => $handle, ':ext' => $extId, ':dt' => date('Y-m-d H:i:s'), ':id' => $id]
            );
            flash('success', 'Dados da conta atualizados.');
        }
    }
    header('Location: index.php?route=social');
    exit;
}

if ($route === 'social_media_upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = Security::cleanInput($_POST['title'] ?? 'Imagem Institucional');
    if (!empty($_FILES['media_file']['tmp_name']) && is_uploaded_file($_FILES['media_file']['tmp_name'])) {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mime = mime_content_type($_FILES['media_file']['tmp_name']) ?: 'image/jpeg';
        if (in_array($mime, $allowedMimes, true)) {
            $ext = pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION) ?: 'jpg';
            $safeName = 'media_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . preg_replace('/[^a-zA-Z0-9]/', '', $ext);
            $destDir  = BASE_PATH . '/storage/uploads';
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }
            $destPath = $destDir . '/' . $safeName;
            if (move_uploaded_file($_FILES['media_file']['tmp_name'], $destPath)) {
                $sizeKb = (int)round(filesize($destPath) / 1024);
                Database::insert(
                    "INSERT INTO social_media_assets (title, file_path, mime_type, file_size_kb, uploaded_by, created_at)
                     VALUES (:t, :fp, :m, :sz, :u, :dt)",
                    [
                        ':t'  => $title,
                        ':fp' => 'storage/uploads/' . $safeName,
                        ':m'  => $mime,
                        ':sz' => $sizeKb,
                        ':u'  => Auth::user()['name'] ?? 'Administrador',
                        ':dt' => date('Y-m-d H:i:s'),
                    ]
                );
                flash('success', 'Imagem enviada ao Banco de Mídia com sucesso.');
            }
        } else {
            flash('error', 'Formato de arquivo não permitido. Envie apenas imagens JPG, PNG ou WEBP.');
        }
    }
    header('Location: index.php?route=social');
    exit;
}

if ($route === 'social_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        Database::execute("DELETE FROM social_posts WHERE id = :id", [':id' => $id]);
        flash('success', 'Publicação removida.');
    }
    header('Location: index.php?route=social');
    exit;
}

// =============================================================================
// 7. INTELIGÊNCIA ARTIFICIAL (MÓDULO 9)
// =============================================================================
if ($route === 'ai') {
    $analyses = Database::fetchAll("SELECT * FROM ai_analyses ORDER BY created_at DESC LIMIT 20");
    render_view('ai/index.php', [
        'pageTitle'        => 'Inteligência Artificial & Análises Estratégicas',
        'pageSubtitle'     => 'Integração Oficial OpenAI com Fontes Verificadas, Datas e Limitações Metodológicas',
        'openAiConfigured' => OpenAIService::isConfigured(),
        'analyses'         => $analyses,
    ]);
}

if ($route === 'ai_execute' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $type   = Security::cleanInput($_POST['analysis_type'] ?? 'Análise de Demandas Municipais');
    $prompt = Security::cleanInput($_POST['custom_prompt'] ?? '');
    OpenAIService::executeAnalysis($type, $prompt);
    flash('success', "Análise '{$type}' gerada com fontes e limitações documentadas.");
    header('Location: index.php?route=ai');
    exit;
}

// =============================================================================
// 8. AGENDA MUNICIPAL & EVENTOS (MÓDULO 10)
// =============================================================================
if ($route === 'agenda') {
    $where  = ['1=1'];
    $params = [];

    if (!empty($_GET['event_type'])) {
        $where[]       = "a.event_type = :et";
        $params[':et'] = $_GET['event_type'];
    }
    if (!empty($_GET['status'])) {
        $where[]       = "a.status = :st";
        $params[':st'] = $_GET['status'];
    }

    $events = Database::fetchAll(
        "SELECT a.*, n.name as neighborhood_name
         FROM agenda_events a
         LEFT JOIN neighborhoods n ON n.id = a.neighborhood_id
         WHERE " . implode(' AND ', $where) . "
         ORDER BY a.start_datetime DESC",
        $params
    );
    $neighborhoods = Database::fetchAll("SELECT id, name FROM neighborhoods ORDER BY name ASC");

    render_view('agenda/index.php', [
        'pageTitle'     => 'Agenda Institucional e Atividades Comunitárias',
        'pageSubtitle'  => 'Eventos, Reuniões, Audiências Públicas, Participantes e Responsáveis',
        'events'        => $events,
        'neighborhoods' => $neighborhoods,
    ]);
}

if ($route === 'agenda_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id           = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $title        = Security::cleanInput($_POST['title'] ?? '');
    $type         = Security::cleanInput($_POST['event_type'] ?? 'Reunião');
    $start        = date('Y-m-d H:i:s', strtotime($_POST['start_datetime'] ?? 'now'));
    $end          = date('Y-m-d H:i:s', strtotime($_POST['end_datetime'] ?? '+2 hours'));
    $nid          = !empty($_POST['neighborhood_id']) ? (int)$_POST['neighborhood_id'] : null;
    $address      = Security::cleanInput($_POST['address'] ?? '');
    $responsible  = Security::cleanInput($_POST['responsible_name'] ?? '');
    $expected     = (int)($_POST['expected_attendance'] ?? 0);
    $actual       = (int)($_POST['actual_attendance'] ?? 0);
    $status       = Security::cleanInput($_POST['status'] ?? 'Agendado');
    $participants = Security::cleanInput($_POST['participants_list'] ?? '');
    $description  = Security::cleanInput($_POST['description'] ?? '');

    if ($id) {
        Database::execute(
            "UPDATE agenda_events
             SET title = :t, event_type = :et, description = :desc, start_datetime = :s, end_datetime = :e,
                 neighborhood_id = :nid, address = :addr, responsible_name = :resp, participants_list = :part,
                 expected_attendance = :exp, actual_attendance = :act, status = :st, updated_at = :dt
             WHERE id = :id",
            [
                ':t'    => $title,
                ':et'   => $type,
                ':desc' => $description,
                ':s'    => $start,
                ':e'    => $end,
                ':nid'  => $nid,
                ':addr' => $address,
                ':resp' => $responsible,
                ':part' => $participants,
                ':exp'  => $expected,
                ':act'  => $actual,
                ':st'   => $status,
                ':dt'   => date('Y-m-d H:i:s'),
                ':id'   => $id,
            ]
        );
        Logger::audit("Compromisso #{$id} atualizado na agenda", 'Agenda');
        flash('success', 'Compromisso atualizado na agenda.');
    } else {
        Database::insert(
            "INSERT INTO agenda_events (title, event_type, description, start_datetime, end_datetime, neighborhood_id, address, responsible_name, participants_list, expected_attendance, actual_attendance, status, created_at, updated_at)
             VALUES (:t, :et, :desc, :s, :e, :nid, :addr, :resp, :part, :exp, :act, :st, :dt, :dt)",
            [
                ':t'    => $title,
                ':et'   => $type,
                ':desc' => $description,
                ':s'    => $start,
                ':e'    => $end,
                ':nid'  => $nid,
                ':addr' => $address,
                ':resp' => $responsible,
                ':part' => $participants,
                ':exp'  => $expected,
                ':act'  => $actual,
                ':st'   => $status,
                ':dt'   => date('Y-m-d H:i:s'),
            ]
        );
        Logger::audit("Novo compromisso cadastrado na agenda: {$title}", 'Agenda');
        flash('success', 'Compromisso registrado na agenda.');
    }
    header('Location: index.php?route=agenda');
    exit;
}

if ($route === 'agenda_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        Database::execute("DELETE FROM agenda_events WHERE id = :id", [':id' => $id]);
        flash('success', 'Compromisso removido da agenda.');
    }
    header('Location: index.php?route=agenda');
    exit;
}

// =============================================================================
// 9. RELATÓRIOS PDF & EXCEL (MÓDULO 11)
// =============================================================================
if ($route === 'reports') {
    $selectedReport = $_GET['type'] ?? 'demands_by_neighborhood';
    $previewData    = ExportService::getReportData($selectedReport);

    render_view('reports/index.php', [
        'pageTitle'      => 'Central de Relatórios Executivos (PDF & Excel)',
        'pageSubtitle'   => 'Exportação Instantânea de Dados Territoriais, Demandas, Radar, Redes e Eventos',
        'selectedReport' => $selectedReport,
        'previewData'    => $previewData,
    ]);
}

if ($route === 'export') {
    $type   = $_GET['type'] ?? 'demands_by_neighborhood';
    $format = $_GET['format'] ?? 'pdf';
    if ($format === 'excel') {
        ExportService::downloadExcel($type);
    } else {
        ExportService::downloadPdf($type);
    }
}

// =============================================================================
// 10. SEGURANÇA, INTEGRAÇÕES, BACKUP & LGPD (MÓDULO 12)
// =============================================================================
if ($route === 'settings') {
    $settingsMap = [
        'site_name'         => get_setting('site_name', 'Vitória Inteligência'),
        'lgpd_dpo_email'    => get_setting('lgpd_dpo_email', 'lgpd@inteligencia.fernandopaiva.com.br'),
        'openai_api_key'    => get_setting('openai_api_key', ''),
        'openai_model'      => get_setting('openai_model', 'gpt-4o-mini'),
        'rss_feeds_vitoria' => get_setting('rss_feeds_vitoria', ''),
    ];

    $users     = Database::fetchAll("SELECT id, name, email, role, active, last_login_at FROM users ORDER BY id ASC");
    $auditLogs = Database::fetchAll("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 35");
    $backups   = BackupService::listBackups();

    render_view('settings/index.php', [
        'pageTitle'   => 'Segurança, APIs Criptografadas, Backups e Auditoria LGPD',
        'pageSubtitle'=> 'Controle de Acesso (RBAC), Criptografia AES-256, Logs e Backups SQL',
        'settingsMap' => $settingsMap,
        'users'       => $users,
        'auditLogs'   => $auditLogs,
        'backups'     => $backups,
    ]);
}

if ($route === 'settings_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireRole(['admin', 'coordenador']);
    set_setting('site_name', Security::cleanInput($_POST['site_name'] ?? 'Vitória Inteligência'), false);
    set_setting('lgpd_dpo_email', Security::cleanInput($_POST['lgpd_dpo_email'] ?? ''), false);
    set_setting('openai_model', Security::cleanInput($_POST['openai_model'] ?? 'gpt-4o-mini'), false);
    set_setting('rss_feeds_vitoria', trim($_POST['rss_feeds_vitoria'] ?? ''), false);

    $openAiKey = trim($_POST['openai_api_key'] ?? '');
    if ($openAiKey !== '') {
        set_setting('openai_api_key', $openAiKey, true, 'Chave da API OpenAI (criptografada AES-256-CBC)');
    }

    Logger::audit('Configurações e chaves criptografadas atualizadas', 'Segurança & APIs');
    flash('success', 'Configurações salvas com sucesso.');
    header('Location: index.php?route=settings');
    exit;
}

if ($route === 'backup_create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireRole(['admin', 'coordenador']);
    $file = BackupService::createSqlBackup();
    flash('success', "Backup SQL completo gerado em storage/backups/{$file}.");
    header('Location: index.php?route=settings');
    exit;
}

if ($route === 'user_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireRole(['admin']);
    $name  = Security::cleanInput($_POST['name'] ?? '');
    $email = strtolower(Security::cleanInput($_POST['email'] ?? ''));
    $role  = Security::cleanInput($_POST['role'] ?? 'analista');
    $pass  = $_POST['password'] ?? '';

    if ($name !== '' && $email !== '' && strlen($pass) >= 8) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        Database::insert(
            "INSERT INTO users (name, email, password_hash, role, active, created_at, updated_at)
             VALUES (:n, :e, :h, :r, 1, :dt, :dt)",
            [':n' => $name, ':e' => $email, ':h' => $hash, ':r' => $role, ':dt' => date('Y-m-d H:i:s')]
        );
        Logger::audit("Novo usuário criado: {$email} ({$role})", 'RBAC');
        flash('success', "Usuário {$name} cadastrado com sucesso.");
    } else {
        flash('error', 'Informe nome, e-mail válido e senha com pelo menos 8 caracteres.');
    }
    header('Location: index.php?route=settings');
    exit;
}

// Fallback seguro para dashboard
header('Location: index.php?route=dashboard');
exit;
