<?php
// app/config.php
// Loads config from .env and defines constants
// Simple .env loader (no external libs)
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    throw new Exception('.env not found. Copy .env.example to .env and set values.');
}

define('DB_HOST', $env['DB_HOST'] ?? '127.0.0.1');
define('DB_PORT', $env['DB_PORT'] ?? '3306');
define('DB_NAME', $env['DB_NAME'] ?? 'mfa_db');
define('DB_USER', $env['DB_USER'] ?? 'root');
define('DB_PASS', $env['DB_PASS'] ?? '');
define('APP_SECRET', $env['APP_SECRET'] ?? '');
define('APP_NAME', $env['APP_NAME'] ?? 'MFA-App');
define('USE_SECURE_COOKIES', isset($env['USE_SECURE_COOKIES']) ? (bool) $env['USE_SECURE_COOKIES'] : false);
define('MAX_FAILED_ATTEMPTS', isset($env['MAX_FAILED_ATTEMPTS']) ? (int) $env['MAX_FAILED_ATTEMPTS'] : 5);
define('LOCKOUT_SECONDS', isset($env['LOCKOUT_SECONDS']) ? (int) $env['LOCKOUT_SECONDS'] : 900);
define('GMAIL_USER', 'pelumiolamilekan11@gmail.com');
define('GMAIL_APP_PASSWORD', 'stzxkpwhrufiktpe'); // 16-character app password
define('OTP_EXPIRE_SECONDS', 300); // 5 minutes
define('OTP_MIN_WAIT_SECONDS', 30); // minimum wait between OTP sends
// Basic runtime settings
ini_set('session.cookie_httponly', 1);
if (USE_SECURE_COOKIES) {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.use_strict_mode', 1);
mb_internal_encoding('UTF-8');

if (!APP_SECRET || strlen(APP_SECRET) < 16) {
    // Warn but don't halt (developer must set to a secure value)
    // In production, you should make this fatal.
    error_log("WARNING: APP_SECRET is not set or too short. Set a secure APP_SECRET in .env");
}