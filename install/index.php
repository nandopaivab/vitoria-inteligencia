<?php
/**
 * VITÓRIA INTELIGÊNCIA — Instalador Visual Plesk (/install)
 * Acessível por https://inteligencia.fernandopaiva.com.br/install
 *
 * Etapas:
 * 1. Verificar PHP (8.2+ recomendado, compatível com 7.4/8.x no Plesk)
 * 2. Verificar extensões (PDO, pdo_mysql, json, mbstring, curl, openssl)
 * 3. Verificar permissões de diretórios (config/, storage/)
 * 4. Solicitar dados MySQL
 * 5. Testar conexão PDO
 * 6. Criar tabelas automaticamente + popular 80 bairros oficiais e equipamentos de Vitória/ES
 * 7. Criar administrador com password_hash()
 * 8. Gerar config/config.php com chave AES-256
 * 9. Finalizar instalação
 * 10. Bloquear o instalador (storage/installed.lock)
 */

define('BASE_PATH', dirname(__DIR__));
define('LOCK_FILE', BASE_PATH . '/storage/installed.lock');
define('CONFIG_PATH', BASE_PATH . '/config/config.php');
define('SCHEMA_SQL_PATH', BASE_PATH . '/database/schema.sql');

session_start();

if (empty($_SESSION['install_csrf'])) {
    $_SESSION['install_csrf'] = bin2hex(random_bytes(32));
}

// Etapa 10: Se já instalado e bloqueado, impedir reexecução não autorizada
$isLocked = file_exists(LOCK_FILE) && file_exists(CONFIG_PATH);

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errorMsg = '';
$successMsg = '';

// Verificações de Ambiente (Etapas 1, 2 e 3)
$phpVersion = PHP_VERSION;
$phpOk = version_compare($phpVersion, '7.4.0', '>=');
$phpRecommended = version_compare($phpVersion, '8.2.0', '>=');

