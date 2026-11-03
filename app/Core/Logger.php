<?php
/**
 * VITÓRIA INTELIGÊNCIA — Sistema de Logs de Arquivo e Auditoria em Banco de Dados (LGPD)
 */

namespace App\Core;

use Throwable;

class Logger
{
    private static string $logDir = __DIR__ . '/../../storage/logs';

    public static function writeFileLog(string $level, string $message, string $file = 'app.log'): void
    {
        if (!is_dir(self::$logDir)) {
            @mkdir(self::$logDir, 0755, true);
        }
        $date = date('Y-m-d H:i:s');
        $ip   = Security::clientIp();
        $line = "[{$date}] [{$level}] [IP: {$ip}] {$message}" . PHP_EOL;
        @file_put_contents(self::$logDir . '/' . $file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message): void
    {
        self::writeFileLog('INFO', $message);
    }

    public static function error(string $message): void
    {
        self::writeFileLog('ERROR', $message, 'error.log');
    }

    public static function cron(string $jobName, string $message): void
    {
        self::writeFileLog('CRON:' . $jobName, $message, 'cron.log');
    }

    /**
     * Registra evento de auditoria na tabela audit_logs e em arquivo.
     */
    public static function audit(string $action, string $module, ?string $details = null): void
    {
        $userId   = $_SESSION['user']['id'] ?? null;
        $userName = $_SESSION['user']['name'] ?? 'Sistema';
        $ip       = Security::clientIp();

        self::writeFileLog('AUDIT', "[{$module}] ({$userName}) {$action} - {$details}", 'audit.log');

        try {
            Database::insert(
                "INSERT INTO audit_logs (user_id, user_name, action, module, details, ip_address, created_at)
                 VALUES (:uid, :uname, :action, :mod, :det, :ip, :dt)",
                [
                    ':uid'    => $userId,
                    ':uname'  => $userName,
                    ':action' => $action,
                    ':mod'    => $module,
                    ':det'    => $details,
                    ':ip'     => $ip,
                    ':dt'     => date('Y-m-d H:i:s'),
                ]
            );
        } catch (Throwable $e) {
            // Silencioso caso o banco ainda esteja em processo de instalação
        }
    }
}
