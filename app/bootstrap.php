<?php
/**
 * VITÓRIA INTELIGÊNCIA — Bootstrap e Autoloader PSR-4 Nativo (Sem exigir Composer)
 */

define('BASE_PATH', dirname(__DIR__));
define('CONFIG_FILE', BASE_PATH . '/config/config.php');
define('INSTALL_LOCK_FILE', BASE_PATH . '/storage/installed.lock');

// Autoloader PSR-4 nativo para o namespace App\
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = BASE_PATH . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Funções auxiliares globais para as views
function e(?string $val): string
{
    return \App\Core\Security::e($val);
}

function csrf_field(): string
{
    return \App\Core\Security::csrfField();
}

function csrf_token(): string
{
    return \App\Core\Security::csrfToken();
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash_' . $key] = $message;
        return null;
    }
    $val = $_SESSION['flash_' . $key] ?? null;
    unset($_SESSION['flash_' . $key]);
    return $val;
}

function get_setting(string $key, string $default = ''): string
{
    try {
        $row = \App\Core\Database::fetchOne(
            "SELECT setting_value, is_encrypted FROM settings WHERE setting_key = :k LIMIT 1",
            [':k' => $key]
        );
        if (!$row) {
            return $default;
        }
        if ((int)$row['is_encrypted'] === 1) {
            $dec = \App\Core\Security::decrypt($row['setting_value']);
            return $dec !== '' ? $dec : $default;
        }
        return (string)$row['setting_value'];
    } catch (\Throwable $e) {
        return $default;
    }
}

function set_setting(string $key, string $value, bool $encrypt = false, string $description = ''): void
{
    $storedVal = $encrypt ? \App\Core\Security::encrypt($value) : $value;
    $existing  = \App\Core\Database::fetchOne("SELECT id FROM settings WHERE setting_key = :k", [':k' => $key]);
    if ($existing) {
        \App\Core\Database::execute(
            "UPDATE settings SET setting_value = :v, is_encrypted = :enc, updated_at = :dt WHERE setting_key = :k",
            [':v' => $storedVal, ':enc' => $encrypt ? 1 : 0, ':dt' => date('Y-m-d H:i:s'), ':k' => $key]
        );
    } else {
        \App\Core\Database::insert(
            "INSERT INTO settings (setting_key, setting_value, is_encrypted, description, updated_at) VALUES (:k, :v, :enc, :desc, :dt)",
            [':k' => $key, ':v' => $storedVal, ':enc' => $encrypt ? 1 : 0, ':desc' => $description, ':dt' => date('Y-m-d H:i:s')]
        );
    }
}
