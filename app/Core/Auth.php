<?php
/**
 * VITÓRIA INTELIGÊNCIA — Autenticação Segura (password_hash / password_verify) e Controle de Acesso (RBAC)
 */

namespace App\Core;

class Auth
{
    public static function check(): bool
    {
        return !empty($_SESSION['user']['id']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
    }

    public static function role(): string
    {
        return $_SESSION['user']['role'] ?? 'visitante';
    }

    /**
     * Verifica se o usuário possui uma das funções exigidas.
     */
    public static function hasRole(array $allowedRoles): bool
    {
        if (!self::check()) {
            return false;
        }
        return in_array(self::role(), $allowedRoles, true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: index.php?route=login');
            exit;
        }
    }

    public static function requireRole(array $allowedRoles): void
    {
        self::requireLogin();
        if (!self::hasRole($allowedRoles)) {
            Logger::audit('Acesso negado por perfil insuficiente', 'RBAC', 'Rota solicitada com perfil: ' . self::role());
            $_SESSION['flash_error'] = 'Permissão insuficiente para executar esta operação.';
            header('Location: index.php?route=dashboard');
            exit;
        }
    }

    /**
     * Realiza login validando hash bcrypt/argon2i via password_verify.
     */
    public static function attempt(string $email, string $password): bool
    {
        $email = trim(strtolower($email));
        $user  = Database::fetchOne(
            "SELECT * FROM users WHERE LOWER(email) = :email AND active = 1 LIMIT 1",
            [':email' => $email]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Logger::audit('Tentativa de login inválida', 'Autenticação', "E-mail: {$email}");
            return false;
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            Database::execute(
                "UPDATE users SET password_hash = :hash WHERE id = :id",
                [':hash' => $newHash, ':id' => $user['id']]
            );
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'    => (int)$user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ];

        Database::execute(
            "UPDATE users SET last_login_at = :dt, last_login_ip = :ip WHERE id = :id",
            [
                ':dt' => date('Y-m-d H:i:s'),
                ':ip' => Security::clientIp(),
                ':id' => $user['id'],
            ]
        );

        Logger::audit('Login realizado com sucesso', 'Autenticação', "Usuário: {$user['email']} ({$user['role']})");
        return true;
    }

    public static function logout(): void
    {
        if (self::check()) {
            Logger::audit('Logout efetuado', 'Autenticação', 'Usuário encerrou a sessão');
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }
}
