<?php
/**
 * VITÓRIA INTELIGÊNCIA — Governança, Segurança, Integrações de APIs, Usuários RBAC, Backups e Logs (Módulo 12)
 */
?>

<div class="row g-4">
    <!-- Configurações Gerais e Credenciais Criptografadas AES-256 -->
    <div class="col-lg-6">
        <div class="vi-card p-4 mb-4">
            <h6 class="fw-bold mb-3 border-bottom pb-2"><i class="bi bi-key-fill text-primary me-2"></i>Credenciais de APIs & Parâmetros Municipais (Criptografia AES-256)</h6>
            <form method="POST" action="index.php?route=settings_save">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nome da Plataforma</label>
                    <input type="text" name="site_name" class="form-control form-control-sm" value="<?= e($settingsMap['site_name'] ?? 'Vitória Inteligência') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">E-mail do Encarregado de Dados (DPO / LGPD)</label>
                    <input type="email" name="lgpd_dpo_email" class="form-control form-control-sm" value="<?= e($settingsMap['lgpd_dpo_email'] ?? 'lgpd@inteligencia.fernandopaiva.com.br') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Chave de API de IA (Suporta <strong>Google Gemini Gratuito</strong> <code>AIza...</code>, <strong>Groq Gratuito</strong> <code>gsk_...</code> ou <strong>OpenAI</strong> <code>sk-...</code>)</label>
                    <input type="password" name="openai_api_key" class="form-control form-control-sm" placeholder="<?= !empty($settingsMap['openai_api_key']) ? '•••••••••••• (Chave configurada — preencha para alterar)' : 'Cole AIza... (Gemini Grátis), gsk_... (Groq Grátis) ou sk-... (OpenAI)' ?>">
                    <div class="form-text small">
                        <i class="bi bi-gift-fill text-success me-1"></i><strong>Opções 100% Gratuitas:</strong> Gere uma chave grátis em <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener">Google AI Studio (Gemini)</a> ou <a href="https://console.groq.com/keys" target="_blank" rel="noopener">Groq Cloud</a> e cole acima!
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Modelo de IA Padrão</label>
                    <select name="openai_model" class="form-select form-select-sm">
                        <?php
                        $modelsList = [
                            'gemini-2.0-flash'        => 'Google Gemini 2.0 Flash (100% GRATUITO com chave AIza...)',
                            'gemini-1.5-flash'        => 'Google Gemini 1.5 Flash (100% GRATUITO com chave AIza...)',
                            'llama-3.3-70b-versatile' => 'Llama 3.3 70B via Groq (100% GRATUITO com chave gsk_...)',
                            'gpt-4o-mini'             => 'OpenAI GPT-4o Mini (Requer créditos na OpenAI)',
                            'gpt-4o'                  => 'OpenAI GPT-4o (Requer créditos na OpenAI)',
                        ];
                        foreach ($modelsList as $mKey => $mLabel):
                        ?>
                            <option value="<?= e($mKey) ?>" <?= (($settingsMap['openai_model'] ?? 'gemini-2.0-flash') === $mKey) ? 'selected' : '' ?>><?= e($mLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Feeds RSS Públicos do Radar Municipal (1 URL por linha)</label>
                    <textarea name="rss_feeds_vitoria" rows="3" class="form-control form-control-sm"><?= e($settingsMap['rss_feeds_vitoria'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-sm btn-primary px-4">
                    <i class="bi bi-shield-lock me-1"></i>Salvar Configurações e Criptografar Chaves
                </button>
            </form>
        </div>

        <!-- Backup SQL do Banco de Dados -->
        <div class="vi-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-database-down text-success me-2"></i>Backups do Banco de Dados (SQL)</h6>
                <form method="POST" action="index.php?route=backup_create" class="m-0">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="bi bi-cloud-arrow-down me-1"></i>Gerar Backup SQL Agora
                    </button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle small mb-0">
                    <thead>
                        <tr>
                            <th>Arquivo SQL</th>
                            <th>Tamanho</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($backups)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-2">Nenhum backup gerado manualmente ainda.</td></tr>
                        <?php else: ?>
                            <?php foreach ($backups as $bk): ?>
                                <tr>
                                    <td><code><?= e($bk['name']) ?></code></td>
                                    <td><?= e($bk['size']) ?></td>
                                    <td><?= e($bk['date']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Controle de Acesso (Usuários RBAC) e Logs de Auditoria -->
    <div class="col-lg-6">
        <div class="vi-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-person-badge-fill text-primary me-2"></i>Controle de Acesso de Usuários (RBAC)</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalUserAdd">
                    <i class="bi bi-person-plus me-1"></i>Novo Usuário
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle small mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Perfil RBAC</th>
                            <th>Último Acesso</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($u['name']) ?></td>
                                <td><?= e($u['email']) ?></td>
                                <td><span class="badge bg-dark text-uppercase"><?= e($u['role']) ?></span></td>
                                <td class="text-muted"><?= e($u['last_login_at'] ?: 'Nunca') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Logs de Auditoria e Execuções Cron -->
        <div class="vi-card p-4">
            <h6 class="fw-bold mb-3 border-bottom pb-2"><i class="bi bi-shield-check text-primary me-2"></i>Logs Recentes de Auditoria, Segurança e Automações Cron</h6>
            <div class="table-responsive" style="max-height: 310px; overflow-y: auto;">
                <table class="table table-sm align-middle small mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Data/Hora</th>
                            <th>Módulo</th>
                            <th>Usuário</th>
                            <th>Ação Auditada</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($auditLogs as $log): ?>
                            <tr>
                                <td class="text-nowrap text-muted"><?= e(date('d/m H:i:s', strtotime($log['created_at']))) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= e($log['module']) ?></span></td>
                                <td><?= e($log['user_name']) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= e($log['action']) ?></div>
                                    <?php if (!empty($log['details'])): ?>
                                        <div class="text-muted" style="font-size: 0.72rem;"><?= e($log['details']) ?></div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo Usuário RBAC -->
<div class="modal fade" id="modalUserAdd" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="index.php?route=user_save" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header" style="background-color: #101B33; color: #fff;">
                <h5 class="modal-title fw-bold">Cadastrar Usuário com Controle de Acesso</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nome Completo *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">E-mail Institucional *</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Perfil de Permissão (RBAC) *</label>
                    <select name="role" class="form-select" required>
                        <option value="analista">Analista Territorial</option>
                        <option value="coordenador">Coordenador</option>
                        <option value="atendente">Atendente Comunitário</option>
                        <option value="admin">Administrador Geral</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Senha Segura (mínimo 8 caracteres — password_hash) *</label>
                    <input type="password" name="password" class="form-control" minlength="8" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Criar Usuário</button>
            </div>
        </form>
    </div>
</div>
