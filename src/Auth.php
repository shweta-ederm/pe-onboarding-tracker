<?php
declare(strict_types=1);

/**
 * Who is using the portal, and what they may do.
 *
 * Three states:
 *
 *   admin   The global administrator. Signs in with the PIN in
 *           config/config.php. May do anything.
 *   member  A person from the People screen. Signs in with their
 *           username and 6-digit PIN. Sees everything, and may change
 *           only the tasks assigned to them.
 *   guest   Not signed in. Sees the sign-in page and nothing else.
 *
 * Every permission question is answered here, and every answer is
 * checked again in the request handler before anything is written.
 * Hiding a control in a template is a courtesy, not a control.
 */
final class Auth
{
    public const ADMIN  = 'admin';
    public const MEMBER = 'member';
    public const GUEST  = 'guest';

    private static array $config = [];
    private static ?array $memberCache = null;

    public static function configure(array $config): void
    {
        self::$config = $config;
    }

    // -----------------------------------------------------------------
    // Who is here
    // -----------------------------------------------------------------

    public static function role(): string
    {
        if (!self::sessionFresh()) {
            return self::GUEST;
        }
        if (!empty($_SESSION['is_admin'])) {
            return self::ADMIN;
        }
        if (!empty($_SESSION['member_id']) && self::member() !== null) {
            return self::MEMBER;
        }
        return self::GUEST;
    }

    public static function isAdmin(): bool
    {
        return self::role() === self::ADMIN;
    }

    public static function isMember(): bool
    {
        return self::role() === self::MEMBER;
    }

    /** True for anyone signed in, admin or member. */
    public static function isSignedIn(): bool
    {
        return self::role() !== self::GUEST;
    }

    /** The signed-in member's row, or null. */
    public static function member(): ?array
    {
        if (empty($_SESSION['member_id'])) {
            return null;
        }
        if (self::$memberCache !== null) {
            return self::$memberCache;
        }
        $row = Database::one(
            'SELECT * FROM assignees WHERE id = :id AND is_active = 1',
            ['id' => (int) $_SESSION['member_id']]
        );
        // Deactivating someone ends their session at the next request.
        if (!$row) {
            unset($_SESSION['member_id']);
            return null;
        }
        self::$memberCache = $row;
        return $row;
    }

    /** The signed-in member's id, or null for admin and guests. */
    public static function memberId(): ?int
    {
        $m = self::isMember() ? self::member() : null;
        return $m ? (int) $m['id'] : null;
    }

    /** Name to show in the top bar. */
    public static function displayName(): string
    {
        if (self::isAdmin()) {
            return 'Administrator';
        }
        $m = self::member();
        return $m ? (string) $m['name'] : 'Guest';
    }

