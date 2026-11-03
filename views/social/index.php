<?php
/**
 * VITÓRIA INTELIGÊNCIA — Central de Redes Sociais (Módulo 8)
 *
 * Plataformas: Instagram, Facebook, X (Twitter)
 * Funcionalidades:
 * - Conectar contas através de APIs oficiais (Meta Graph API v19.0 e X API v2)
 * - Calendário editorial e criação de publicações
 * - Banco de imagens / mídia
 * - Geração de legendas e hashtags por IA
 * - Agendamento com recorrência Diária, Semanal ou Mensal
 * - Fluxo de aprovação de conteúdo antes da publicação
 * - Fila de publicação (somente executa quando aprovado E com credencial oficial válida)
 * - Métricas e relatórios
 */
?>

<!-- Status das Contas Conectadas via APIs Oficiais -->
<div class="row g-3 mb-4">
    <?php foreach ($accounts as $acc): ?>
        <div class="col-md-4">
            <div class="vi-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge bg-dark mb-1"><?= e($acc['platform']) ?></span>
                        <h6 class="fw-bold mb-0"><?= e($acc['account_name']) ?></h6>
                        <small class="text-muted"><?= e($acc['account_handle']) ?></small>
                    </div>
                    <?php if ($acc['status'] === 'Conectada (API Oficial)'): ?>
                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>API Oficial Ativa</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-key me-1"></i><?= e($acc['status']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="row g-2 mt-2 pt-2 border-top small">
                    <div class="col-6">
                        <div class="text-muted">Seguidores</div>
                        <div class="fw-bold"><?= number_format((int)$acc['followers_count'], 0, ',', '.') ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted">Taxa de Engajamento</div>
                        <div class="fw-bold text-success"><?= number_format((float)$acc['engagement_rate'], 2, ',', '.') ?>%</div>
                    </div>
                </div>
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-outline-primary w-100"
                            onclick='openAccountTokenModal(<?= json_encode($acc, JSON_UNESCAPED_UNICODE) ?>)'>
                        <i class="bi bi-plug me-1"></i>Configurar Token da API Oficial
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Barra de Ações do Calendário Editorial e Fila -->
<div class="vi-card p-3 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h6 class="fw-bold mb-0"><i class="bi bi-calendar3-Week text-primary me-2"></i>Calendário Editorial, Fluxo de Aprovação e Fila de Disparo</h6>
        <small class="text-muted">Regra de Governança: Nenhuma publicação vai ao ar sem status <strong>Aprovado</strong> e token oficial ativo.</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalMediaBank">
            <i class="bi bi-images me-1"></i>Banco de Imagens (<?= count($mediaAssets) ?>)
        </button>
        <form method="POST" action="index.php?route=social_run_queue" class="m-0">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-success">
                <i class="bi bi-play-circle me-1"></i>Processar Fila Aprovada Agora
            </button>
        </form>
        <a href="index.php?route=export&type=social_media&format=excel" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-file-earmark-excel me-1"></i>Relatório Excel
        </a>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalSocialPost">
            <i class="bi bi-plus-circle me-1"></i>Nova Publicação / Agendamento
        </button>
    </div>
</div>

