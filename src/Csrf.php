<?php
declare(strict_types=1);

/** Per-session CSRF token. Required on every state-changing request. */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf" value="' . e(self::token()) . '">';
    }

    public static function check(?string $given): bool
    {
        $expected = $_SESSION['csrf'] ?? '';
        return is_string($given) && $expected !== '' && hash_equals((string) $expected, $given);
    }

    /** Abort unless the request carries a valid token. */
    public static function requireValid(bool $json = false): void
    {
        $given = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (self::check(is_string($given) ? $given : null)) {
            return;
        }
        if ($json) {
            json_out(['ok' => false, 'error' => 'Your session expired. Reload the page and try again.'], 419);
        }
        http_response_code(419);
        echo 'Your session expired. Please go back, reload the page, and try again.';
        exit;
    }
}