    /** Name recorded against every change in the activity log. */
    public static function actor(): string
    {
        if (self::isAdmin()) {
            return 'Administrator';
        }
        $m = self::member();
        if (!$m) {
            return 'Unknown';
        }
        $full = trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? ''));
        return $full !== '' ? $full : (string) $m['name'];
    }

    private static function sessionFresh(): bool
    {
        if (empty($_SESSION['signed_in_at'])) {
            return false;
        }
        $ttl  = max(5, (int) (self::$config['session_minutes'] ?? 480)) * 60;
        $last = (int) $_SESSION['signed_in_at'];
        if ($last <= 0 || (time() - $last) > $ttl) {
            self::logout();
            return false;
        }
        return true;
    }

    // -----------------------------------------------------------------
    // What they may do
    // -----------------------------------------------------------------

    /** Administration: products, tasks, people, categories, archive. */
    public static function canManage(): bool
    {
        return self::isAdmin();
    }

    /**
     * May the current user change this task on this practice?
     *
     * Admin: always. Member: only when the task's effective assignee is
     * them, which includes tasks they hold through a default assignee.
     */
    public static function canEditTask(int $practiceId, int $taskId): bool
    {
        if (self::isAdmin()) {
            return true;
        }
        $me = self::memberId();
        if ($me === null) {
            return false;
        }

        $owner = Database::scalar(
            'SELECT COALESCE(pt.assignee_id, t.default_assignee_id)
               FROM tasks t
               LEFT JOIN practice_tasks pt
                      ON pt.practice_id = :pid AND pt.task_id = t.id
              WHERE t.id = :tid',
            ['pid' => $practiceId, 'tid' => $taskId]
        );

        return $owner !== null && (int) $owner === $me;
    }

    // -----------------------------------------------------------------
    // Signing in
    // -----------------------------------------------------------------

    public static function pinConfigured(): bool
    {
        return trim((string) (self::$config['admin_pin_hash'] ?? '')) !== '';
    }

    /**
     * Try the global administrator PIN.
     * Returns an error message, or null on success.
     */
    public static function attemptAdmin(string $pin): ?string
    {
        if ($err = self::throttle()) {
            return $err;
        }
        if (!self::pinConfigured()) {
            return 'No administrator PIN is configured yet. Set admin_pin_hash in config/config.php.';
        }

        $ok = password_verify($pin, (string) self::$config['admin_pin_hash']);
        usleep(250000);

        if (!$ok) {
            self::recordFailure();
            return 'That PIN was not recognised.';
        }

        self::startSession();
        $_SESSION['is_admin'] = true;
        return null;
    }

    /**
     * Try a team member's username and PIN.
     * Returns an error message, or null on success.
     */
    public static function attemptMember(string $username, string $pin): ?string
    {
        if ($err = self::throttle()) {
            return $err;
        }

        $username = trim($username);
        $row = $username === '' ? null : Database::one(
            'SELECT * FROM assignees WHERE username = :u AND is_active = 1',
            ['u' => $username]
        );

        // Verify against a dummy hash when the user does not exist, so a
        // wrong username and a wrong PIN take the same time to fail.
        $hash = $row['pin_hash'] ?? '$2y$10$usesomesillystringfoeleven.eKvjWMR7BnHqMYBQ0K0ZyQZFZ0oS';
        $ok   = password_verify($pin, (string) $hash) && $row && !empty($row['pin_hash']);
        usleep(250000);

        if (!$ok) {
            self::recordFailure();
            return 'That username and PIN did not match. Ask your administrator if you need a new PIN.';
        }

        self::startSession();
        $_SESSION['member_id'] = (int) $row['id'];
        self::$memberCache = null;

        Database::run('UPDATE assignees SET last_login = NOW() WHERE id = :id', ['id' => (int) $row['id']]);
        return null;
    }

    private static function startSession(): void
    {
        unset($_SESSION['login_tries']);
        session_regenerate_id(true);
        $_SESSION['signed_in_at'] = time();
    }

    /** Slow down guessing of a six-digit PIN. */
    private static function throttle(): ?string
    {
        $now   = time();
        $tries = array_values(array_filter(
            (array) ($_SESSION['login_tries'] ?? []),
            static fn($t) => ($now - (int) $t) < 900
        ));
        $_SESSION['login_tries'] = $tries;

        return count($tries) >= 8
            ? 'Too many attempts. Wait 15 minutes and try again.'
            : null;
    }

    private static function recordFailure(): void
    {
        $tries = (array) ($_SESSION['login_tries'] ?? []);
        $tries[] = time();
        $_SESSION['login_tries'] = $tries;
    }

    public static function logout(): void
    {
        unset($_SESSION['is_admin'], $_SESSION['member_id'], $_SESSION['signed_in_at']);
        self::$memberCache = null;
    }

    /** Confirm the administrator PIN again, for destructive actions. */
    public static function confirmAdminPin(string $pin): bool
    {
        return self::pinConfigured()
            && password_verify($pin, (string) self::$config['admin_pin_hash']);
    }

    // -----------------------------------------------------------------
    // Guards
    // -----------------------------------------------------------------

    /** Everything needs a sign-in now. */
    public static function requireLogin(bool $json = false): void
    {
        if (self::isSignedIn()) {
            return;
        }
        if ($json) {
            json_out(['ok' => false, 'error' => 'Your session ended. Reload and sign in again.'], 401);
        }
        redirect(url('login', ['next' => $_SERVER['REQUEST_URI'] ?? '']));
    }

    /** Administration only. */
    public static function requireAdmin(bool $json = false): void
    {
        if (self::isAdmin()) {
            return;
        }
        if ($json) {
            json_out(['ok' => false, 'error' => 'Only the administrator can do that.'], 403);
        }
        if (self::isMember()) {
            http_response_code(403);
            render('error', [
                'title'   => 'Not available to you',
                'message' => 'That screen is for the administrator. You can view everything, '
                           . 'and change the tasks assigned to you.',
            ]);
            exit;
        }
        redirect(url('login', ['next' => $_SERVER['REQUEST_URI'] ?? '']));
    }
}
