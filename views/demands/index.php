<?php
/**
 * VITÓRIA INTELIGÊNCIA — Gestão de Demandas Municipais (Módulo 6)
 *
 * Categorias Obrigatórias:
 * Saúde, Educação, Mobilidade, Segurança pública, Infraestrutura, Iluminação,
 * Limpeza urbana, Saneamento, Meio ambiente, Esporte, Assistência social.
 *
 * Campos Obrigatórios:
 * Identificação (protocol_code), Título, Descrição, Bairro, Endereço ou localização,
 * Coordenadas (lat/lng), Categoria, Data, Prioridade administrativa, Status, Responsável, Histórico.
 */

$demandCategories = [
    'Saúde',
    'Educação',
    'Mobilidade',
    'Segurança pública',
    'Infraestrutura',
    'Iluminação',
    'Limpeza urbana',
    'Saneamento',
    'Meio ambiente',
    'Esporte',
    'Assistência social',
];

$demandStatuses = ['Aberta', 'Em Análise', 'Encaminhada ao Órgão', 'Em Execução', 'Concluída', 'Arquivada'];
$demandPriorities = ['Baixa', 'Média', 'Alta', 'Urgente'];
?>

<!-- Resumo Agregado por Categoria e Bairro -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="vi-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart-steps text-primary me-2"></i>Demandas por Categoria Municipal</h6>
                <div class="d-flex gap-2">
                    <a href="index.php?route=export&type=demands_by_category&format=pdf" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1"></i>PDF Categorias</a>
                    <a href="index.php?route=export&type=demands_by_neighborhood&format=excel" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel Bairros</a>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-2">
                <?php foreach ($summaryByCategory as $sc): ?>
                    <span class="badge bg-light text-dark border px-3 py-2">
                        <strong><?= e($sc['category']) ?>:</strong> <?= (int)$sc['total'] ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="vi-card p-3 h-100 d-flex flex-column justify-content-between">
            <div>
                <h6 class="fw-bold mb-1"><i class="bi bi-plus-square-fill text-success me-2"></i>Abertura Rápida de Demanda</h6>
                <p class="small text-muted mb-2">Registre protocolos georreferenciados vinculados aos 80 bairros oficiais de Vitória/ES.</p>
            </div>
            <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#modalDemandForm" onclick="openNewDemandModal()">
                <i class="bi bi-plus-circle me-1"></i>Cadastrar Nova Demanda Municipal
            </button>
        </div>
    </div>
</div>

