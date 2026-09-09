<?php
declare(strict_types=1);

/**
 * Shared-PIN admin authentication.
 *
 * Not signed in  = read-only viewer (the public dashboard).
 * Signed in      = admin, may edit everything.
 */
final class Auth
{
    private static array $config = [];

    public static function configure(array $config): void
    {
        self::$config = $config;
    }

    public static function isAdmin(): bool
    {
        if (empty($_SESSION['is_admin'])) {
            return false;
        }
        $ttl = max(5, (int) (self::$config['session_minutes'] ?? 480)) * 60;
        $last = (int) ($_SESSION['admin_since'] ?? 0);
        if ($last <= 0 || (time() - $last) > $ttl) {
            self::logout();
            return false;
        }
        return true;
    }

    /** True when a PIN has actually been configured. */
    public static function pinConfigured(): bool
    {
        return trim((string) (self::$config['admin_pin_hash'] ?? '')) !== '';
    }

    /**
     * Attempt a login. Returns an error string, or null on success.
     * Throttled to slow down guessing of a short PIN.
     */
    public static function attempt(string $pin): ?string
    {
        $now = time();
        $tries = (array) ($_SESSION['login_tries'] ?? []);
        $tries = array_values(array_filter($tries, static fn($t) => ($now - (int) $t) < 900));

        if (count($tries) >= 8) {
            $_SESSION['login_tries'] = $tries;
            return 'Too many attempts. Wait 15 minutes and try again.';
        }

        if (!self::pinConfigured()) {
            return 'No admin PIN is configured yet. Set admin_pin_hash in config/config.php.';
        }

        // Constant-ish time: always run the hash comparison.
        $ok = password_verify($pin, (string) self::$config['admin_pin_hash']);

        // Slow every attempt down a little.
        usleep(250000);

        if (!$ok) {
            $tries[] = $now;
            $_SESSION['login_tries'] = $tries;
            return 'That PIN was not recognised.';
        }

        unset($_SESSION['login_tries']);
        session_regenerate_id(true);
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_since'] = $now;
        return null;
    }

    public static function logout(): void
    {
        unset($_SESSION['is_admin'], $_SESSION['admin_since']);
    }

    /** Abort a request that requires admin rights. */
    public static function requireAdmin(bool $json = false): void
    {
        if (self::isAdmin()) {
            return;
        }
        if ($json) {
            json_out(['ok' => false, 'error' => 'Read-only. Sign in as an admin to make changes.'], 403);
        }
        redirect(url('login', ['next' => $_SERVER['REQUEST_URI'] ?? '']));
    }

    /** Label recorded in the activity log. */
    public static function actor(): string
    {
        return self::isAdmin() ? 'admin' : 'viewer';
    }
}
