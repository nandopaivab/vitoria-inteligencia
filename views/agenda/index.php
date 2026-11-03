<?php
/**
 * VITÓRIA INTELIGÊNCIA — Agenda Municipal e Comunitária (Módulo 10)
 *
 * Itens Obrigatórios:
 * - Eventos, Reuniões, Audiências Públicas, Compromissos Institucionais, Atividades Comunitárias
 * - Participantes, Responsáveis, Endereços, Bairro, Público Esperado/Presente e Status
 */

$eventTypes = [
    'Evento',
    'Reunião',
    'Audiência Pública',
    'Compromisso Institucional',
    'Atividade Comunitária',
];
$eventStatuses = ['Agendado', 'Confirmado', 'Realizado', 'Cancelado'];
?>

<div class="vi-card p-3 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h6 class="fw-bold mb-0"><i class="bi bi-calendar-event text-primary me-2"></i>Gestão de Agenda, Reuniões Comunitárias e Audiências Públicas</h6>
        <small class="text-muted">Controle completo de compromissos territoriais, responsáveis, endereços e lista de participantes</small>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?route=export&type=event_attendance&format=pdf" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i>PDF Participação
        </a>
        <a href="index.php?route=export&type=event_attendance&format=excel" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i>Excel Agenda
        </a>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgendaForm" onclick="openNewAgendaModal()">
            <i class="bi bi-calendar-plus me-1"></i>Novo Evento / Reunião
        </button>
    </div>
</div>

<!-- Filtros da Agenda -->
<div class="vi-card p-3 mb-4">
    <form method="GET" action="index.php" class="row g-2 align-items-end">
        <input type="hidden" name="route" value="agenda">
        <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Tipo de Compromisso</label>
            <select name="event_type" class="form-select form-select-sm">
                <option value="">Todos os Tipos</option>
                <?php foreach ($eventTypes as $et): ?>
                    <option value="<?= e($et) ?>" <?= (($_GET['event_type'] ?? '') === $et) ? 'selected' : '' ?>><?= e($et) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Todos os Status</option>
                <?php foreach ($eventStatuses as $st): ?>
                    <option value="<?= e($st) ?>" <?= (($_GET['status'] ?? '') === $st) ? 'selected' : '' ?>><?= e($st) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Filtrar Agenda</button>
            <a href="index.php?route=agenda" class="btn btn-sm btn-outline-secondary">Limpar</a>
        </div>
    </form>
</div>

<!-- Lista de Eventos e Reuniões -->
<div class="vi-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Data & Horário</th>
                    <th>Tipo</th>
                    <th>Título & Descrição</th>
                    <th>Endereço & Bairro</th>
                    <th>Responsável & Participantes</th>
                    <th>Presença</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($events)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">Nenhum evento ou reunião encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($events as $ev): ?>
                        <tr>
                            <td class="text-nowrap">
                                <div class="fw-bold"><?= e(date('d/m/Y', strtotime($ev['start_datetime']))) ?></div>
                                <div class="text-muted"><?= e(date('H:i', strtotime($ev['start_datetime']))) ?> às <?= e(date('H:i', strtotime($ev['end_datetime']))) ?></div>
                            </td>
                            <td><span class="badge bg-primary-subtle text-primary"><?= e($ev['event_type']) ?></span></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($ev['title']) ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;"><?= e($ev['description']) ?></div>
                            </td>
                            <td>
                                <div><i class="bi bi-geo-alt me-1 text-primary"></i><?= e($ev['address']) ?></div>
                                <span class="badge bg-light text-dark border"><?= e($ev['neighborhood_name'] ?? 'Vitória/ES') ?></span>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= e($ev['responsible_name']) ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;">Participantes: <?= e($ev['participants_list'] ?: 'Aberto à comunidade') ?></div>
                            </td>
                            <td>
                                <div>Esp: <?= (int)$ev['expected_attendance'] ?></div>
                                <div class="fw-semibold text-success">Pres: <?= (int)$ev['actual_attendance'] ?></div>
                            </td>
                            <td>
                                <?php
                                $evClass = match ($ev['status']) {
                                    'Realizado'  => 'bg-success',
                                    'Confirmado' => 'bg-primary',
                                    'Cancelado'  => 'bg-danger',
                                    default      => 'bg-warning text-dark',
                                };
                                ?>
                                <span class="badge <?= $evClass ?>"><?= e($ev['status']) ?></span>
                            </td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        onclick='openEditAgendaModal(<?= json_encode($ev, JSON_UNESCAPED_UNICODE) ?>)'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="index.php?route=agenda_delete" class="d-inline" onsubmit="return confirm('Excluir este compromisso da agenda?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Cadastrar / Editar Agenda -->