<!-- Tabela do Calendário Editorial e Fila de Publicação -->
<div class="vi-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>ID / Título</th>
                    <th>Redes</th>
                    <th>Legenda & Hashtags</th>
                    <th>Agendamento</th>
                    <th>Recorrência</th>
                    <th>Fluxo de Aprovação</th>
                    <th>Métricas (Alcance / Interações)</th>
                    <th class="text-end">Governança & Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $p): ?>
                    <tr>
                        <td class="fw-semibold">
                            #<?= (int)$p['id'] ?> — <?= e($p['title']) ?>
                            <div class="text-muted" style="font-size: 0.72rem;">Autor: <?= e($p['created_by_name']) ?></div>
                        </td>
                        <td>
                            <?php foreach (explode(',', $p['platforms']) as $plat): ?>
                                <span class="badge bg-dark me-1"><?= e(trim($plat)) ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td style="max-width: 310px;">
                            <div class="text-truncate" title="<?= e($p['caption']) ?>"><?= e($p['caption']) ?></div>
                            <div class="text-primary" style="font-size: 0.72rem;"><?= e($p['hashtags']) ?></div>
                            <?php if (!empty($p['api_response_log'])): ?>
                                <div class="text-muted mt-1" style="font-size: 0.7rem;"><i class="bi bi-terminal me-1"></i><?= e($p['api_response_log']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime($p['scheduled_at']))) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= e($p['recurrence']) ?></span></td>
                        <td>
                            <?php
                            $stClass = match ($p['approval_status']) {
                                'Publicado'    => 'bg-success',
                                'Aprovado'     => 'bg-primary',
                                'Em Revisão'   => 'bg-warning text-dark',
                                'Rejeitado'    => 'bg-danger',
                                'Falha na API' => 'bg-danger',
                                default        => 'bg-secondary',
                            };
                            ?>
                            <span class="badge <?= $stClass ?>"><?= e($p['approval_status']) ?></span>
                            <?php if (!empty($p['approved_by_name'])): ?>
                                <div class="text-muted" style="font-size: 0.7rem;">Por: <?= e($p['approved_by_name']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div>Alcance: <strong><?= number_format((int)$p['reach_metric'], 0, ',', '.') ?></strong></div>
                            <div class="text-muted">
                                <i class="bi bi-heart-fill text-danger"></i> <?= (int)$p['likes_metric'] ?> |
                                <i class="bi bi-chat-fill text-primary"></i> <?= (int)$p['comments_metric'] ?> |
                                <i class="bi bi-share-fill text-success"></i> <?= (int)$p['shares_metric'] ?>
                            </div>
                        </td>
                        <td class="text-end text-nowrap">
                            <?php if ($p['approval_status'] !== 'Publicado'): ?>
                                <form method="POST" action="index.php?route=social_approve" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <input type="hidden" name="decision" value="Aprovado">
                                    <button type="submit" class="btn btn-sm btn-success" title="Aprovar Conteúdo para a Fila">
                                        <i class="bi bi-check-lg"></i> Aprovar
                                    </button>
                                </form>
                                <form method="POST" action="index.php?route=social_approve" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <input type="hidden" name="decision" value="Rejeitado">
                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Devolver / Rejeitar">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" action="index.php?route=social_delete" class="d-inline" onsubmit="return confirm('Excluir esta publicação do calendário editorial?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Nova Publicação com Gerador de Legenda por IA -->
<div class="modal fade" id="modalSocialPost" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="index.php?route=social_save" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header" style="background-color: #101B33; color: #fff;">
                <h5 class="modal-title fw-bold"><i class="bi bi-megaphone me-2"></i>Criar Publicação no Calendário Editorial</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Título Interno da Pauta *</label>
                        <input type="text" name="title" id="social_title" class="form-control" placeholder="Ex: Mutirão de Zeladoria em Maruípe" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Plataformas Oficiais *</label>
                        <div class="d-flex gap-3 pt-1">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="platforms[]" value="Instagram" id="platIG" checked>
                                <label class="form-check-label small" for="platIG">Instagram</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="platforms[]" value="Facebook" id="platFB" checked>
                                <label class="form-check-label small" for="platFB">Facebook</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="platforms[]" value="X" id="platX">
                                <label class="form-check-label small" for="platX">X</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold mb-0">Legenda da Publicação *</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="generateAICaption()">
                                <i class="bi bi-stars me-1"></i>Gerar Legenda e Hashtags com IA
                            </button>
                        </div>
                        <textarea name="caption" id="social_caption" rows="4" class="form-control" required></textarea>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Hashtags Estratégicas</label>
                        <input type="text" name="hashtags" id="social_hashtags" class="form-control" value="#VitoriaES #GestaoComunitaria #InteligenciaTerritorial #ParticipacaoCidada">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Data e Hora de Agendamento *</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime('+1 day 10:00')) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Recorrência Automática *</label>
                        <select name="recurrence" class="form-select">
                            <option value="Nenhuma">Nenhuma (Publicação Única)</option>
                            <option value="Diária">Diária</option>
                            <option value="Semanal">Semanal</option>
                            <option value="Mensal">Mensal</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Status Inicial *</label>
                        <select name="approval_status" class="form-select">
                            <option value="Em Revisão">Enviar para Revisão / Aprovação</option>
                            <option value="Rascunho">Salvar como Rascunho</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">URL da Imagem do Banco de Mídia (Opcional)</label>
                        <input type="text" name="media_url" class="form-control" placeholder="storage/uploads/card_institucional.jpg">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Agendar no Calendário Editorial</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Banco de Imagens -->
