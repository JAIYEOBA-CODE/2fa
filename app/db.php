<?php
// app/db.php
// mysqli connection helper (throws exceptions on error)
require_once __DIR__ . '/config.php';

function get_db(): mysqli
{
    static $mysqli = null;
    if ($mysqli instanceof mysqli)
        return $mysqli;

    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
    if ($mysqli->connect_errno) {
        throw new Exception('DB Connect Error: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}