<div class="modal fade" id="modalAgendaForm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="index.php?route=agenda_save" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="agenda_id" value="">
            <div class="modal-header" style="background-color: #101B33; color: #fff;">
                <h5 class="modal-title fw-bold" id="modalAgendaTitle">Agendar Compromisso / Atividade</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Título do Evento / Reunião *</label>
                        <input type="text" name="title" id="agenda_title" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Tipo *</label>
                        <select name="event_type" id="agenda_type" class="form-select" required>
                            <?php foreach ($eventTypes as $et): ?>
                                <option value="<?= e($et) ?>"><?= e($et) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Início *</label>
                        <input type="datetime-local" name="start_datetime" id="agenda_start" class="form-control" value="<?= date('Y-m-d\T19:00') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Término *</label>
                        <input type="datetime-local" name="end_datetime" id="agenda_end" class="form-control" value="<?= date('Y-m-d\T21:00') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Bairro (Vitória/ES)</label>
                        <select name="neighborhood_id" id="agenda_neighborhood" class="form-select">
                            <option value="">Vitória (Geral)</option>
                            <?php foreach ($neighborhoods as $n): ?>
                                <option value="<?= (int)$n['id'] ?>"><?= e($n['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Endereço Completo / Local *</label>
                        <input type="text" name="address" id="agenda_address" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Responsável pela Coordenação *</label>
                        <input type="text" name="responsible_name" id="agenda_responsible" class="form-control" value="<?= e(\App\Core\Auth::user()['name'] ?? 'Coordenação Territorial') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Público Esperado</label>
                        <input type="number" name="expected_attendance" id="agenda_expected" class="form-control" value="30" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Público Presente Confirmado</label>
                        <input type="number" name="actual_attendance" id="agenda_actual" class="form-control" value="0" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Status *</label>
                        <select name="status" id="agenda_status" class="form-select" required>
                            <?php foreach ($eventStatuses as $st): ?>
                                <option value="<?= e($st) ?>"><?= e($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Participantes / Entidades Convidadas</label>
                        <input type="text" name="participants_list" id="agenda_participants" class="form-control" placeholder="Ex: Associação de Moradores, Lideranças Comunitárias, Equipe Técnica">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Pauta / Descrição da Atividade</label>
                        <textarea name="description" id="agenda_description" rows="2" class="form-control"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar na Agenda</button>
            </div>
        </form>
    </div>
</div>

<script>
function openNewAgendaModal() {
    document.getElementById('modalAgendaTitle').textContent = 'Novo Compromisso na Agenda';
    document.getElementById('agenda_id').value = '';
    document.getElementById('agenda_title').value = '';
    document.getElementById('agenda_address').value = '';
    document.getElementById('agenda_participants').value = '';
    document.getElementById('agenda_description').value = '';
}

function openEditAgendaModal(ev) {
    document.getElementById('modalAgendaTitle').textContent = 'Editar Compromisso #' + ev.id;
    document.getElementById('agenda_id').value = ev.id;
    document.getElementById('agenda_title').value = ev.title;
    document.getElementById('agenda_type').value = ev.event_type;
    document.getElementById('agenda_start').value = ev.start_datetime.replace(' ', 'T').substring(0, 16);
    document.getElementById('agenda_end').value = ev.end_datetime.replace(' ', 'T').substring(0, 16);
    document.getElementById('agenda_neighborhood').value = ev.neighborhood_id || '';
    document.getElementById('agenda_address').value = ev.address;
    document.getElementById('agenda_responsible').value = ev.responsible_name;
    document.getElementById('agenda_expected').value = ev.expected_attendance;
    document.getElementById('agenda_actual').value = ev.actual_attendance;
    document.getElementById('agenda_status').value = ev.status;
    document.getElementById('agenda_participants').value = ev.participants_list || '';
    document.getElementById('agenda_description').value = ev.description || '';
    new bootstrap.Modal(document.getElementById('modalAgendaForm')).show();
}
</script>
