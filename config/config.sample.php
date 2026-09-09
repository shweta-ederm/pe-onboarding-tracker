<?php
/**
 * Copy this file to config/config.php on the server and fill it in.
 * config/config.php is gitignored and is never overwritten by deploys.
 */
return [
    // --- Database (cPanel > MySQL Databases) --------------------------
    'db_host'     => 'localhost',
    'db_name'     => 'cpaneluser_onboarding',
    'db_user'     => 'cpaneluser_onboard',
    'db_pass'     => 'CHANGE_ME',
    'db_charset'  => 'utf8mb4',

    // --- Admin access -------------------------------------------------
    // Generate a hash by opening public/install.php, or in PHP:
    //   echo password_hash('your-pin-here', PASSWORD_DEFAULT);
    // Do NOT store the PIN in plain text.
    'admin_pin_hash' => '',

    // --- Behaviour ----------------------------------------------------
    'app_name'      => 'Practice Onboarding Tracker',
    'timezone'      => 'America/New_York',

    // Set to false once you are live. When true, public/install.php works.
    'allow_install' => true,

    // Session lifetime for an admin login, in minutes.
    'session_minutes' => 480,
];
