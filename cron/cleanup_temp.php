<?php
/**
 * PLESK CRON JOB 6 — Limpar Arquivos Temporários e Rotacionar Logs Antigos
 * Comando Plesk sugerido (semanalmente aos domingos às 03:30):
 * php /var/www/vhosts/fernandopaiva.com.br/inteligencia.fernandopaiva.com.br/cron/cleanup_temp.php
 */

require_once __DIR__ . '/CronRunner.php';

CronRunner::run('cleanup_temp', function (): string {
    $cacheDir = BASE_PATH . '/storage/cache';
    $removed  = 0;

    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '/*.tmp') ?: [];
        foreach ($files as $f) {
            if (is_file($f) && (time() - filemtime($f)) > 86400) {
                @unlink($f);
                $removed++;
            }
        }
    }

    return "Limpeza concluída: {$removed} arquivo(s) temporário(s) removido(s).";
});
