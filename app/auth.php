<?php
// app/auth.php
// Handles user authentication, registration, TOTP verification, and login audit logging

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/totp.php';
require_once __DIR__ . '/session.php';

function log_event($user_id, $event, $meta = null)
{
    $db = get_db();
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $metaJson = $meta ? json_encode($meta) : null;
    $stmt = $db->prepare("INSERT INTO auth_logs (user_id, event, ip, user_agent, meta) VALUES (?,?,?,?,?)");
    $stmt->bind_param('issss', $user_id, $event, $ip, $ua, $metaJson);
    $stmt->execute();
    $stmt->close();
}

/**
 * Register new user with TOTP secret
 */
function register_user($fullname, $email, $username, $password, $phone = null)
{
    $db = get_db();

    // Ensure unique email and username
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param('ss', $email, $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        throw new Exception('Email or username already exists.');
    }
    $stmt->close();

    // Hash password and generate TOTP secret
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $secret = totp_generate_secret();

    // Insert user
    $stmt = $db->prepare("INSERT INTO users (fullname, email, username, password_hash, totp_secret, totp_enabled) VALUES (?,?,?,?,?,1)");
    $stmt->bind_param('sssss', $fullname, $email, $username, $password_hash, $secret);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new Exception('DB Insert Error: ' . $err);
    }
    $user_id = $stmt->insert_id;
    $stmt->close();

    // Generate and insert backup codes
    $backup_codes = [];
    $stmt = $db->prepare("INSERT INTO backup_codes (user_id, code_hash) VALUES (?,?)");
    foreach (range(1, 10) as $i) {
        $code = strtoupper(bin2hex(random_bytes(4))); // 8 hex characters
        $backup_codes[] = $code;
        $hash = password_hash($code, PASSWORD_DEFAULT);
        $stmt->bind_param('is', $user_id, $hash);
        $stmt->execute();
    }
    $stmt->close();

    log_event($user_id, 'register', ['email' => $email]);
    return [
        'user_id' => $user_id,
        'secret' => $secret,
        'backup_codes' => $backup_codes
    ];
}

/**
 * Find user by login (username or email)
 */
function find_user_by_login($login)
{
    $db = get_db();
    $stmt = $db->prepare("SELECT id, fullname, email, username, password_hash, totp_secret, totp_enabled, is_locked, failed_attempts, last_failed_at, is_admin FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param('ss', $login, $login);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result ?: null;
}

/**
 * Increment failed login attempts
 */
function increment_failed_attempt($user_id)
{
    $db = get_db();

    $stmt = $db->prepare("UPDATE users SET failed_attempts = failed_attempts + 1, last_failed_at = NOW() WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();

    // Lock account if threshold exceeded
    $stmt = $db->prepare("SELECT failed_attempts FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res && (int) $res['failed_attempts'] >= MAX_FAILED_ATTEMPTS) {
        $lock = $db->prepare("UPDATE users SET is_locked = 1 WHERE id = ?");
        $lock->bind_param('i', $user_id);
        $lock->execute();
        $lock->close();
        log_event($user_id, 'account-locked');
    }
}

/**
 * Reset failed login attempts
 */
function reset_failed_attempts($user_id)
{
    $db = get_db();
    $stmt = $db->prepare("UPDATE users SET failed_attempts = 0, last_failed_at = NULL WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * Verify password and prepare for second-factor authentication
 */
function verify_password_and_stage($login, $password)
{
    $user = find_user_by_login($login);
    if (!$user) {
        log_event(null, 'login-fail', ['login' => $login]);
        throw new Exception('Invalid credentials.');
    }

    if ($user['is_locked']) {
        throw new Exception('Account is locked. Please contact admin.');
    }

    if (!password_verify($password, $user['password_hash'])) {
        increment_failed_attempt($user['id']);
        log_event($user['id'], 'login-fail');
        throw new Exception('Invalid credentials.');
    }

    // Password verified — move to TOTP step
    $_SESSION['auth_user_temp'] = $user['id'];
    $_SESSION['auth_user_temp_at'] = time();
    log_event($user['id'], 'password-success');
    return $user;
}

/**
 * Verify TOTP code or backup code, then finalize login
 */
function verify_totp_and_login($user_id, $code, $use_backup = false)
{
    $db = get_db();
    $stmt = $db->prepare("SELECT id, totp_secret, totp_enabled, is_locked, email FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        throw new Exception('User not found.');
    }

    if ($user['is_locked']) {
        throw new Exception('Account locked.');
    }

    $secret = $user['totp_secret'];

    // --- Backup code verification ---
    if ($use_backup) {
        $stmt = $db->prepare("SELECT id, code_hash FROM backup_codes WHERE user_id = ? AND used = 0");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $codes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($codes as $row) {
            if (password_verify(strtoupper($code), $row['code_hash'])) {
                // Mark code as used
                $update = $db->prepare("UPDATE backup_codes SET used = 1 WHERE id = ?");
                $update->bind_param('i', $row['id']);
                $update->execute();
                $update->close();

                session_regenerate_secure();
                $_SESSION['user_id'] = $user_id;
                reset_failed_attempts($user_id);
                log_event($user_id, 'login-success-backup');
                return true;
            }
        }

        increment_failed_attempt($user_id);
        log_event($user_id, 'totp-fail', ['reason' => 'invalid-backup']);
        throw new Exception('Invalid backup code.');
    }

}

/**
 * Require admin privileges
 */
function require_admin()
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    $db = get_db();
    $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$result || !$result['is_admin']) {
        http_response_code(403);
        echo "Forbidden";
        exit;
    }
}