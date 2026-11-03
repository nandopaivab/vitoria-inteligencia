<?php
/**
 * VITÓRIA INTELIGÊNCIA — Camada de Conexão PDO Segura (MySQL 8 / MariaDB)
 * Utiliza exclusivamente Prepared Statements nativos e tratamento seguro de exceções.
 */

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];

    public static function init(array $config): void
    {
        self::$config = $config;
    }

    public static function getConnection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $dbConfig = self::$config['db'] ?? [];
        $driver   = $dbConfig['driver'] ?? 'mysql';

        try {
            if ($driver === 'sqlite') {
                $dsn = 'sqlite:' . ($dbConfig['database'] ?? (__DIR__ . '/../../storage/database.sqlite'));
                self::$instance = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                self::$instance->exec('PRAGMA foreign_keys = ON;');
            } else {
                $host    = $dbConfig['host'] ?? 'localhost';
                $port    = $dbConfig['port'] ?? '3306';
                $dbname  = $dbConfig['database'] ?? '';
                $charset = $dbConfig['charset'] ?? 'utf8mb4';
                $user    = $dbConfig['username'] ?? '';
                $pass    = $dbConfig['password'] ?? '';

                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            }
        } catch (PDOException $e) {
            Logger::error('Database Connection Error: ' . $e->getMessage());
            throw new PDOException('Falha ao conectar ao banco de dados. Verifique as configurações do sistema.');
        }

        return self::$instance;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    public static function fetchColumn(string $sql, array $params = [], int $column = 0)
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn($column);
    }

    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function insert(string $sql, array $params = []): string
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $pdo->lastInsertId();
    }
}
