<?php
/**
 * PLESK CRON JOB 3 — Atualizar Indicadores Territoriais e Alertas
 * Comando Plesk sugerido (a cada 6 horas):
 * php /var/www/vhosts/fernandopaiva.com.br/inteligencia.fernandopaiva.com.br/cron/update_indicators.php
 */

require_once __DIR__ . '/CronRunner.php';

use App\Core\Database;

CronRunner::run('update_indicators', function (): string {
    $urgentOpen = (int)Database::fetchColumn(
        "SELECT COUNT(*) FROM demands WHERE priority = 'Urgente' AND status NOT IN ('Concluída', 'Arquivada')"
    );
    $totalNeighborhoods = (int)Database::fetchColumn("SELECT COUNT(*) FROM neighborhoods WHERE monitored = 1");

    $snapshot = [
        'updated_at'              => date('Y-m-d H:i:s'),
        'monitored_neighborhoods' => $totalNeighborhoods,
        'urgent_open_demands'     => $urgentOpen,
        'consented_contacts'      => (int)Database::fetchColumn("SELECT COUNT(*) FROM contacts WHERE consent_lgpd = 1"),
    ];

    file_put_contents(
        BASE_PATH . '/storage/cache/indicators_snapshot.json',
        json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );

    return "Indicadores atualizados para {$totalNeighborhoods} bairros de Vitória/ES ({$urgentOpen} demandas urgentes abertas).";
});
