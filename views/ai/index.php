<?php
/**
 * VITÓRIA INTELIGÊNCIA — Assistente de Inteligência Artificial (Módulo 9)
 *
 * 8 Capacidades Obrigatórias:
 * 1. Resumir notícias (Resumo de Notícias)
 * 2. Analisar demandas municipais (Análise de Demandas Municipais)
 * 3. Identificar tendências de assuntos públicos (Tendências de Assuntos Públicos)
 * 4. Gerar relatórios (Relatório Executivo)
 * 5. Sugerir pautas informativas (Sugestão de Pautas Informativas)
 * 6. Elaborar calendário editorial (Calendário Editorial)
 * 7. Preparar textos institucionais (Texto Institucional)
 * 8. Analisar indicadores (Análise de Indicadores)
 *
 * Todas as análises apresentam obrigatoriamente fontes, datas e limitações dos dados.
 */

$aiTasks = [
    'Resumo de Notícias',
    'Análise de Demandas Municipais',
    'Tendências de Assuntos Públicos',
    'Relatório Executivo',
    'Sugestão de Pautas Informativas',
    'Calendário Editorial',
    'Texto Institucional',
    'Análise de Indicadores',
];
?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="vi-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-stars text-primary me-2"></i>Assistente Estratégico IA</h5>
                <?php if ($openAiConfigured): ?>
                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>API OpenAI Conectada</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark"><i class="bi bi-cpu me-1"></i>Modo Analítico Local</span>
                <?php endif; ?>
            </div>

            <p class="small text-muted">
                Selecione uma das 8 funções analíticas abaixo. O assistente cruza em tempo real o banco de demandas dos 80 bairros de Vitória/ES, o Radar Municipal e os indicadores territoriais, apresentando <strong>fontes, datas e limitações metodológicas</strong>.
            </p>

            <form method="POST" action="index.php?route=ai_execute">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Função Analítica *</label>
                    <select name="analysis_type" class="form-select" required>
                        <?php foreach ($aiTasks as $t): ?>
                            <option value="<?= e($t) ?>"><?= e($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Contexto ou Recorte Territorial Específico (Opcional)</label>
                    <textarea name="custom_prompt" rows="4" class="form-control" placeholder="Ex: Focar nas demandas de Saúde e Drenagem da Região 4 (Maruípe) e Região 7 (São Pedro)..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-lightning-charge-fill me-1"></i>Executar Análise com Fontes e Limitações
                </button>
            </form>

            <?php if (!$openAiConfigured): ?>
                <div class="alert alert-light border small mt-3 mb-0">
                    <i class="bi bi-key text-primary me-1"></i>
                    Para habilitar chamadas ao modelo externo <code>gpt-4o-mini</code> / <code>gpt-4o</code> da OpenAI, informe sua chave <code>sk-...</code> em <a href="index.php?route=settings">Segurança, APIs & LGPD</a>.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="vi-card">
            <div class="vi-card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-journal-richtext text-primary me-2"></i>Histórico de Análises, Pareceres e Pautas Geradas (<?= count($analyses) ?>)</h6>
            </div>

            <div class="p-3" style="max-height: 700px; overflow-y: auto;">
                <?php if (empty($analyses)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-stars fs-1 text-secondary"></i>
                        <p class="mt-2 mb-0">Execute sua primeira análise no painel ao lado para gerar pareceres fundamentados.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($analyses as $an): ?>
                        <div class="border rounded-3 p-3 mb-3 bg-white shadow-sm">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                <div>
                                    <span class="badge bg-primary me-1"><?= e($an['analysis_type']) ?></span>
                                    <span class="badge bg-light text-dark border"><?= e($an['model_used']) ?></span>
                                </div>
                                <small class="text-muted"><i class="bi bi-clock me-1"></i><?= e(date('d/m/Y H:i', strtotime($an['created_at']))) ?> — <?= e($an['user_name']) ?></small>
                            </div>

                            <div class="small text-muted mb-2">
                                <strong>Parâmetro:</strong> <?= e($an['prompt_context']) ?>
                            </div>

                            <div class="p-3 bg-light rounded border mb-3 small" style="white-space: pre-wrap;"><?= e($an['ai_response']) ?></div>

                            <div class="row g-2 small">
                                <div class="col-md-6">
                                    <div class="p-2 rounded border bg-success-subtle text-success-emphasis h-100" style="white-space: pre-wrap; font-size: 0.76rem;">
                                        <strong><i class="bi bi-check2-circle me-1"></i>Fontes e Datas Verificadas:</strong><br><?= e($an['data_sources']) ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-2 rounded border bg-warning-subtle text-warning-emphasis h-100" style="white-space: pre-wrap; font-size: 0.76rem;">
                                        <strong><i class="bi bi-exclamation-triangle me-1"></i>Limitações dos Dados:</strong><br><?= e($an['data_limitations']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
