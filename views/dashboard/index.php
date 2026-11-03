<?php
/**
 * VITÓRIA INTELIGÊNCIA — Dashboard Central (Módulo 3)
 * Indicadores Obrigatórios:
 * 1. Contatos cadastrados com consentimento
 * 2. Voluntários cadastrados
 * 3. Demandas comunitárias
 * 4. Bairros monitorados
 * 5. Eventos realizados
 * 6. Reuniões agendadas
 * 7. Notícias monitoradas
 * 8. Publicações nas redes sociais
 * 9. Indicadores de engajamento
 * + Gráficos de evolução mensal e comparações territoriais (Chart.js)
 */
?>

<!-- 9 Cards de Indicadores Estratégicos -->
<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-6">
        <div class="vi-kpi-card d-flex align-items-center justify-content-between">
            <div>
                <div class="text-muted small fw-semibold text-uppercase">1. Contatos com Consentimento LGPD</div>
                <h3 class="fw-bold mb-1 mt-1" style="color: #101B33;"><?= number_format((int)$kpis['consented_contacts'], 0, ',', '.') ?></h3>
                <span class="badge bg-success-subtle text-success"><i class="bi bi-shield-check me-1"></i>100% Auditados LGPD</span>
            </div>
            <div class="vi-kpi-icon" style="background-color: rgba(37, 99, 235, 0.12); color: #2563EB;">
                <i class="bi bi-person-check-fill"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="vi-kpi-card d-flex align-items-center justify-content-between">
            <div>
                <div class="text-muted small fw-semibold text-uppercase">2. Voluntários Cadastrados</div>
                <h3 class="fw-bold mb-1 mt-1" style="color: #101B33;"><?= number_format((int)$kpis['volunteers_count'], 0, ',', '.') ?></h3>
                <span class="small text-muted">Ativos nas 9 Regiões Administrativas</span>
            </div>
            <div class="vi-kpi-icon" style="background-color: rgba(22, 163, 74, 0.12); color: #16A34A;">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="vi-kpi-card d-flex align-items-center justify-content-between">
            <div>
                <div class="text-muted small fw-semibold text-uppercase">3. Demandas Comunitárias</div>
                <h3 class="fw-bold mb-1 mt-1" style="color: #101B33;"><?= number_format((int)$kpis['total_demands'], 0, ',', '.') ?></h3>
                <span class="badge bg-warning-subtle text-warning-emphasis"><?= (int)$kpis['open_demands'] ?> em andamento</span>
                <span class="badge bg-success-subtle text-success ms-1"><?= (int)$kpis['resolved_demands'] ?> concluídas</span>
            </div>
            <div class="vi-kpi-icon" style="background-color: rgba(245, 158, 11, 0.15); color: #F59E0B;">
                <i class="bi bi-clipboard2-pulse-fill"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="vi-kpi-card d-flex align-items-center justify-content-between">
            <div>
                <div class="text-muted small fw-semibold text-uppercase">4. Bairros Monitorados (Vitória/ES)</div>
                <h3 class="fw-bold mb-1 mt-1" style="color: #101B33;"><?= (int)$kpis['monitored_neighborhoods'] ?> <small class="fs-6 text-muted">/ 80 oficiais</small></h3>
                <span class="small text-muted">Lei Municipal nº 8.611/2013</span>
            </div>
            <div class="vi-kpi-icon" style="background-color: rgba(16, 27, 51, 0.1); color: #101B33;">
                <i class="bi bi-geo-alt-fill"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="vi-kpi-card d-flex align-items-center justify-content-between">
            <div>
                <div class="text-muted small fw-semibold text-uppercase">5. Eventos Realizados</div>
                <h3 class="fw-bold mb-1 mt-1" style="color: #101B33;"><?= number_format((int)$kpis['completed_events'], 0, ',', '.') ?></h3>
                <span class="small text-success"><i class="bi bi-check2-all me-1"></i>Atividades comunitárias concluídas</span>
            </div>
            <div class="vi-kpi-icon" style="background-color: rgba(22, 163, 74, 0.12); color: #16A34A;">
                <i class="bi bi-calendar2-check-fill"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="vi-kpi-card d-flex align-items-center justify-content-between">
            <div>
                <div class="text-muted small fw-semibold text-uppercase">6. Reuniões Agendadas</div>
                <h3 class="fw-bold mb-1 mt-1" style="color: #101B33;"><?= number_format((int)$kpis['scheduled_meetings'], 0, ',', '.') ?></h3>
                <span class="small text-primary"><i class="bi bi-clock-history me-1"></i>Próximos compromissos na agenda</span>
            </div>
            <div class="vi-kpi-icon" style="background-color: rgba(37, 99, 235, 0.12); color: #2563EB;">
                <i class="bi bi-calendar-week-fill"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="vi-kpi-card d-flex align-items-center justify-content-between">
            <div>
                <div class="text-muted small fw-semibold text-uppercase">7. Notícias Monitoradas (Radar)</div>
                <h3 class="fw-bold mb-1 mt-1" style="color: #101B33;"><?= number_format((int)$kpis['monitored_news'], 0, ',', '.') ?></h3>
                <span class="small text-muted">Portais ES, PMV, Câmara, IBGE e Trends</span>
            </div>
            <div class="vi-kpi-icon" style="background-color: rgba(245, 158, 11, 0.15); color: #F59E0B;">
                <i class="bi bi-newspaper"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="vi-kpi-card d-flex align-items-center justify-content-between">
            <div>
                <div class="text-muted small fw-semibold text-uppercase">8. Publicações nas Redes Sociais</div>
                <h3 class="fw-bold mb-1 mt-1" style="color: #101B33;"><?= number_format((int)$kpis['social_posts_count'], 0, ',', '.') ?></h3>
                <span class="small text-muted"><?= (int)$kpis['social_approved_queue'] ?> na fila de aprovação/disparo</span>
            </div>
            <div class="vi-kpi-icon" style="background-color: rgba(37, 99, 235, 0.12); color: #2563EB;">
                <i class="bi bi-megaphone-fill"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-12">
        <div class="vi-kpi-card d-flex align-items-center justify-content-between">
            <div>
                <div class="text-muted small fw-semibold text-uppercase">9. Indicador de Engajamento Digital</div>
                <h3 class="fw-bold mb-1 mt-1" style="color: #16A34A;"><?= number_format((float)$kpis['avg_engagement_rate'], 2, ',', '.') ?>%</h3>
                <span class="small text-muted">Alcance acumulado: <?= number_format((int)$kpis['total_reach'], 0, ',', '.') ?> contas</span>
            </div>
            <div class="vi-kpi-icon" style="background-color: rgba(22, 163, 74, 0.12); color: #16A34A;">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos de Evolução Mensal e Comparação Territorial -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="vi-card h-100">
            <div class="vi-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart-line text-primary me-2"></i>Evolução Mensal (Demandas x Contatos LGPD)</h6>
                    <small class="text-muted">Histórico consolidado de atendimentos e novos cadastros comunitários</small>
                </div>
                <a href="index.php?route=reports&type=monthly_evolution" class="btn btn-sm btn-outline-primary">Exportar Dados</a>
            </div>
            <div class="p-3">
                <canvas id="monthlyEvolutionChart" height="125"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="vi-card h-100">
            <div class="vi-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0"><i class="bi bi-pie-chart text-primary me-2"></i>Comparação Territorial por Região Administrativa</h6>
                    <small class="text-muted">Distribuição de demandas entre as 9 RAs de Vitória/ES</small>
                </div>
                <a href="index.php?route=map" class="btn btn-sm btn-outline-secondary">Ver no Mapa</a>
            </div>
            <div class="p-3">
                <canvas id="regionalComparisonChart" height="165"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Tabelas Rápidas de Demandas Recentes e Radar Municipal -->
