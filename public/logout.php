<?php
require_once __DIR__ . '/../app/session.php';
require_once __DIR__ . '/../app/auth.php';
if (is_logged_in()) {
    log_event($_SESSION['user_id'], 'logout', null);
}
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}
session_destroy();
header('Location: index.php');
exit;