<?php
/**
 * VITÓRIA INTELIGÊNCIA — Camada de Segurança, Criptografia AES-256, CSRF e Sanitização
 */

namespace App\Core;

class Security
{
    private static string $appKey = '';

    public static function init(string $appKey): void
    {
        self::$appKey = $appKey ?: hash('sha256', 'vitoria-inteligencia-default-key');
    }

    /**
     * Inicia sessão PHP protegida com flags HttpOnly, SameSite=Lax e regeneração periódica.
     */
    public static function startSecureSession(string $sessionName = 'VITORIA_INTEL_SESS'): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        session_name($sessionName);
        session_set_cookie_params([
            'lifetime' => 7200,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();

        if (!isset($_SESSION['_created_at'])) {
            $_SESSION['_created_at'] = time();
        } elseif (time() - $_SESSION['_created_at'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['_created_at'] = time();
        }
    }

    /**
     * Gera ou retorna o token CSRF da sessão atual.
     */
    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    /**
     * Campo HTML oculto com o token CSRF para formulários POST.
     */
    public static function csrfField(): string
    {
        $token = self::e(self::csrfToken());
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Valida o token CSRF recebido via POST ou cabeçalho X-CSRF-Token.
     */
    public static function verifyCsrf(?string $token = null): bool
    {
        $token = $token ?? ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        if (empty($token) || empty($_SESSION['_csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['_csrf_token'], (string)$token);
    }

    /**
     * Exige token CSRF válido em requisições POST; interrompe com erro 403 se inválido.
     */
    public static function requireCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !self::verifyCsrf()) {
            Logger::audit('Falha de validação CSRF bloqueada', 'Segurança');
            http_response_code(403);
            die('Erro de segurança (403): Token CSRF inválido ou sessão expirada. Recarregue a página e tente novamente.');
        }
    }

    /**
     * Escape seguro para saída HTML (prevenção de XSS).
     */
    public static function e(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Sanitiza string de entrada removendo tags de controle e espaços excedentes.
     */
    public static function cleanInput(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return trim(strip_tags($value));
    }

    /**
     * Criptografia simétrica AES-256-CBC para armazenar chaves de API (OpenAI, Meta, X) no banco.
     */
    public static function encrypt(string $plainText): string
    {
        if ($plainText === '') {
            return '';
        }
        if (!function_exists('openssl_encrypt')) {
            return base64_encode($plainText);
        }
        $key = hash('sha256', self::$appKey, true);
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');
        $iv = random_bytes($ivLength);
        $cipherText = openssl_encrypt($plainText, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $cipherText, $key, true);
        return base64_encode($iv . $hmac . $cipherText);
    }

    /**
     * Descriptografa valor criptografado com AES-256-CBC e verifica integridade HMAC.
     */
    public static function decrypt(?string $encodedPayload): string
    {
        if (empty($encodedPayload)) {
            return '';
        }
        if (!function_exists('openssl_decrypt')) {
            return (string)base64_decode($encodedPayload, true);
        }
        $raw = base64_decode($encodedPayload, true);
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');
        if ($raw === false || strlen($raw) <= ($ivLength + 32)) {
            return '';
        }
        $key = hash('sha256', self::$appKey, true);
        $iv = substr($raw, 0, $ivLength);
        $hmac = substr($raw, $ivLength, 32);
        $cipherText = substr($raw, $ivLength + 32);
        $calcHmac = hash_hmac('sha256', $cipherText, $key, true);
        if (!hash_equals($hmac, $calcHmac)) {
            return '';
        }
        $decrypted = openssl_decrypt($cipherText, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $decrypted !== false ? $decrypted : '';
    }

    /**
     * Retorna o IP real do cliente considerando proxies reversos do Plesk/Nginx.
     */
    public static function clientIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}
