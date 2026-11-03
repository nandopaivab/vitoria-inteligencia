<?php
/**
 * VITÓRIA INTELIGÊNCIA — Radar de Inteligência Municipal (Módulo 7)
 *
 * Fontes:
 * Google Trends, Google News, Portais do ES, Prefeitura de Vitória, Câmara Municipal,
 * Governo do ES, IBGE, Dados públicos municipais, Fontes públicas de redes sociais.
 *
 * Categorias:
 * Saúde, Educação, Segurança, Mobilidade, Transporte, Infraestrutura,
 * Meio ambiente, Economia, Turismo, Esporte, Administração pública.
 *
 * Diferencia explicitamente:
 * - Notícia Jornalística
 * - Portal Oficial / Governo
 * - Tendência de Busca (Google Trends)
 * - Dados Públicos / IBGE
 * - Menção Pública Social
 * Não inventa pesquisas por bairro nem realiza raspagem contrária às regras das plataformas.
 */

$radarCategories = [
    'Saúde',
    'Educação',
    'Segurança',
    'Mobilidade',
    'Transporte',
    'Infraestrutura',
    'Meio ambiente',
    'Economia',
    'Turismo',
    'Esporte',
    'Administração pública',
];

$sourceTypes = [
    'Notícia Jornalística',
    'Portal Oficial / Governo',
    'Tendência de Busca (Google Trends)',
    'Dados Públicos / IBGE',
    'Menção Pública Social',
];
?>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="vi-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h6 class="fw-bold mb-0"><i class="bi bi-graph-up text-primary me-2"></i>Comparação entre Temas e Categorias Monitoradas</h6>
                    <small class="text-muted">Diferenciação transparente entre Notícias, Portais Oficiais, Dados IBGE, Google Trends e Menções Sociais</small>
                </div>
                <div class="d-flex gap-2">
                    <form method="POST" action="index.php?route=radar_sync" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-arrow-repeat me-1"></i>Sincronizar Feeds Públicos Agora
                        </button>
                    </form>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalRadarAdd">
                        <i class="bi bi-plus-circle me-1"></i>Registrar Fonte / Notícia
                    </button>
                </div>
            </div>
            <canvas id="radarCategoryChart" height="90"></canvas>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="vi-card p-3 h-100">
            <h6 class="fw-bold mb-2"><i class="bi bi-tags-fill text-warning me-2"></i>Temas Recorrentes em Vitória/ES</h6>
            <div class="list-group list-group-flush small">
                <?php foreach ($recurringThemes as $rt): ?>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold text-dark"><?= e($rt['recurring_theme'] ?: $rt['category']) ?></div>
                            <span class="badge bg-light text-secondary border"><?= e($rt['category']) ?></span>
                        </div>
                        <span class="badge bg-primary rounded-pill"><?= (int)$rt['total'] ?> reg.</span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-3 pt-2 border-top small text-muted">
                <i class="bi bi-info-circle me-1"></i>Nota: Dados do Google Trends refletem interesse de busca agregado (ES / Grande Vitória), sem inferência fictícia por bairro.
            </div>
        </div>
    </div>
</div>

