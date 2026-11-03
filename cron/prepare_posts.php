<?php
/**
 * PLESK CRON JOB 4 — Preparar Publicações Recorrentes e Notificar Revisores
 * Comando Plesk sugerido (diariamente às 07:00):
 * php /var/www/vhosts/fernandopaiva.com.br/inteligencia.fernandopaiva.com.br/cron/prepare_posts.php
 */

require_once __DIR__ . '/CronRunner.php';

use App\Core\Database;

CronRunner::run('prepare_posts', function (): string {
    $pendingReview = (int)Database::fetchColumn(
        "SELECT COUNT(*) FROM social_posts WHERE approval_status = 'Em Revisão'"
    );

    if ($pendingReview > 0) {
        Database::insert(
            "INSERT INTO notifications (title, message, type, link, is_read, created_at)
             VALUES (:t, :m, 'info', '?route=social', 0, :dt)",
            [
                ':t'  => 'Publicações aguardando aprovação editorial',
                ':m'  => "Existem {$pendingReview} publicação(ões) no calendário editorial aguardando aprovação antes do agendamento.",
                ':dt' => date('Y-m-d H:i:s'),
            ]
        );
    }

    return "Verificação do calendário editorial concluída ({$pendingReview} publicações em revisão).";
});
