<?php
error_reporting(E_ALL & ~E_WARNING);

// public/login_totp.php - TOTP entry and verification
require_once __DIR__ . '/../app/session.php';
require_once __DIR__ . '/../app/csrf.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/config.php';
$errors = [];
$success = '';

if (empty($_SESSION['auth_user_temp']) || (time() - ($_SESSION['auth_user_temp_at'] ?? 0)) > 300) {
    header('Location: login.php');
    exit;
}
$uid = $_SESSION['auth_user_temp'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $code = trim((string) ($_POST['code'] ?? ''));
        $use_backup = !empty($_POST['use_backup']);
        $method = $_POST['method'] ?? 'totp';
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

        // Validation
        if ($use_backup) {
            if ($code === '' || !preg_match('/^[A-Za-z0-9]{4,}$/', $code)) {
                $errors[] = 'Enter a valid backup code (alphanumeric).';
            }
        } else if ($method === 'totp') {
            if (!preg_match('/^\d{6}$/', $code)) {
                $errors[] = 'Enter the 6-digit authenticator code.';
            }
        } else if ($method === 'email') {
            if (!$email) {
                $errors[] = 'Please enter a valid email.';
            }
            if ($code === '' || !preg_match('/^\d{6}$/', $code)) {
                $errors[] = 'Enter the 6-digit OTP.';
            }
        }

        if (empty($errors)) {
            try {
                if ($use_backup) {
                    // Backup code verification
                    if (verify_totp_and_login($uid, $code, true)) {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $uid;
                        $_SESSION['logged_in_at'] = time();
                        unset($_SESSION['auth_user_temp'], $_SESSION['auth_user_temp_at']);
                        header('Location: dashboard.php');
                        exit;
                    } else {
                        $errors[] = 'Invalid backup code.';
                    }
                } else if ($method === 'totp') {
                    // TOTP verification (Google Authenticator)
                    require_once __DIR__ . '/../app/totp.php';
                    $totp_result = totp_verify($uid, $code);
                    if (empty($totp_result['error']) && $totp_result === true) {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $uid;
                        $_SESSION['logged_in_at'] = time();
                        unset($_SESSION['auth_user_temp'], $_SESSION['auth_user_temp_at']);
                        header('Location: dashboard.php');
                        exit;
                    } else {
                        $errors[] = $totp_result['error'] ?? 'Invalid authenticator code.';
                    }
                } else if ($method === 'email') {
                    // Email OTP verification
                    if (empty($_SESSION['otp_hash']) || empty($_SESSION['otp_email']) || empty($_SESSION['otp_expires_at'])) {
                        $errors[] = 'No OTP was requested or session expired. Request a new OTP.';
                    } else if (strtolower($email) !== strtolower($_SESSION['otp_email'])) {
                        $errors[] = 'Email does not match the email where the OTP was sent.';
                    } else if (time() > (int) $_SESSION['otp_expires_at']) {
                        unset($_SESSION['otp_hash'], $_SESSION['otp_email'], $_SESSION['otp_expires_at']);
                        $errors[] = 'OTP has expired. Request a new one.';
                    } else {
                        if (!isset($_SESSION['otp_attempts']))
                            $_SESSION['otp_attempts'] = 0;
                        $_SESSION['otp_attempts']++;
                        if ($_SESSION['otp_attempts'] > 5) {
                            unset($_SESSION['otp_hash'], $_SESSION['otp_email'], $_SESSION['otp_expires_at']);
                            $errors[] = 'Too many attempts. Request a new OTP.';
                        } else if (password_verify($code, $_SESSION['otp_hash'])) {
                            $success = 'OTP verified — authentication successful!';
                            unset($_SESSION['otp_hash'], $_SESSION['otp_email'], $_SESSION['otp_expires_at'], $_SESSION['otp_attempts']);
                            $_SESSION['verified_email'] = $email;
                            session_regenerate_id(true);
                            $_SESSION['user_id'] = $uid;
                            $_SESSION['logged_in_at'] = time();
                            unset($_SESSION['auth_user_temp'], $_SESSION['auth_user_temp_at']);
                            header('Location: dashboard.php');
                            exit;
                        } else {
                            $errors[] = 'Invalid OTP.';
                        }
                    }
                }
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>TOTP - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row">
            <div class="col-md-6 mx-auto">
                <a href="login.php" class="btn btn-outline-primary mb-3 w-100">Back to password</a>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="mb-3">Enter TOTP or Backup Code</h3>

                        <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $e)
                                    echo "<div>" . htmlspecialchars($e) . "</div>"; ?>
                        </div>
                        <?php endif; ?>

                        <form method="post" novalidate>
                            <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                            <div class="mb-3">
                                <label class="form-label">Choose verification method:</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="method" id="method_totp"
                                        value="totp"
                                        <?php echo (empty($_POST['method']) || $_POST['method'] === 'totp') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="method_totp">Authenticator App (Google
                                        Authenticator)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="method" id="method_email"
                                        value="email"
                                        <?php echo (!empty($_POST['method']) && $_POST['method'] === 'email') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="method_email">Email OTP</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="use_backup" id="use_backup"
                                        <?php echo (!empty($_POST['use_backup']) ? 'checked' : ''); ?>>
                                    <label class="form-check-label" for="use_backup">This is a backup code</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" id="code-label">Code</label>
                                <input id="code" class="form-control" name="code" autocomplete="one-time-code"
                                    inputmode="numeric" autofocus />
                                <div class="form-text" id="code-help">Enter the 6-digit code from your authenticator
                                    app, email OTP, or backup code.</div>
                            </div>
                            <div class="mb-3" id="email-group"
                                style="display: <?php echo (!empty($_POST['method']) && $_POST['method'] === 'email') ? 'block' : 'none'; ?>;">
                                <label for="email">Email (use same address you requested OTP to):</label>
                                <input id="email" name="email" type="email" class="form-control"
                                    value="<?php echo isset($_SESSION['otp_email']) ? htmlspecialchars($_SESSION['otp_email']) : ''; ?>">
                            </div>
                            <button class="btn btn-primary">Verify &amp; Login</button>
                        </form>

                        <p class="mt-3 mb-0"><a href="login.php">Back to password step</a></p>
                        <p class="small text-muted mt-2">Lost access to your authenticator? Use one of your backup codes
                            or contact the administrator.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // UI helper: show/hide email field and update code hint
    document.addEventListener('DOMContentLoaded', function() {
        var methodTotp = document.getElementById('method_totp');
        var methodEmail = document.getElementById('method_email');
        var emailGroup = document.getElementById('email-group');
        var code = document.getElementById('code');
        var help = document.getElementById('code-help');
        var chk = document.getElementById('use_backup');

        function updateUI() {
            if (methodEmail.checked) {
                emailGroup.style.display = 'block';
                code.setAttribute('placeholder', 'Enter 6-digit OTP from email');
                help.textContent = 'Enter the 6-digit OTP sent to your email.';
            } else if (chk.checked) {
                emailGroup.style.display = 'none';
                code.setAttribute('placeholder', 'Enter backup code (alphanumeric)');
                code.setAttribute('inputmode', 'text');
                help.textContent = 'Backup codes are alphanumeric. Each code can be used once.';
            } else {
                emailGroup.style.display = 'none';
                code.setAttribute('placeholder', '123456');
                code.setAttribute('inputmode', 'numeric');
                help.textContent = 'Enter the 6-digit code from your authenticator app.';
            }
        }
        if (methodTotp && methodEmail && emailGroup && code && help && chk) {
            methodTotp.addEventListener('change', updateUI);
            methodEmail.addEventListener('change', updateUI);
            chk.addEventListener('change', updateUI);
            updateUI();
        }
    });
    </script>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>

</html>