<div class="row g-4">
    <div class="col-lg-7">
        <div class="vi-card">
            <div class="vi-card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-list-check text-primary me-2"></i>Demandas Comunitárias Recentes por Bairro</h6>
                <a href="index.php?route=demands" class="btn btn-sm btn-primary">Gerenciar Demandas</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Protocolo</th>
                            <th>Título / Demanda</th>
                            <th>Bairro</th>
                            <th>Categoria</th>
                            <th>Prioridade</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentDemands as $d): ?>
                            <tr>
                                <td class="fw-semibold"><code><?= e($d['protocol_code']) ?></code></td>
                                <td><?= e($d['title']) ?></td>
                                <td><?= e($d['neighborhood_name']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= e($d['category']) ?></span></td>
                                <td>
                                    <?php
                                    $pBadge = match ($d['priority']) {
                                        'Urgente' => 'bg-danger',
                                        'Alta'    => 'bg-warning text-dark',
                                        'Média'   => 'bg-primary',
                                        default   => 'bg-secondary',
                                    };
                                    ?>
                                    <span class="badge <?= $pBadge ?>"><?= e($d['priority']) ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $d['status'] === 'Concluída' ? 'bg-success' : 'bg-info-subtle text-info-emphasis' ?>">
                                        <?= e($d['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="vi-card">
            <div class="vi-card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-broadcast text-warning me-2"></i>Últimos Alertas do Radar Municipal</h6>
                <a href="index.php?route=radar" class="btn btn-sm btn-outline-primary">Abrir Radar</a>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($recentRadar as $item): ?>
                    <div class="list-group-item p-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge bg-primary-subtle text-primary"><?= e($item['category']) ?></span>
                            <small class="text-muted"><?= e(date('d/m/Y H:i', strtotime($item['published_at']))) ?></small>
                        </div>
                        <div class="fw-semibold small mb-1"><?= e($item['title']) ?></div>
                        <div class="d-flex justify-content-between align-items-center small text-muted">
                            <span><i class="bi bi-building me-1"></i><?= e($item['source_name']) ?> (<?= e($item['source_type']) ?>)</span>
                            <a href="<?= e($item['source_url']) ?>" target="_blank" rel="noopener" class="text-decoration-none">Fonte <i class="bi bi-box-arrow-up-right"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const monthlyLabels = <?= json_encode($chartMonthly['labels']) ?>;
    const monthlyDemands = <?= json_encode($chartMonthly['demands']) ?>;
    const monthlyContacts = <?= json_encode($chartMonthly['contacts']) ?>;

    new Chart(document.getElementById('monthlyEvolutionChart'), {
        type: 'line',
        data: {
            labels: monthlyLabels,
            datasets: [
                {
                    label: 'Demandas Registradas',
                    data: monthlyDemands,
                    borderColor: '#2563EB',
                    backgroundColor: 'rgba(37, 99, 235, 0.12)',
                    tension: 0.35,
                    fill: true,
                    borderWidth: 2.5
                },
                {
                    label: 'Contatos com Consentimento LGPD',
                    data: monthlyContacts,
                    borderColor: '#16A34A',
                    backgroundColor: 'rgba(22, 163, 74, 0.1)',
                    tension: 0.35,
                    fill: true,
                    borderWidth: 2.5
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    const regionLabels = <?= json_encode($chartRegions['labels']) ?>;
    const regionCounts = <?= json_encode($chartRegions['counts']) ?>;

    new Chart(document.getElementById('regionalComparisonChart'), {
        type: 'bar',
        data: {
            labels: regionLabels,
            datasets: [{
                label: 'Demandas por Região Administrativa',
                data: regionCounts,
                backgroundColor: [
                    '#101B33', '#2563EB', '#16A34A', '#F59E0B',
                    '#3B82F6', '#0D9488', '#6366F1', '#EA580C', '#475569'
                ],
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
});
</script>
