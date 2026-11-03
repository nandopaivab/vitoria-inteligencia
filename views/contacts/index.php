<?php
/**
 * VITÓRIA INTELIGÊNCIA — Gestão Comunitária & Conformidade LGPD (Módulo 5)
 *
 * Campos obrigatórios:
 * - Nome, Telefone, E-mail, Bairro, Data do cadastro, Histórico de relacionamento,
 *   Consentimento, Preferência de comunicação, Responsável pelo atendimento.
 *
 * Operações:
 * - Cadastrar, Editar, Excluir, Pesquisar, Filtrar, Exportar (PDF/Excel), Consultar/Registrar histórico.
 *
 * Restrição Legal Estrita:
 * - Não coleta intenção de voto, preferência partidária, ideologia ou perfil político individual.
 * - Não utiliza endereço residencial para classificação ou previsão de comportamento eleitoral.
 */
?>

<div class="alert alert-primary border-0 shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-shield-lock-fill fs-4 text-primary"></i>
        <div class="small">
            <strong>Diretriz de Proteção de Dados (LGPD — Lei nº 13.709/2018):</strong>
            Este módulo registra exclusivamente voluntários, colaboradores e participantes de atividades comunitárias com termo de consentimento verificável. É expressamente vedado o registro de intenção de voto, filiação/preferência partidária, ideologia ou classificação eleitoral.
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?route=export&type=community_activities&format=excel" class="btn btn-sm btn-outline-success bg-white">
            <i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel
        </a>
        <a href="index.php?route=export&type=community_activities&format=pdf" class="btn btn-sm btn-outline-danger bg-white">
            <i class="bi bi-file-earmark-pdf me-1"></i>Exportar PDF
        </a>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalContactForm" onclick="openNewContactModal()">
            <i class="bi bi-person-plus-fill me-1"></i>Novo Contato Comunitário
        </button>
    </div>
</div>