$extensions = [
    'pdo'       => extension_loaded('pdo'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'json'      => extension_loaded('json'),
    'mbstring'  => extension_loaded('mbstring'),
    'curl'      => extension_loaded('curl'),
    'openssl'   => extension_loaded('openssl'),
];

$directories = [
    'config/'          => is_writable(BASE_PATH . '/config'),
    'storage/'         => is_writable(BASE_PATH . '/storage'),
    'storage/logs/'    => is_writable(BASE_PATH . '/storage/logs'),
    'storage/backups/' => is_writable(BASE_PATH . '/storage/backups'),
    'storage/cache/'   => is_writable(BASE_PATH . '/storage/cache'),
    'storage/uploads/' => is_writable(BASE_PATH . '/storage/uploads'),
];

$envReady = $phpOk && $extensions['pdo'] && $extensions['json'] && $directories['config/'] && $directories['storage/'];

// Processamento do Formulário de Instalação (Etapas 4 a 10)
if (!$isLocked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['install_csrf'], $csrf)) {
        $errorMsg = 'Token CSRF inválido. Recarregue a página.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'install_system') {
            $dbHost = trim($_POST['db_host'] ?? 'localhost');
            $dbPort = trim($_POST['db_port'] ?? '3306');
            $dbName = trim($_POST['db_name'] ?? '');
            $dbUser = trim($_POST['db_user'] ?? '');
            $dbPass = $_POST['db_pass'] ?? '';

            $appUrl     = rtrim(trim($_POST['app_url'] ?? 'https://inteligencia.fernandopaiva.com.br'), '/');
            $adminName  = trim($_POST['admin_name'] ?? 'Administrador Geral');
            $adminEmail = trim(strtolower($_POST['admin_email'] ?? 'admin@inteligencia.fernandopaiva.com.br'));
            $adminPass  = $_POST['admin_pass'] ?? '';
            $adminPass2 = $_POST['admin_pass_confirm'] ?? '';

            if ($dbName === '' || $dbUser === '') {
                $errorMsg = 'Informe o nome do banco de dados MySQL e o usuário.';
                $step = 2;
            } elseif ($adminEmail === '' || strlen($adminPass) < 8) {
                $errorMsg = 'Informe um e-mail válido para o administrador e uma senha com pelo menos 8 caracteres.';
                $step = 2;
            } elseif ($adminPass !== $adminPass2) {
                $errorMsg = 'A confirmação da senha do administrador não confere.';
                $step = 2;
            } else {
                try {
                    // Etapa 5: Testar Conexão MySQL PDO
                    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
                    $pdo = new PDO($dsn, $dbUser, $dbPass, [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);

                    // Etapa 6: Criar Tabelas Automaticamente a partir do schema.sql
                    if (!file_exists(SCHEMA_SQL_PATH)) {
                        throw new RuntimeException('Arquivo database/schema.sql não encontrado.');
                    }

                    $sqlContent = file_get_contents(SCHEMA_SQL_PATH);
                    // Dividir instruções SQL com segurança
                    $statements = preg_split('/;\s*[\r\n]+/', $sqlContent);
                    foreach ($statements as $stmtSql) {
                        $stmtSql = trim($stmtSql);
                        if ($stmtSql === '' || strpos($stmtSql, '--') === 0) {
                            // Remover linhas puramente comentadas no início do bloco
                            $lines = explode("\n", $stmtSql);
                            $cleanLines = array_filter($lines, function ($l) {
                                $t = trim($l);
                                return $t !== '' && strpos($t, '--') !== 0;
                            });
                            $stmtSql = trim(implode("\n", $cleanLines));
                        }
                        if ($stmtSql !== '') {
                            $pdo->exec($stmtSql);
                        }
                    }

                    // Etapa 7: Criar Administrador com password_hash()
                    $passHash = password_hash($adminPass, PASSWORD_DEFAULT);
                    $stmtAdmin = $pdo->prepare(
                        "INSERT INTO users (name, email, password_hash, role, active, created_at, updated_at)
                         VALUES (:name, :email, :hash, 'admin', 1, NOW(), NOW())
                         ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), role = 'admin', active = 1"
                    );
                    $stmtAdmin->execute([
                        ':name'  => $adminName,
                        ':email' => $adminEmail,
                        ':hash'  => $passHash,
                    ]);

                    // Etapa 8: Gerar config/config.php com chave AES-256 exclusiva
                    $appKey = bin2hex(random_bytes(32));
                    $installedAt = date('Y-m-d H:i:s');

                    $configArray = [
                        'app_name'         => 'Vitória Inteligência',
                        'app_url'          => $appUrl,
                        'timezone'         => 'America/Sao_Paulo',
                        'environment'      => 'production',
                        'debug'            => false,
                        'db'               => [
                            'driver'   => 'mysql',
                            'host'     => $dbHost,
                            'port'     => $dbPort,
                            'database' => $dbName,
                            'username' => $dbUser,
                            'password' => $dbPass,
                            'charset'  => 'utf8mb4',
                        ],
                        'app_key'          => $appKey,
                        'session_name'     => 'VITORIA_INTEL_SESS',
                        'session_lifetime' => 7200,
                        'installed_at'     => $installedAt,
                    ];

                    $configPhp = "<?php\n/**\n * VITÓRIA INTELIGÊNCIA — Configuração Gerada pelo Instalador Plesk em {$installedAt}\n */\n\nreturn " . var_export($configArray, true) . ";\n";
                    file_put_contents(CONFIG_PATH, $configPhp, LOCK_EX);
                    @chmod(CONFIG_PATH, 0640);

                    // Etapa 10: Bloquear o instalador
                    $lockPayload = json_encode([
                        'installed_at' => $installedAt,
                        'admin_email'  => $adminEmail,
                        'php_version'  => PHP_VERSION,
                        'ip'           => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    file_put_contents(LOCK_FILE, $lockPayload, LOCK_EX);

                    $isLocked = true;
                    $step = 3;
                    $successMsg = 'Instalação concluída com sucesso! Todas as 17 tabelas, 80 bairros oficiais e 75 equipamentos públicos de Vitória/ES foram configurados.';
                } catch (Throwable $e) {
                    $errorMsg = 'Erro durante a instalação: ' . $e->getMessage();
                    $step = 2;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador Plesk — Vitória Inteligência</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --vi-navy: #101B33;
            --vi-blue: #2563EB;
            --vi-gray: #F1F5F9;
            --vi-green: #16A34A;
            --vi-orange: #F59E0B;
        }
        body {
            background-color: var(--vi-gray);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
        }
        .installer-header {
            background: linear-gradient(135deg, var(--vi-navy) 0%, #1e3a8a 100%);
            color: #fff;
            padding: 2.5rem 0;
            border-bottom: 4px solid var(--vi-blue);
        }
        .step-badge {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            background: #cbd5e1;
            color: #334155;
        }
        .step-badge.active {
            background: var(--vi-blue);
            color: #fff;
        }
        .step-badge.done {
            background: var(--vi-green);
            color: #fff;
        }
        .card-installer {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(16, 27, 51, 0.08);
        }
    </style>
</head>
<body>

<div class="installer-header mb-4">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <span class="badge bg-primary mb-2"><i class="bi bi-shield-lock me-1"></i> INSTALADOR OFICIAL PLESK</span>
                <h2 class="fw-bold mb-1">VITÓRIA INTELIGÊNCIA</h2>
                <p class="mb-0 text-white-50">Plataforma de Inteligência Territorial, Gestão Comunitária e Comunicação Digital — Vitória/ES</p>
            </div>
            <div class="text-end">
                <span class="badge bg-light text-dark px-3 py-2">PHP <?= htmlspecialchars($phpVersion) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <?php if ($isLocked && $step !== 3): ?>
                <div class="card card-installer p-4 p-md-5 text-center">
                    <div class="mb-3">
                        <i class="bi bi-lock-fill text-success" style="font-size: 3.5rem;"></i>
                    </div>
                    <h3 class="fw-bold">Instalador Bloqueado por Segurança</h3>
                    <p class="text-muted mx-auto" style="max-width: 620px;">
                        O sistema <strong>Vitória Inteligência</strong> já está instalado e ativo neste servidor Plesk.
                        Por motivos de segurança (Etapa 10), o instalador foi bloqueado automaticamente através do arquivo <code>storage/installed.lock</code>.
                    </p>
                    <div class="alert alert-info text-start mx-auto mt-3" style="max-width: 620px;">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Caso precise reinstalar do zero, remova manualmente o arquivo <code>storage/installed.lock</code> pelo Gerenciador de Arquivos do Plesk.
                    </div>
                    <div class="mt-4">
                        <a href="../index.php" class="btn btn-primary btn-lg px-5">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Acessar Plataforma Vitória Inteligência
                        </a>
                    </div>
                </div>
            <?php else: ?>

                <!-- Barra de Etapas -->
                <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-3 shadow-sm mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="step-badge <?= $step === 1 ? 'active' : 'done' ?>">1</span>
                        <span class="fw-semibold small">1. Diagnóstico do Servidor Plesk</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                    <div class="d-flex align-items-center gap-2">
                        <span class="step-badge <?= $step === 2 ? 'active' : ($step > 2 ? 'done' : '') ?>">2</span>
                        <span class="fw-semibold small">2. Banco MySQL e Administrador</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                    <div class="d-flex align-items-center gap-2">
                        <span class="step-badge <?= $step === 3 ? 'done' : '' ?>">3</span>
                        <span class="fw-semibold small">3. Conclusão e Bloqueio</span>
                    </div>
                </div>

                <?php if ($errorMsg !== ''): ?>
                    <div class="alert alert-danger d-flex align-items-center shadow-sm">
                        <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                        <div><?= htmlspecialchars($errorMsg) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($step === 1): ?>
                    <div class="card card-installer p-4">
                        <h4 class="fw-bold mb-3"><i class="bi bi-cpu me-2 text-primary"></i>Etapas 1 a 3: Verificação de PHP, Extensões e Permissões</h4>
                        <p class="text-muted small">Verificando se a hospedagem Plesk atende a todos os requisitos da plataforma Vitória Inteligência.</p>

                        <h6 class="fw-bold mt-3 text-uppercase small text-secondary">1. Versão do PHP</h6>
                        <ul class="list-group mb-4">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Versão Atual do PHP: <?= htmlspecialchars($phpVersion) ?></strong>
                                    <div class="small text-muted">Recomendado PHP 8.2+ (compatível com PHP 7.4+ / 8.0 / 8.1 / 8.2 / 8.3 no Plesk)</div>
                                </div>
                                <?php if ($phpRecommended): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>PHP 8.2+ Ideal</span>
                                <?php elseif ($phpOk): ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-check-circle me-1"></i>Compatível</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Incompatível</span>
                                <?php endif; ?>
                            </li>
                        </ul>

                        <h6 class="fw-bold text-uppercase small text-secondary">2. Extensões Obrigatórias do PHP</h6>
                        <div class="row g-2 mb-4">
                            <?php foreach ($extensions as $ext => $loaded): ?>
                                <div class="col-md-4">
                                    <div class="border rounded p-2 d-flex justify-content-between align-items-center bg-light">
                                        <span class="fw-semibold"><code><?= htmlspecialchars($ext) ?></code></span>
                                        <?php if ($loaded): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-lg"></i> Ativa</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="bi bi-x-lg"></i> Ausente</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <h6 class="fw-bold text-uppercase small text-secondary">3. Permissões de Escrita de Diretórios</h6>
                        <div class="row g-2 mb-4">
                            <?php foreach ($directories as $dir => $writable): ?>
                                <div class="col-md-4">
                                    <div class="border rounded p-2 d-flex justify-content-between align-items-center bg-light">
                                        <span><code><?= htmlspecialchars($dir) ?></code></span>
                                        <?php if ($writable): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-lg"></i> Gravável</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="bi bi-x-lg"></i> Sem Permissão</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-flex justify-content-end mt-2">
                            <?php if ($envReady): ?>
                                <a href="?step=2" class="btn btn-primary btn-lg px-4">
                                    Avançar para Configuração MySQL <i class="bi bi-arrow-right ms-2"></i>
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-lg" disabled>Corrija os itens pendentes no Plesk para avançar</button>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($step === 2): ?>
                    <div class="card card-installer p-4">
                        <h4 class="fw-bold mb-3"><i class="bi bi-database-check me-2 text-primary"></i>Etapas 4 a 8: Banco de Dados MySQL e Conta Administradora</h4>
                        <form method="POST" action="?step=2">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['install_csrf']) ?>">
                            <input type="hidden" name="action" value="install_system">

                            <div class="row g-3">
                                <div class="col-12">
                                    <h6 class="fw-bold text-primary border-bottom pb-2">Configuração do Banco MySQL / MariaDB (Plesk)</h6>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">Servidor MySQL (Host)</label>
                                    <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Porta</label>
                                    <input type="text" name="db_port" class="form-control" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Nome do Banco de Dados</label>
                                    <input type="text" name="db_name" class="form-control" placeholder="ex: vitoria_inteligencia" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Usuário MySQL</label>
                                    <input type="text" name="db_user" class="form-control" placeholder="ex: vitoria_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Senha do Banco</label>
                                    <input type="password" name="db_pass" class="form-control" placeholder="Senha do usuário MySQL">
                                </div>

                                <div class="col-12 mt-4">
                                    <h6 class="fw-bold text-primary border-bottom pb-2">Domínio e Administrador Geral</h6>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">URL Pública da Instalação (Subdomínio Plesk)</label>
                                    <input type="url" name="app_url" class="form-control" value="<?= htmlspecialchars($_POST['app_url'] ?? 'https://inteligencia.fernandopaiva.com.br') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nome do Administrador</label>
                                    <input type="text" name="admin_name" class="form-control" value="<?= htmlspecialchars($_POST['admin_name'] ?? 'Fernando Paiva - Admin') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">E-mail de Login</label>
                                    <input type="email" name="admin_email" class="form-control" value="<?= htmlspecialchars($_POST['admin_email'] ?? 'admin@fernandopaiva.com.br') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Senha do Administrador (mínimo 8 caracteres)</label>
                                    <input type="password" name="admin_pass" class="form-control" minlength="8" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Confirmar Senha</label>
                                    <input type="password" name="admin_pass_confirm" class="form-control" minlength="8" required>
                                </div>
                            </div>

                            <div class="alert alert-light border mt-4 small">
                                <i class="bi bi-check2-circle text-success me-1"></i>
                                Ao clicar em <strong>Instalar Vitória Inteligência</strong>, o sistema irá testar a conexão PDO, criar as 17 tabelas automaticamente, importar a malha oficial dos 80 bairros e 75 equipamentos públicos de Vitória/ES, gravar <code>config/config.php</code> e bloquear este instalador.
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="?step=1" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-1"></i>Voltar
                                </a>
                                <button type="submit" class="btn btn-success btn-lg px-4">
                                    <i class="bi bi-rocket-takeoff me-2"></i>Instalar Vitória Inteligência
                                </button>
                            </div>
                        </form>
                    </div>

                <?php elseif ($step === 3): ?>
                    <div class="card card-installer p-4 p-md-5 text-center">
                        <div class="mb-3">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h3 class="fw-bold text-success">Instalação Finalizada e Bloqueada com Sucesso!</h3>
                        <p class="text-muted mx-auto" style="max-width: 650px;">
                            <?= htmlspecialchars($successMsg) ?>
                        </p>
                        <div class="row g-3 text-start my-3">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded border h-100">
                                    <h6 class="fw-bold"><i class="bi bi-shield-check text-success me-2"></i>Segurança Ativada</h6>
                                    <p class="small text-muted mb-0">
                                        Arquivo <code>config/config.php</code> gerado com chave AES-256 exclusiva e instalador bloqueado em <code>storage/installed.lock</code>.
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded border h-100">
                                    <h6 class="fw-bold"><i class="bi bi-clock-history text-primary me-2"></i>Tarefas Agendadas (Plesk Cron)</h6>
                                    <p class="small text-muted mb-0">
                                        Configure no painel Plesk os scripts da pasta <code>cron/</code> conforme documentado no arquivo <code>README.md</code>.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="../index.php?route=login" class="btn btn-primary btn-lg px-5">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Fazer Login no Painel
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>
</div>

</body>
</html>
