<?php
// app/totp.php
// Self-contained RFC6238-compatible TOTP (works with Google Authenticator)
// Provides: generateSecret(), getCode(), verifyCode(), getProvisioningUri()
// Secrets can be stored encrypted by the app using APP_SECRET and openssl
require_once __DIR__ . '/../vendor/autoload.php';

use OTPHP\TOTP;
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
// Base32 encode/decode helpers (RFC4648)


function totp_generate_secret()
{
    $totp = TOTP::create(null, 30, 'sha1', 6);
    $secret = $totp->getSecret();
    return $secret;
}

function totp_get_timestamp($time = null)
{
    $time = $time ?? time();
    return floor($time / 30);
}

function totp_hmac($key, $counter)
{
    // key is binary
    $counterBytes = pack('N*', 0) . pack('N*', $counter); // 64-bit big-endian
    return hash_hmac('sha1', $counterBytes, $key, true);
}

function get_user($identifier)
{
    $db = get_db();

    $stmt = $db->prepare(query: "SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param('ss', $identifier, $identifier);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}


function totp_verify($identifier, $code)
{
    // Get user details
    $user = get_user($identifier);
    if (!$user) {
        return ['error' => 'User not found'];
    }

    // Retrieve and validate secret
    $secret = trim($user['totp_secret']);
    if (empty($secret)) {
        return ['error' => 'User has no TOTP secret'];
    }

    // Create TOTP object (period = 30s, algorithm = SHA1, 6 digits)
    $totp = TOTP::create($secret, 30, 'sha1', 6);
    $totp->setLabel($user['email']);

    // Verify the user-entered code (allow small time window drift of 1 step)
    $verified = $totp->verify($code, null, 1);

    if ($verified) {
        return [
            'success' => true,
            'user' => $user,
            'debug' => [
                'message' => 'TOTP code verified successfully',
                'entered_code' => $code,
                'secret' => $secret
            ]
        ];
    } else {
        // For debugging: show what the current valid code is
        return [
            'error' => 'Invalid authentication code',
            'debug' => [
                'current_valid_code' => $totp->now(),
                'entered_code' => $code,
                'secret' => $secret
            ]
        ];
    }
}


function totp_get_provisioning_uri($email, $issuer, $secret)
{
    // Create a TOTP instance with the same parameters you used for generation
    $totp = TOTP::create($secret, 30, 'sha1', 6);
    $totp->setLabel($email);
    $totp->setIssuer($issuer);
    return $totp->getProvisioningUri();
}