<!-- Filtros do Radar -->
<div class="vi-card p-3 mb-4">
    <form method="GET" action="index.php" class="row g-2 align-items-end">
        <input type="hidden" name="route" value="radar">
        <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Pesquisar Assunto, Órgão ou Palavra-chave</label>
            <input type="text" name="q" class="form-control form-control-sm" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Ex: Orla de São Pedro, UBS, Drenagem, IBGE...">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Categoria</label>
            <select name="category" class="form-select form-select-sm">
                <option value="">Todas as Categorias</option>
                <?php foreach ($radarCategories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= (($_GET['category'] ?? '') === $cat) ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Natureza da Fonte</label>
            <select name="source_type" class="form-select form-select-sm">
                <option value="">Todas as Fontes</option>
                <?php foreach ($sourceTypes as $st): ?>
                    <option value="<?= e($st) ?>" <?= (($_GET['source_type'] ?? '') === $st) ? 'selected' : '' ?>><?= e($st) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Filtrar</button>
            <a href="index.php?route=radar" class="btn btn-sm btn-outline-secondary">Limpar</a>
        </div>
    </form>
</div>

<!-- Lista de Notícias e Fontes Monitoradas -->
<div class="vi-card">
    <div class="vi-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-newspaper text-primary me-2"></i>Histórico de Monitoramento Público (<?= count($radarItems) ?> registros)</h6>
        <a href="index.php?route=export&type=monitored_news&format=pdf" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1"></i>Exportar PDF</a>
    </div>
    <div class="list-group list-group-flush">
        <?php if (empty($radarItems)): ?>
            <div class="p-4 text-center text-muted">Nenhum item encontrado para os filtros aplicados.</div>
        <?php else: ?>
            <?php foreach ($radarItems as $item): ?>
                <div class="list-group-item p-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <?php
                            $typeBadge = match ($item['source_type']) {
                                'Portal Oficial / Governo'           => 'bg-success',
                                'Dados Públicos / IBGE'              => 'bg-dark',
                                'Tendência de Busca (Google Trends)' => 'bg-warning text-dark',
                                'Menção Pública Social'              => 'bg-info text-dark',
                                default                              => 'bg-primary',
                            };
                            ?>
                            <span class="badge <?= $typeBadge ?> me-1"><?= e($item['source_type']) ?></span>
                            <span class="badge bg-light text-dark border me-1"><?= e($item['category']) ?></span>
                            <?php if (!empty($item['neighborhood_name'])): ?>
                                <span class="badge bg-secondary-subtle text-secondary-emphasis"><i class="bi bi-geo-alt me-1"></i><?= e($item['neighborhood_name']) ?></span>
                            <?php endif; ?>
                            <h6 class="fw-bold mt-2 mb-1"><?= e($item['title']) ?></h6>
                        </div>
                        <div class="text-end small">
                            <div class="text-muted"><i class="bi bi-calendar3 me-1"></i><?= e(date('d/m/Y H:i', strtotime($item['published_at']))) ?></div>
                            <div class="fw-semibold text-secondary"><?= e($item['source_name']) ?></div>
                        </div>
                    </div>

                    <p class="small text-muted mb-2 mt-1"><?= e($item['content_excerpt']) ?></p>

                    <?php if (!empty($item['ai_summary'])): ?>
                        <div class="p-2 rounded bg-light border-start border-4 border-primary small mb-2">
                            <i class="bi bi-stars text-primary me-1"></i><strong>Resumo Analítico:</strong> <?= e($item['ai_summary']) ?>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div class="small text-muted">
                            <strong>Tema:</strong> <?= e($item['recurring_theme'] ?: $item['category']) ?>
                        </div>
                        <div class="d-flex gap-2">
                            <form method="POST" action="index.php?route=radar_ai_summarize" class="m-0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-stars me-1"></i>Atualizar Resumo IA
                                </button>
                            </form>
                            <a href="<?= e($item['source_url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                Fonte Original <i class="bi bi-box-arrow-up-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Adicionar Item ao Radar -->
<div class="modal fade" id="modalRadarAdd" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="index.php?route=radar_save" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header" style="background-color: #101B33; color: #fff;">
                <h5 class="modal-title fw-bold">Registrar Notícia, Dado Público ou Tendência no Radar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Título da Notícia / Pauta Pública *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Natureza da Fonte *</label>
                        <select name="source_type" class="form-select" required>
                            <?php foreach ($sourceTypes as $st): ?>
                                <option value="<?= e($st) ?>"><?= e($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Nome do Veículo / Órgão *</label>
                        <input type="text" name="source_name" class="form-control" placeholder="Ex: Prefeitura de Vitória / A Gazeta / IBGE" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Categoria *</label>
                        <select name="category" class="form-select" required>
                            <?php foreach ($radarCategories as $cat): ?>
                                <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Bairro Relacionado (Opcional)</label>
                        <select name="neighborhood_id" class="form-select">
                            <option value="">Município Geral (Vitória/ES)</option>
                            <?php foreach ($neighborhoods as $n): ?>
                                <option value="<?= (int)$n['id'] ?>"><?= e($n['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Link para a Fonte Original (URL) *</label>
                        <input type="url" name="source_url" class="form-control" placeholder="https://www.vitoria.es.gov.br/..." required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Tema Recorrente</label>
                        <input type="text" name="recurring_theme" class="form-control" placeholder="Ex: Obras de Macrodrenagem">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Trecho / Conteúdo Principal *</label>
                        <textarea name="content_excerpt" rows="3" class="form-control" required></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar no Radar Municipal</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels = <?= json_encode(array_column($categoryStats, 'category')) ?>;
    const totals = <?= json_encode(array_map('intval', array_column($categoryStats, 'total'))) ?>;

    new Chart(document.getElementById('radarCategoryChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Volume de Publicações / Fontes por Tema',
                data: totals,
                backgroundColor: '#2563EB',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
});
</script>
