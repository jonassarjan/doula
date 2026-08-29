<?php
class Auth
{
    public static function check(): bool
    {
        return !empty($_SESSION['admin']);
    }

    public static function require(): void
    {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/admin/login.php');
            exit;
        }
    }

    public static function attempt(string $email, string $password): bool
    {
        if ($email === ADMIN_EMAIL && password_verify($password, ADMIN_PASSWORD_HASH)) {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            return true;
        }
        return false;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }
}