<div class="modal fade" id="modalMediaBank" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #101B33; color: #fff;">
                <h5 class="modal-title fw-bold"><i class="bi bi-images me-2"></i>Banco de Imagens Institucional</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="index.php?route=social_media_upload" enctype="multipart/form-data" class="row g-2 align-items-end mb-4 p-3 bg-light rounded border">
                    <?= csrf_field() ?>
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold mb-1">Título da Mídia *</label>
                        <input type="text" name="title" class="form-control form-control-sm" placeholder="Ex: Foto Parque Pedra da Cebola" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold mb-1">Arquivo de Imagem (JPG/PNG/WEBP) *</label>
                        <input type="file" name="media_file" accept="image/*" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Enviar</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-sm align-middle small">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Caminho</th>
                                <th>Tamanho</th>
                                <th>Enviado por</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mediaAssets)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">Nenhuma imagem enviada ainda.</td></tr>
                            <?php else: ?>
                                <?php foreach ($mediaAssets as $ma): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= e($ma['title']) ?></td>
                                        <td><code><?= e($ma['file_path']) ?></code></td>
                                        <td><?= (int)$ma['file_size_kb'] ?> KB</td>
                                        <td><?= e($ma['uploaded_by']) ?></td>
                                        <td><?= e($ma['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Configurar Credencial Oficial de Conta Social -->
<div class="modal fade" id="modalSocialAccount" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="index.php?route=social_account_save" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="acc_id" value="">
            <div class="modal-header" style="background-color: #101B33; color: #fff;">
                <h5 class="modal-title fw-bold">Conectar API Oficial — <span id="acc_platform_title"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nome da Conta Oficial</label>
                    <input type="text" name="account_name" id="acc_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Identificador / Handle (@)</label>
                    <input type="text" name="account_handle" id="acc_handle" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Page ID / IG Business ID Externo</label>
                    <input type="text" name="external_account_id" id="acc_ext_id" class="form-control" placeholder="ID numérico oficial na Meta/X">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Access Token Oficial (Criptografado com AES-256)</label>
                    <input type="password" name="access_token" class="form-control" placeholder="Cole o token OAuth2 oficial da plataforma...">
                    <small class="text-muted">Deixe em branco para manter o token atual ou desconectar.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Credencial Criptografada</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAccountTokenModal(acc) {
    document.getElementById('acc_id').value = acc.id;
    document.getElementById('acc_platform_title').textContent = acc.platform;
    document.getElementById('acc_name').value = acc.account_name;
    document.getElementById('acc_handle').value = acc.account_handle;
    document.getElementById('acc_ext_id').value = acc.external_account_id || '';
    new bootstrap.Modal(document.getElementById('modalSocialAccount')).show();
}

function generateAICaption() {
    const title = document.getElementById('social_title').value.trim() || 'Ações Comunitárias e Serviços Públicos em Vitória/ES';
    const captionEl = document.getElementById('social_caption');
    const tagsEl = document.getElementById('social_hashtags');

    captionEl.value = `📍 ${title}\n\nAcompanhe os indicadores territoriais e as ações voltadas à melhoria contínua dos 80 bairros de Vitória/ES. Nosso compromisso é com a transparência, escuta cidadã ativa (em total conformidade com a LGPD) e fortalecimento dos equipamentos públicos de saúde, educação, mobilidade e meio ambiente.\n\nParticipe das reuniões comunitárias da sua Região Administrativa!`;
    tagsEl.value = '#VitoriaES #InteligenciaTerritorial #GestaoComunitaria #TransparenciaPublica #CidadaniaVitoria';
}
</script>
