<?php
/**
 * PLESK CRON JOB 2 — Gerar Relatórios Consolidados e Backup Diário
 * Comando Plesk sugerido (diariamente às 02:00):
 * php /var/www/vhosts/fernandopaiva.com.br/inteligencia.fernandopaiva.com.br/cron/generate_reports.php
 */

require_once __DIR__ . '/CronRunner.php';

use App\Services\BackupService;
use App\Services\ExportService;

CronRunner::run('generate_reports', function (): string {
    $pdfContent = ExportService::generatePdfBinary('municipal_indicators');
    $reportDir  = BASE_PATH . '/storage/backups';
    if (!is_dir($reportDir)) {
        @mkdir($reportDir, 0755, true);
    }
    $pdfFile = 'relatorio_indicadores_vitoria_' . date('Ymd') . '.pdf';
    file_put_contents($reportDir . '/' . $pdfFile, $pdfContent, LOCK_EX);

    $sqlBackup = BackupService::createSqlBackup();
    return "Relatório PDF ({$pdfFile}) e Backup SQL ({$sqlBackup}) gerados com sucesso em storage/backups/.";
});
