<?php
/**
 * Practice Onboarding Tracker - application bootstrap.
 *
 * Loaded by every entry point. Loads config, opens the database,
 * starts the session, and pulls in the rest of the app.
 *
 * This application stores operational onboarding data only.
 * It must never be used to store protected health information.
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_VERSION', '1.0.0');

// PHP 8.0 or newer is required. Say so clearly rather than dying on a
// parse error, since GoDaddy lets you pick the version per domain.
if (PHP_VERSION_ID < 80000) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "This application needs PHP 8.0 or newer. This server is running " . PHP_VERSION . ".\n"
       . "In cPanel, open 'Select PHP Version' and switch this domain to PHP 8.1 or 8.2.\n";
    exit;
}

// ---------------------------------------------------------------------
// Config
// ---------------------------------------------------------------------

$configFile = APP_ROOT . '/config/config.php';

if (!is_file($configFile)) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Setup needed</title>'
       . '<div style="font:15px/1.6 system-ui,sans-serif;max-width:38rem;margin:6rem auto;padding:0 1.5rem">'
       . '<h1 style="font-size:1.3rem">Setup needed</h1>'
       . '<p><code>config/config.php</code> was not found. Copy '
       . '<code>config/config.sample.php</code> to <code>config/config.php</code> '
       . 'on the server and fill in your database credentials.</p></div>';
    exit;
}

/** @var array<string,mixed> $config */
$config = require $configFile;

$defaults = [
    'db_host' => 'localhost', 'db_charset' => 'utf8mb4',
    'app_name' => 'Practice Onboarding Tracker',
    'timezone' => 'America/New_York',
    'admin_pin_hash' => '', 'allow_install' => false,
    'session_minutes' => 480,
];
$config = array_merge($defaults, $config);

date_default_timezone_set((string) $config['timezone']);

// Log errors, never print them to visitors.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// ---------------------------------------------------------------------
// Session
// ---------------------------------------------------------------------

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('pot_session');
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Activity.php';
require_once __DIR__ . '/Repo.php';

Database::configure($config);
Auth::configure($config);
