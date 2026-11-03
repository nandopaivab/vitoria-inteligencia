<?php
/**
 * VITÓRIA INTELIGÊNCIA — Layout Mestre (Sidebar #101B33, Topbar com Notificações, Responsivo)
 */
use App\Core\Auth;
use App\Core\Database;

$currentUser = Auth::user();
$currentRoute = $_GET['route'] ?? 'dashboard';

$unreadNotifications = [];
try {
    $unreadNotifications = Database::fetchAll(
        "SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC LIMIT 8"
    );
} catch (\Throwable $e) {
    $unreadNotifications = [];
}

$flashSuccess = flash('success');
$flashError   = flash('error');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Painel Estratégico') ?> — Vitória Inteligência</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="public/assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>

<!-- Menu Lateral (#101B33) -->
<aside class="vi-sidebar" id="viSidebar">
    <div class="vi-sidebar-brand">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-3 p-2" style="background-color: #2563EB;">
                    <i class="bi bi-compass-fill fs-5 text-white"></i>
                </span>
                <div>
                    <div class="fw-bold text-white lh-1" style="font-size: 1.05rem;">VITÓRIA</div>
                    <small class="text-info fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.08em;">INTELIGÊNCIA</small>
                </div>
            </div>
            <button class="btn btn-sm btn-outline-light d-lg-none" onclick="document.getElementById('viSidebar').classList.remove('show')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="mt-2 small text-white-50" style="font-size: 0.73rem;">
            <i class="bi bi-geo-alt-fill text-primary me-1"></i>Município de Vitória / ES (IBGE 3205309)
        </div>
    </div>

    <nav class="vi-sidebar-nav">
        <div class="vi-nav-section">Inteligência & Território</div>
        <a href="index.php?route=dashboard" class="vi-nav-link <?= $currentRoute === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard Estratégico</span>
        </a>
        <a href="index.php?route=map" class="vi-nav-link <?= $currentRoute === 'map' ? 'active' : '' ?>">
            <i class="bi bi-map"></i>
            <span>Mapa Interativo GIS</span>
        </a>

        <div class="vi-nav-section">Gestão Municipal</div>
        <a href="index.php?route=contacts" class="vi-nav-link <?= $currentRoute === 'contacts' ? 'active' : '' ?>">
            <i class="bi bi-people"></i>
            <span>Gestão Comunitária (LGPD)</span>
        </a>
        <a href="index.php?route=demands" class="vi-nav-link <?= $currentRoute === 'demands' ? 'active' : '' ?>">
            <i class="bi bi-clipboard2-pulse"></i>
            <span>Gestão de Demandas</span>
        </a>
        <a href="index.php?route=agenda" class="vi-nav-link <?= $currentRoute === 'agenda' ? 'active' : '' ?>">
            <i class="bi bi-calendar-event"></i>
            <span>Agenda & Eventos</span>
        </a>

        <div class="vi-nav-section">Comunicação & IA</div>
        <a href="index.php?route=radar" class="vi-nav-link <?= $currentRoute === 'radar' ? 'active' : '' ?>">
            <i class="bi bi-broadcast-pin"></i>
            <span>Radar Municipal</span>
        </a>
        <a href="index.php?route=social" class="vi-nav-link <?= $currentRoute === 'social' ? 'active' : '' ?>">
            <i class="bi bi-share"></i>
            <span>Central de Redes Sociais</span>
        </a>
        <a href="index.php?route=ai" class="vi-nav-link <?= $currentRoute === 'ai' ? 'active' : '' ?>">
            <i class="bi bi-stars"></i>
            <span>Inteligência Artificial</span>
        </a>

        <div class="vi-nav-section">Governança & Exportação</div>
        <a href="index.php?route=reports" class="vi-nav-link <?= $currentRoute === 'reports' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-bar-graph"></i>
            <span>Relatórios PDF & Excel</span>
        </a>
        <a href="index.php?route=settings" class="vi-nav-link <?= $currentRoute === 'settings' ? 'active' : '' ?>">
            <i class="bi bi-shield-lock"></i>
            <span>Segurança, APIs & LGPD</span>
        </a>
    </nav>

    <div class="p-3 border-top border-secondary border-opacity-25 small">
        <div class="d-flex align-items-center justify-content-between">
            <div class="text-truncate me-2">
                <div class="fw-semibold text-white text-truncate"><?= e($currentUser['name'] ?? 'Usuário') ?></div>
                <div class="text-white-50 text-uppercase" style="font-size: 0.7rem;"><?= e($currentUser['role'] ?? 'admin') ?></div>
            </div>
            <a href="index.php?route=logout" class="btn btn-sm btn-outline-light" title="Sair do Sistema">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</aside>

<!-- Área Principal -->
<div class="vi-main">
    <!-- Topbar -->
    <header class="vi-topbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-secondary d-lg-none" onclick="document.getElementById('viSidebar').classList.toggle('show')">
                <i class="bi bi-list"></i>
            </button>
            <div>
                <h5 class="mb-0 fw-bold" style="color: #101B33;"><?= e($pageTitle ?? 'Vitória Inteligência') ?></h5>
                <small class="text-muted"><?= e($pageSubtitle ?? 'Plataforma de Inteligência Territorial, Gestão Comunitária e Comunicação Digital') ?></small>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-success-subtle text-success border border-success-subtle d-none d-md-inline-flex align-items-center gap-1 px-3 py-2">
                <i class="bi bi-shield-check"></i> Conformidade LGPD Ativa
            </span>

            <!-- Dropdown de Notificações -->
            <div class="dropdown">
                <button class="btn btn-light position-relative border" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell"></i>
                    <?php if (count($unreadNotifications) > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= count($unreadNotifications) ?>
                        </span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow border-0 p-0" style="width: 340px;">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
                        <strong class="small">Notificações do Sistema</strong>
                        <?php if (count($unreadNotifications) > 0): ?>
                            <form method="POST" action="index.php?route=mark_notifications_read" class="m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none">Marcar lidas</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <?php if (empty($unreadNotifications)): ?>
                            <div class="p-3 text-center text-muted small">Nenhuma notificação pendente.</div>
                        <?php else: ?>
                            <?php foreach ($unreadNotifications as $notif): ?>
                                <a href="<?= e($notif['link'] ?: '#') ?>" class="dropdown-item p-3 border-bottom text-wrap">
                                    <div class="fw-semibold small text-dark"><?= e($notif['title']) ?></div>
                                    <div class="text-muted small mt-1"><?= e($notif['message']) ?></div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Conteúdo Dinâmico -->
    <main class="container-fluid p-4 flex-grow-1">
        <?php if ($flashSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <div><?= e($flashSuccess) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
                <div><?= e($flashError) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?= $contentHtml ?? '' ?>
    </main>

    <!-- Rodapé Institucional -->
    <footer class="bg-white border-top py-3 px-4 text-muted small d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <strong>VITÓRIA INTELIGÊNCIA</strong> — Inteligência Territorial, Gestão Comunitária e Comunicação Digital (Vitória/ES)
        </div>
        <div>
            Base Territorial Oficial: Lei Municipal nº 8.611/2013 (80 Bairros) | LGPD Lei nº 13.709/2018
        </div>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
