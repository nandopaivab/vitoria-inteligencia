<?php
/**
 * VITÓRIA INTELIGÊNCIA — Central de Relatórios e Exportação PDF / Excel (Módulo 11)
 *
 * 8 Relatórios Obrigatórios:
 * 1. Demandas por bairro (demands_by_neighborhood)
 * 2. Demandas por categoria (demands_by_category)
 * 3. Evolução mensal (monthly_evolution)
 * 4. Indicadores municipais (municipal_indicators)
 * 5. Notícias monitoradas (monitored_news)
 * 6. Atividades comunitárias (community_activities)
 * 7. Redes sociais (social_media)
 * 8. Participação em eventos (event_attendance)
 */

$reportCatalog = [
    'demands_by_neighborhood' => ['title' => '1. Demandas por Bairro', 'icon' => 'bi-geo-alt-fill', 'desc' => 'Totalização de demandas abertas e concluídas por bairro oficial e Região Administrativa.'],
    'demands_by_category'     => ['title' => '2. Demandas por Categoria', 'icon' => 'bi-tags-fill', 'desc' => 'Volume de protocolos, prioridades críticas e taxa de resolução nas 11 categorias municipais.'],
    'monthly_evolution'       => ['title' => '3. Evolução Mensal', 'icon' => 'bi-graph-up-arrow', 'desc' => 'Série histórica mensal de abertura de demandas, resoluções e novos cadastros LGPD.'],
    'municipal_indicators'    => ['title' => '4. Indicadores Municipais (80 Bairros)', 'icon' => 'bi-building-check', 'desc' => 'Quadro completo dos 80 bairros oficiais (Lei 8.611/2013) com população, área, domicílios e equipamentos.'],
    'monitored_news'          => ['title' => '5. Notícias Monitoradas (Radar)', 'icon' => 'bi-newspaper', 'desc' => 'Histórico de publicações, portais oficiais, dados IBGE e tendências por categoria.'],
    'community_activities'    => ['title' => '6. Atividades Comunitárias (LGPD)', 'icon' => 'bi-people-fill', 'desc' => 'Relação auditável de voluntários, colaboradores e participantes com consentimento ativo.'],
    'social_media'            => ['title' => '7. Redes Sociais e Métricas', 'icon' => 'bi-share-fill', 'desc' => 'Desempenho do calendário editorial, status de aprovação, alcance e engajamento.'],
    'event_attendance'        => ['title' => '8. Participação em Eventos', 'icon' => 'bi-calendar2-check-fill', 'desc' => 'Relatório de reuniões, audiências públicas, locais e comparativo de público esperado x presente.'],
];
?>

<!-- Cards dos 8 Relatórios Obrigatórios -->
<div class="row g-3 mb-4">
    <?php foreach ($reportCatalog as $key => $meta): ?>
        <div class="col-xl-3 col-md-6">
            <div class="vi-card p-3 h-100 d-flex flex-column justify-content-between <?= $selectedReport === $key ? 'border-primary border-2' : '' ?>">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-primary-subtle text-primary p-2"><i class="bi <?= e($meta['icon']) ?> fs-6"></i></span>
                        <h6 class="fw-bold mb-0 small"><?= e($meta['title']) ?></h6>
                    </div>
                    <p class="text-muted small mb-3" style="font-size: 0.78rem;"><?= e($meta['desc']) ?></p>
                </div>
                <div class="d-flex gap-1">
                    <a href="index.php?route=reports&type=<?= e($key) ?>" class="btn btn-sm btn-outline-primary flex-grow-1">
                        <i class="bi bi-eye me-1"></i>Ver
                    </a>
                    <a href="index.php?route=export&type=<?= e($key) ?>&format=pdf" class="btn btn-sm btn-danger" title="Baixar PDF Oficial">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                    <a href="index.php?route=export&type=<?= e($key) ?>&format=excel" class="btn btn-sm btn-success" title="Baixar Planilha Excel">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Pré-visualização Interativa do Relatório Selecionado -->
<div class="vi-card">
    <div class="vi-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h6 class="fw-bold mb-0"><i class="bi bi-table text-primary me-2"></i><?= e($previewData['title']) ?></h6>
            <small class="text-muted">Total de linhas no relatório: <?= count($previewData['rows']) ?> | Emissão: <?= date('d/m/Y H:i') ?></small>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php?route=export&type=<?= e($selectedReport) ?>&format=pdf" class="btn btn-sm btn-danger">
                <i class="bi bi-file-earmark-pdf me-1"></i>Exportar este Relatório em PDF
            </a>
            <a href="index.php?route=export&type=<?= e($selectedReport) ?>&format=excel" class="btn btn-sm btn-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Exportar este Relatório em Excel (.xls)
            </a>
        </div>
    </div>

    <div class="table-responsive" style="max-height: 520px;">
        <table class="table table-striped table-hover align-middle mb-0 small">
            <thead class="table-dark sticky-top">
                <tr>
                    <?php foreach ($previewData['headers'] as $th): ?>
                        <th><?= e((string)$th) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($previewData['rows'])): ?>
                    <tr><td colspan="<?= count($previewData['headers']) ?>" class="text-center py-4 text-muted">Sem registros disponíveis para este relatório.</td></tr>
                <?php else: ?>
                    <?php foreach ($previewData['rows'] as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td><?= e((string)$cell) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