<!-- Filtros e Pesquisa -->
<div class="vi-card p-3 mb-4">
    <form method="GET" action="index.php" class="row g-2 align-items-end">
        <input type="hidden" name="route" value="contacts">
        <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Pesquisar por Nome, E-mail ou Telefone</label>
            <input type="text" name="q" class="form-control form-control-sm" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Digite para pesquisar...">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Bairro de Vitória</label>
            <select name="neighborhood_id" class="form-select form-select-sm">
                <option value="">Todos os 80 Bairros</option>
                <?php foreach ($neighborhoods as $n): ?>
                    <option value="<?= (int)$n['id'] ?>" <?= (isset($_GET['neighborhood_id']) && (int)$_GET['neighborhood_id'] === (int)$n['id']) ? 'selected' : '' ?>>
                        <?= e($n['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Perfil Comunitário</label>
            <select name="profile_type" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach (['Voluntário', 'Colaborador', 'Participante de Atividade', 'Liderança Comunitária'] as $pt): ?>
                    <option value="<?= e($pt) ?>" <?= (($_GET['profile_type'] ?? '') === $pt) ? 'selected' : '' ?>><?= e($pt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Filtrar</button>
            <a href="index.php?route=contacts" class="btn btn-sm btn-outline-secondary">Limpar</a>
        </div>
    </form>
</div>

<!-- Tabela de Contatos Cadastrados -->
<div class="vi-card">
    <div class="vi-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-people-fill text-primary me-2"></i>Relação de Contatos, Voluntários e Colaboradores (<?= count($contacts) ?>)</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Nome</th>
                    <th>Perfil</th>
                    <th>Telefone / E-mail</th>
                    <th>Bairro</th>
                    <th>Data Cadastro</th>
                    <th>Consentimento LGPD</th>
                    <th>Preferência</th>
                    <th>Responsável</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contacts)): ?>
                    <tr><td colspan="9" class="text-center py-4 text-muted">Nenhum contato encontrado para os filtros informados.</td></tr>
                <?php else: ?>
                    <?php foreach ($contacts as $c): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($c['name']) ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;"><?= e(mb_substr((string)$c['relationship_summary'], 0, 75)) ?></div>
                            </td>
                            <td>
                                <span class="badge <?= $c['profile_type'] === 'Voluntário' ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary' ?>">
                                    <?= e($c['profile_type']) ?>
                                </span>
                            </td>
                            <td>
                                <div><i class="bi bi-telephone me-1 text-muted"></i><?= e($c['phone']) ?></div>
                                <?php if (!empty($c['email'])): ?>
                                    <div class="text-muted"><i class="bi bi-envelope me-1"></i><?= e($c['email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="fw-semibold"><?= e($c['neighborhood_name'] ?? 'Vitória') ?></td>
                            <td><?= e(date('d/m/Y', strtotime($c['registration_date']))) ?></td>
                            <td>
                                <?php if ((int)$c['consent_lgpd'] === 1): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Consentimento Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Revogado</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= e($c['communication_pref']) ?></span></td>
                            <td><?= e($c['responsible_name']) ?></td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-info"
                                        onclick='openHistoryModal(<?= json_encode($c, JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($interactionsByContact[$c["id"]] ?? [], JSON_UNESCAPED_UNICODE) ?>)'
                                        title="Consultar Histórico de Relacionamento">
                                    <i class="bi bi-clock-history"></i> Histórico (<?= count($interactionsByContact[$c['id']] ?? []) ?>)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        onclick='openEditContactModal(<?= json_encode($c, JSON_UNESCAPED_UNICODE) ?>)'
                                        title="Editar Cadastro">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="index.php?route=contact_delete" class="d-inline" onsubmit="return confirm('Confirma a exclusão definitiva deste registro conforme direito de eliminação LGPD?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir Registro (LGPD)">
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

<!-- Modal Criar / Editar Contato -->
<div class="modal fade" id="modalContactForm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="index.php?route=contact_save" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="contact_id" value="">
            <div class="modal-header" style="background-color: #101B33; color: #fff;">
                <h5 class="modal-title fw-bold" id="modalContactTitle">Novo Cadastro Comunitário (LGPD)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Nome Completo *</label>
                        <input type="text" name="name" id="contact_name" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Telefone / WhatsApp *</label>
                        <input type="text" name="phone" id="contact_phone" class="form-control" placeholder="(27) 99999-0000" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Perfil *</label>
                        <select name="profile_type" id="contact_profile" class="form-select" required>
                            <option value="Voluntário">Voluntário</option>
                            <option value="Colaborador">Colaborador</option>
                            <option value="Participante de Atividade">Participante de Atividade</option>
                            <option value="Liderança Comunitária">Liderança Comunitária</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold">E-mail</label>
                        <input type="email" name="email" id="contact_email" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Bairro (Vitória/ES) *</label>
                        <select name="neighborhood_id" id="contact_neighborhood" class="form-select" required>
                            <?php foreach ($neighborhoods as $n): ?>
                                <option value="<?= (int)$n['id'] ?>"><?= e($n['name']) ?> (<?= e($n['region_name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Data do Cadastro *</label>
                        <input type="date" name="registration_date" id="contact_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Preferência de Comunicação *</label>
                        <select name="communication_pref" id="contact_pref" class="form-select" required>
                            <option value="WhatsApp">WhatsApp</option>
                            <option value="E-mail">E-mail</option>
                            <option value="Telefone">Telefone</option>
                            <option value="SMS">SMS</option>
                            <option value="Nenhum">Nenhum</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Responsável pelo Atendimento *</label>
                        <input type="text" name="responsible_name" id="contact_responsible" class="form-control" value="<?= e(\App\Core\Auth::user()['name'] ?? 'Coordenação Territorial') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Status de Consentimento LGPD *</label>
                        <select name="consent_lgpd" id="contact_consent" class="form-select" required>
                            <option value="1">Sim — Consentimento Ativo Verificado</option>
                            <option value="0">Não / Consentimento Revogado</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Síntese do Histórico de Relacionamento Comunitário</label>
                        <textarea name="relationship_summary" id="contact_summary" rows="3" class="form-control" placeholder="Descreva a atuação comunitária, participação em reuniões de bairro ou projetos sociais (proibido inserir preferência política/eleitoral)..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Salvar Cadastro</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Consultar / Adicionar Histórico de Relacionamento -->
<div class="modal fade" id="modalContactHistory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #101B33; color: #fff;">
                <h5 class="modal-title fw-bold"><i class="bi bi-clock-history me-2"></i>Histórico de Relacionamento — <span id="histContactName"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 bg-light rounded border mb-3 small" id="histContactSummary"></div>

                <h6 class="fw-bold small text-uppercase text-secondary">Interações Registradas</h6>
                <div id="histInteractionsList" class="mb-4" style="max-height: 240px; overflow-y: auto;"></div>

                <hr>
                <h6 class="fw-bold small text-uppercase text-primary">Registrar Nova Interação / Atendimento</h6>
                <form method="POST" action="index.php?route=contact_interaction_add" class="row g-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="contact_id" id="hist_contact_id" value="">
                    <div class="col-md-4">
                        <select name="interaction_type" class="form-select form-select-sm" required>
                            <option value="Atendimento Presencial">Atendimento Presencial</option>
                            <option value="Contato Telefônico">Contato Telefônico</option>
                            <option value="Mensagem / Informativo">Mensagem / Informativo</option>
                            <option value="Participação em Reunião">Participação em Reunião</option>
                            <option value="Participação em Evento">Participação em Evento</option>
                            <option value="Atualização de Consentimento LGPD">Atualização de Consentimento LGPD</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="description" class="form-control form-control-sm" placeholder="Descreva o atendimento ou participação comunitária..." required>
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
function openNewContactModal() {
    document.getElementById('modalContactTitle').textContent = 'Novo Cadastro Comunitário (LGPD)';
    document.getElementById('contact_id').value = '';
    document.getElementById('contact_name').value = '';
    document.getElementById('contact_phone').value = '';
    document.getElementById('contact_email').value = '';
    document.getElementById('contact_profile').value = 'Voluntário';
    document.getElementById('contact_date').value = '<?= date('Y-m-d') ?>';
    document.getElementById('contact_pref').value = 'WhatsApp';
    document.getElementById('contact_consent').value = '1';
    document.getElementById('contact_summary').value = '';
}

function openEditContactModal(c) {
    document.getElementById('modalContactTitle').textContent = 'Editar Cadastro Comunitário #' + c.id;
    document.getElementById('contact_id').value = c.id;
    document.getElementById('contact_name').value = c.name;
    document.getElementById('contact_phone').value = c.phone;
    document.getElementById('contact_email').value = c.email || '';
    document.getElementById('contact_profile').value = c.profile_type;
    document.getElementById('contact_neighborhood').value = c.neighborhood_id;
    document.getElementById('contact_date').value = c.registration_date;
    document.getElementById('contact_pref').value = c.communication_pref;
    document.getElementById('contact_responsible').value = c.responsible_name;
    document.getElementById('contact_consent').value = c.consent_lgpd;
    document.getElementById('contact_summary').value = c.relationship_summary || '';
    new bootstrap.Modal(document.getElementById('modalContactForm')).show();
}

function openHistoryModal(c, interactions) {
    document.getElementById('histContactName').textContent = c.name;
    document.getElementById('hist_contact_id').value = c.id;
    document.getElementById('histContactSummary').innerHTML =
        `<strong>Bairro:</strong> ${c.neighborhood_name || ''} | <strong>Canal Preferido:</strong> ${c.communication_pref} | <strong>Finalidade LGPD:</strong> ${c.consent_purpose}<br>` +
        `<strong>Síntese:</strong> ${c.relationship_summary || 'Sem observações adicionais.'}`;

    const listEl = document.getElementById('histInteractionsList');
    if (!interactions || interactions.length === 0) {
        listEl.innerHTML = '<div class="text-muted small">Nenhuma interação individual registrada ainda.</div>';
    } else {
        let html = '<ul class="list-group list-group-flush small">';
        interactions.forEach(item => {
            html += `<li class="list-group-item px-0">
                <div class="d-flex justify-content-between">
                    <span class="badge bg-primary-subtle text-primary">${item.interaction_type}</span>
                    <span class="text-muted">${item.interaction_date} — Resp: ${item.user_name}</span>
                </div>
                <div class="mt-1">${item.description}</div>
            </li>`;
        });
        html += '</ul>';
        listEl.innerHTML = html;
    }
    new bootstrap.Modal(document.getElementById('modalContactHistory')).show();
}
</script>
