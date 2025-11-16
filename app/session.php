<?php
// app/session.php
// Session initialization and helper functions
require_once __DIR__ . '/config.php';

session_name('mfa_session');
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => 0, // session cookie
    'path' => $cookieParams['path'],
    'domain' => $cookieParams['domain'],
    'secure' => USE_SECURE_COOKIES,
    'httponly' => true,
    'samesite' => 'Lax'
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Short helper to enforce login timeouts and regenerate
function session_regenerate_secure()
{
    // regenerate id and set some times
    session_regenerate_id(true);
    $_SESSION['created_at'] = time();
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function require_login()
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}