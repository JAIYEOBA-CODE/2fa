<?php
// app/csrf.php
// Simple CSRF token helper using session
require_once __DIR__ . '/session.php';
error_reporting(E_ALL & ~E_WARNING);

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify($token): bool
{
    if (!isset($_SESSION['csrf_token']))
        return false;
    return hash_equals($_SESSION['csrf_token'], (string) $token);
}