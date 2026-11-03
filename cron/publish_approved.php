<?php
/**
 * PLESK CRON JOB 5 — Executar Publicações Aprovadas na Fila (Somente com Token API Oficial Válido)
 * Comando Plesk sugerido (a cada 15 minutos):
 * php /var/www/vhosts/fernandopaiva.com.br/inteligencia.fernandopaiva.com.br/cron/publish_approved.php
 */

require_once __DIR__ . '/CronRunner.php';

use App\Services\SocialService;

CronRunner::run('publish_approved', function (): string {
    $res = SocialService::processApprovedQueue();
    return "Fila de publicações aprovadas processada: {$res['published']} publicada(s) via API Oficial, {$res['blocked']} aguardando token oficial.";
});