<!-- Filtros de Demandas -->
<div class="vi-card p-3 mb-4">
    <form method="GET" action="index.php" class="row g-2 align-items-end">
        <input type="hidden" name="route" value="demands">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Busca por Protocolo ou Título</label>
            <input type="text" name="q" class="form-control form-control-sm" value="<?= e($_GET['q'] ?? '') ?>" placeholder="VIT-2026-... ou palavra-chave">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Categoria Municipal</label>
            <select name="category" class="form-select form-select-sm">
                <option value="">Todas as 11 Categorias</option>
                <?php foreach ($demandCategories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= (($_GET['category'] ?? '') === $cat) ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Bairro</label>
            <select name="neighborhood_id" class="form-select form-select-sm">
                <option value="">Todos os Bairros</option>
                <?php foreach ($neighborhoods as $n): ?>
                    <option value="<?= (int)$n['id'] ?>" <?= (isset($_GET['neighborhood_id']) && (int)$_GET['neighborhood_id'] === (int)$n['id']) ? 'selected' : '' ?>>
                        <?= e($n['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach ($demandStatuses as $st): ?>
                    <option value="<?= e($st) ?>" <?= (($_GET['status'] ?? '') === $st) ? 'selected' : '' ?>><?= e($st) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Filtrar</button>
            <a href="index.php?route=demands" class="btn btn-sm btn-outline-secondary">Limpar</a>
        </div>
    </form>
</div>

<!-- Tabela de Demandas -->
<div class="vi-card">
    <div class="vi-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-clipboard2-pulse text-primary me-2"></i>Protocolos de Demandas Municipais (<?= count($demands) ?>)</h6>
        <a href="index.php?route=map" class="btn btn-sm btn-outline-primary"><i class="bi bi-map me-1"></i>Visualizar no Mapa de Calor</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Identificação</th>
                    <th>Título & Localização</th>
                    <th>Bairro</th>
                    <th>Categoria</th>
                    <th>Data</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <th>Responsável</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($demands)): ?>
                    <tr><td colspan="9" class="text-center py-4 text-muted">Nenhuma demanda encontrada para os critérios selecionados.</td></tr>
                <?php else: ?>
                    <?php foreach ($demands as $d): ?>
                        <tr>
                            <td class="fw-bold text-nowrap">
                                <code><?= e($d['protocol_code']) ?></code>
                                <?php if (!empty($d['external_reference'])): ?>
                                    <div class="text-muted" style="font-size: 0.7rem;">Ref: <?= e($d['external_reference']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($d['title']) ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    <i class="bi bi-geo-alt me-1"></i><?= e($d['address_location']) ?>
                                    (<code><?= e($d['lat']) ?>, <?= e($d['lng']) ?></code>)
                                </div>
                            </td>
                            <td class="fw-semibold"><?= e($d['neighborhood_name']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= e($d['category']) ?></span></td>
                            <td><?= e(date('d/m/Y', strtotime($d['demand_date']))) ?></td>
                            <td>
                                <?php
                                $pClass = match ($d['priority']) {
                                    'Urgente' => 'bg-danger',
                                    'Alta'    => 'bg-warning text-dark',
                                    'Média'   => 'bg-primary',
                                    default   => 'bg-secondary',
                                };
                                ?>
                                <span class="badge <?= $pClass ?>"><?= e($d['priority']) ?></span>
                            </td>
                            <td>
                                <?php
                                $sClass = match ($d['status']) {
                                    'Concluída'            => 'bg-success',
                                    'Em Execução'          => 'bg-primary',
                                    'Encaminhada ao Órgão' => 'bg-info text-dark',
                                    default                => 'bg-secondary-subtle text-secondary-emphasis',
                                };
                                ?>
                                <span class="badge <?= $sClass ?>"><?= e($d['status']) ?></span>
                            </td>
                            <td><?= e($d['responsible_name']) ?></td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-info"
                                        onclick='openDemandHistoryModal(<?= json_encode($d, JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($historyByDemand[$d["id"]] ?? [], JSON_UNESCAPED_UNICODE) ?>)'
                                        title="Histórico e Tramitação">
                                    <i class="bi bi-clock-history"></i> (<?= count($historyByDemand[$d['id']] ?? []) ?>)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        onclick='openEditDemandModal(<?= json_encode($d, JSON_UNESCAPED_UNICODE) ?>)'
                                        title="Editar Demanda">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="index.php?route=demand_delete" class="d-inline" onsubmit="return confirm('Deseja excluir esta demanda?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Cadastrar / Editar Demanda -->
<div class="modal fade" id="modalDemandForm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="index.php?route=demand_save" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="demand_id" value="">
            <div class="modal-header" style="background-color: #101B33; color: #fff;">
                <h5 class="modal-title fw-bold" id="modalDemandTitle">Registrar Demanda Municipal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Identificação / Protocolo *</label>
                        <input type="text" name="protocol_code" id="demand_protocol" class="form-control" value="VIT-<?= date('Y') ?>-<?= str_pad((string)(count($demands) + 11), 4, '0', STR_PAD_LEFT) ?>" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Título da Demanda *</label>
                        <input type="text" name="title" id="demand_title" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Categoria Municipal *</label>
                        <select name="category" id="demand_category" class="form-select" required>
                            <?php foreach ($demandCategories as $cat): ?>
                                <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Bairro (Vitória/ES) *</label>
                        <select name="neighborhood_id" id="demand_neighborhood" class="form-select" onchange="syncCoordsFromNeighborhood(this)" required>
                            <?php foreach ($neighborhoods as $n): ?>
                                <option value="<?= (int)$n['id'] ?>" data-lat="<?= e($n['lat']) ?>" data-lng="<?= e($n['lng']) ?>">
                                    <?= e($n['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Data do Registro *</label>
                        <input type="date" name="demand_date" id="demand_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Endereço ou Localização de Referência *</label>
                        <input type="text" name="address_location" id="demand_address" class="form-control" placeholder="Ex: Av. Maruípe, próx. ao nº 1500" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Latitude (WGS84) *</label>
                        <input type="text" name="lat" id="demand_lat" class="form-control" value="<?= e($_GET['new_lat'] ?? '-20.319700') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Longitude (WGS84) *</label>
                        <input type="text" name="lng" id="demand_lng" class="form-control" value="<?= e($_GET['new_lng'] ?? '-40.338500') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Prioridade Administrativa *</label>
                        <select name="priority" id="demand_priority" class="form-select" required>
                            <?php foreach ($demandPriorities as $pr): ?>
                                <option value="<?= e($pr) ?>" <?= $pr === 'Média' ? 'selected' : '' ?>><?= e($pr) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Status *</label>
                        <select name="status" id="demand_status" class="form-select" required>
                            <?php foreach ($demandStatuses as $st): ?>
                                <option value="<?= e($st) ?>"><?= e($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Responsável *</label>
                        <input type="text" name="responsible_name" id="demand_responsible" class="form-control" value="<?= e(\App\Core\Auth::user()['name'] ?? 'Coordenação de Zeladoria') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Protocolo Externo (156/Ofício)</label>
                        <input type="text" name="external_reference" id="demand_ext_ref" class="form-control" placeholder="Ex: 156-PMV-95001">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Descrição Técnica Detalhada *</label>
                        <textarea name="description" id="demand_description" rows="3" class="form-control" required></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Salvar Demanda</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Histórico de Demanda -->
<div class="modal fade" id="modalDemandHistory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #101B33; color: #fff;">
                <h5 class="modal-title fw-bold"><i class="bi bi-clock-history me-2"></i>Histórico da Demanda <span id="histDemandProtocol"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 bg-light rounded border mb-3 small" id="histDemandInfo"></div>
                <h6 class="fw-bold small text-uppercase text-secondary">Linha do Tempo de Tramitação</h6>
                <div id="histDemandList" class="mb-4" style="max-height: 220px; overflow-y: auto;"></div>

                <hr>
                <h6 class="fw-bold small text-uppercase text-primary">Adicionar Despacho / Atualizar Status</h6>
                <form method="POST" action="index.php?route=demand_history_add" class="row g-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="demand_id" id="hist_demand_id" value="">
                    <div class="col-md-4">
                        <select name="new_status" id="hist_new_status" class="form-select form-select-sm" required>
                            <?php foreach ($demandStatuses as $st): ?>
                                <option value="<?= e($st) ?>"><?= e($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Informe a providência, número de ofício ou vistoria..." required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-success w-100">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function syncCoordsFromNeighborhood(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    if (opt && opt.dataset.lat && opt.dataset.lng) {
        document.getElementById('demand_lat').value = opt.dataset.lat;
        document.getElementById('demand_lng').value = opt.dataset.lng;
    }
}

function openNewDemandModal() {
    document.getElementById('modalDemandTitle').textContent = 'Registrar Nova Demanda Municipal';
    document.getElementById('demand_id').value = '';
    document.getElementById('demand_title').value = '';
    document.getElementById('demand_address').value = '';
    document.getElementById('demand_description').value = '';
    document.getElementById('demand_ext_ref').value = '';
}

function openEditDemandModal(d) {
    document.getElementById('modalDemandTitle').textContent = 'Editar Demanda ' + d.protocol_code;
    document.getElementById('demand_id').value = d.id;
    document.getElementById('demand_protocol').value = d.protocol_code;
    document.getElementById('demand_title').value = d.title;
    document.getElementById('demand_category').value = d.category;
    document.getElementById('demand_neighborhood').value = d.neighborhood_id;
    document.getElementById('demand_date').value = d.demand_date;
    document.getElementById('demand_address').value = d.address_location;
    document.getElementById('demand_lat').value = d.lat;
    document.getElementById('demand_lng').value = d.lng;
    document.getElementById('demand_priority').value = d.priority;
    document.getElementById('demand_status').value = d.status;
    document.getElementById('demand_responsible').value = d.responsible_name;
    document.getElementById('demand_ext_ref').value = d.external_reference || '';
    document.getElementById('demand_description').value = d.description;
    new bootstrap.Modal(document.getElementById('modalDemandForm')).show();
}

function openDemandHistoryModal(d, historyItems) {
    document.getElementById('histDemandProtocol').textContent = d.protocol_code;
    document.getElementById('hist_demand_id').value = d.id;
    document.getElementById('hist_new_status').value = d.status;
    document.getElementById('histDemandInfo').innerHTML =
        `<strong>${d.title}</strong><br>${d.description}<br>` +
        `<span class="text-muted">Local: ${d.address_location} (${d.neighborhood_name}) | Coordenadas: ${d.lat}, ${d.lng}</span>`;

    const listEl = document.getElementById('histDemandList');
    if (!historyItems || historyItems.length === 0) {
        listEl.innerHTML = '<div class="text-muted small">Nenhuma movimentação registrada ainda.</div>';
    } else {
        let html = '<ul class="list-group list-group-flush small">';
        historyItems.forEach(h => {
            html += `<li class="list-group-item px-0">
                <div class="d-flex justify-content-between">
                    <span><span class="badge bg-secondary">${h.previous_status || 'Início'}</span> &rarr; <span class="badge bg-primary">${h.new_status}</span></span>
                    <span class="text-muted">${h.created_at} (${h.user_name})</span>
                </div>
                <div class="mt-1">${h.notes}</div>
            </li>`;
        });
        html += '</ul>';
        listEl.innerHTML = html;
    }
    new bootstrap.Modal(document.getElementById('modalDemandHistory')).show();
}

<?php if (!empty($_GET['new_lat']) && !empty($_GET['new_lng'])): ?>
document.addEventListener('DOMContentLoaded', function () {
    <?php if (!empty($_GET['neighborhood_id'])): ?>
    document.getElementById('demand_neighborhood').value = '<?= (int)$_GET['neighborhood_id'] ?>';
    <?php endif; ?>
    new bootstrap.Modal(document.getElementById('modalDemandForm')).show();
});
<?php endif; ?>
</script>
