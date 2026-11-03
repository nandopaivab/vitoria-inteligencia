<?php
/**
 * VITÓRIA INTELIGÊNCIA — Controlador de Automações CLI para Plesk Cron Jobs (Módulo 14)
 * Implementa bloqueio por arquivo (flock) contra execuções duplicadas simultâneas,
 * logs em storage/logs/cron.log e auditoria na tabela cron_runs.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Logger;
use App\Core\Security;

class CronRunner
{
    public static function run(string $jobName, callable $task): void
    {
        if (!file_exists(CONFIG_FILE)) {
            echo "[CRON:{$jobName}] Sistema ainda não instalado (config/config.php ausente).\n";
            return;
        }

        $config = require CONFIG_FILE;
        date_default_timezone_set($config['timezone'] ?? 'America/Sao_Paulo');
        Security::init($config['app_key'] ?? '');
        Database::init($config);

        $lockDir = BASE_PATH . '/storage/cache';
        if (!is_dir($lockDir)) {
            @mkdir($lockDir, 0755, true);
        }

        $lockFile = $lockDir . '/cron_' . preg_replace('/[^a-zA-Z0-9_]/', '', $jobName) . '.lock';
        $fp = fopen($lockFile, 'c+');

        if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
            $msg = "Execução ignorada: job '{$jobName}' já está em andamento (lock ativo).";
            Logger::cron($jobName, $msg);
            try {
                Database::insert(
                    "INSERT INTO cron_runs (job_name, status, started_at, finished_at, output_message) VALUES (:j, 'skipped_locked', :dt, :dt, :msg)",
                    [':j' => $jobName, ':dt' => date('Y-m-d H:i:s'), ':msg' => $msg]
                );
            } catch (\Throwable $e) {}
            echo "[CRON:{$jobName}] {$msg}\n";
            if ($fp) {
                fclose($fp);
            }
            return;
        }

        ftruncate($fp, 0);
        fwrite($fp, (string)getmypid());

        $startedAt = date('Y-m-d H:i:s');
        $runId = null;
        try {
            $runId = Database::insert(
                "INSERT INTO cron_runs (job_name, status, started_at, output_message) VALUES (:j, 'running', :dt, 'Em execução...')",
                [':j' => $jobName, ':dt' => $startedAt]
            );
        } catch (\Throwable $e) {}

        try {
            Logger::cron($jobName, "Iniciando tarefa agendada...");
            $output = (string)$task();
            $finishedAt = date('Y-m-d H:i:s');

            if ($runId) {
                Database::execute(
                    "UPDATE cron_runs SET status = 'success', finished_at = :fin, output_message = :out WHERE id = :id",
                    [':fin' => $finishedAt, ':out' => $output, ':id' => $runId]
                );
            }
            Logger::cron($jobName, "Concluído com sucesso: {$output}");
            echo "[CRON:{$jobName}] SUCESSO: {$output}\n";
        } catch (\Throwable $e) {
            $err = $e->getMessage();
            if ($runId) {
                Database::execute(
                    "UPDATE cron_runs SET status = 'error', finished_at = :fin, output_message = :out WHERE id = :id",
                    [':fin' => date('Y-m-d H:i:s'), ':out' => $err, ':id' => $runId]
                );
            }
            Logger::cron($jobName, "ERRO: {$err}");
            echo "[CRON:{$jobName}] ERRO: {$err}\n";
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}
