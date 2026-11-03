<?php
/**
 * PLESK CRON JOB 1 — Atualizar Notícias do Radar Municipal
 * Comando Plesk sugerido (a cada 2 horas):
 * php /var/www/vhosts/fernandopaiva.com.br/inteligencia.fernandopaiva.com.br/cron/update_news.php
 */

require_once __DIR__ . '/CronRunner.php';

use App\Services\RadarService;

CronRunner::run('update_news', function (): string {
    $res = RadarService::syncPublicFeeds();
    return "Feeds RSS de Vitória/ES sincronizados. Novos registros inseridos: {$res['inserted']}.";
});
