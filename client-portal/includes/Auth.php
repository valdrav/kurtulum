<?php

declare(strict_types=1);

final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;

    public static function attempt(array $config, string $username, string $password): bool
    {
        if (self::isLocked()) {
            return false;
        }

        $users = $config['users'] ?? [];
        $hash = $users[$username] ?? null;

        if (! is_string($hash) || ! password_verify($password, $hash)) {
            self::registerFailure();

            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = $username;
        unset($_SESSION['login_failures'], $_SESSION['login_locked_until']);

        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function user(): ?string
    {
        $user = $_SESSION['user'] ?? null;

        return is_string($user) ? $user : null;
    }

    public static function isLocked(): bool
    {
        $until = $_SESSION['login_locked_until'] ?? 0;

        return is_int($until) && $until > time();
    }

    public static function lockMessage(): string
    {
        $until = (int) ($_SESSION['login_locked_until'] ?? 0);
        $mins = max(1, (int) ceil(($until - time()) / 60));

        return "Çok fazla hatalı deneme. {$mins} dakika sonra tekrar deneyin.";
    }

    private static function registerFailure(): void
    {
        $_SESSION['login_failures'] = (int) ($_SESSION['login_failures'] ?? 0) + 1;
        if ($_SESSION['login_failures'] >= self::MAX_ATTEMPTS) {
            $_SESSION['login_locked_until'] = time() + (self::LOCK_MINUTES * 60);
            $_SESSION['login_failures'] = 0;
        }
    }
}
