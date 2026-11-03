<?php
/**
 * VITÓRIA INTELIGÊNCIA — Serviço de Backup SQL e Conformidade LGPD
 */

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;

class BackupService
{
    private static array $tables = [
        'users',
        'neighborhoods',
        'public_equipments',
        'contacts',
        'contact_interactions',
        'demands',
        'demand_history',
        'radar_items',
        'social_accounts',
        'social_media_assets',
        'social_posts',
        'agenda_events',
        'ai_analyses',
        'notifications',
        'settings',
        'audit_logs',
        'cron_runs',
    ];

    /**
     * Gera um dump SQL completo de todas as tabelas e salva em storage/backups/.
     */
    public static function createSqlBackup(): string
    {
        $backupDir = BASE_PATH . '/storage/backups';
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }

        $filename = 'backup_vitoria_inteligencia_' . date('Ymd_His') . '.sql';
        $filePath = $backupDir . '/' . $filename;

        $pdo   = Database::getConnection();
        $lines = [
            "-- =============================================================================",
            "-- BACKUP AUTOMÁTICO VITÓRIA INTELIGÊNCIA",
            "-- Data: " . date('d/m/Y H:i:s'),
            "-- =============================================================================",
            "SET NAMES utf8mb4;",
            "SET FOREIGN_KEY_CHECKS = 0;",
            "",
        ];

        foreach (self::$tables as $table) {
            try {
                $rows = Database::fetchAll("SELECT * FROM {$table}");
                $lines[] = "-- Tabela: {$table} (" . count($rows) . " registros)";
                foreach ($rows as $row) {
                    $cols = array_map(fn($c) => "`{$c}`", array_keys($row));
                    $vals = array_map(function ($v) use ($pdo) {
                        if ($v === null) {
                            return 'NULL';
                        }
                        return $pdo->quote((string)$v);
                    }, array_values($row));

                    $lines[] = "REPLACE INTO `{$table}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");";
                }
                $lines[] = "";
            } catch (\Throwable $e) {
                $lines[] = "-- Aviso ao exportar {$table}: " . $e->getMessage();
            }
        }

        $lines[] = "SET FOREIGN_KEY_CHECKS = 1;";
        file_put_contents($filePath, implode("\n", $lines), LOCK_EX);

        Logger::audit("Backup SQL gerado com sucesso: {$filename}", 'Segurança & Backup');
        return $filename;
    }

    public static function listBackups(): array
    {
        $backupDir = BASE_PATH . '/storage/backups';
        if (!is_dir($backupDir)) {
            return [];
        }
        $files = glob($backupDir . '/*.sql') ?: [];
        rsort($files);
        $result = [];
        foreach ($files as $f) {
            $result[] = [
                'name' => basename($f),
                'size' => round(filesize($f) / 1024, 1) . ' KB',
                'date' => date('d/m/Y H:i:s', filemtime($f)),
            ];
        }
        return $result;
    }
}
