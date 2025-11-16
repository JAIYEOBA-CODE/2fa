<?php
error_reporting(E_ALL & ~E_WARNING);

// public/login.php - password step
require_once __DIR__ . '/../app/session.php';
require_once __DIR__ . '/../app/csrf.php';
require_once __DIR__ . '/../app/auth.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        try {
            $user = verify_password_and_stage($login, $password);
            if (!$user || empty($user['email'])) {
                throw new Exception('User not found or missing email.');
            }
            $email = $user['email'];
            if (!isset($_SESSION['otp_last_sent'])) {
                $_SESSION['otp_last_sent'] = 0;
            }
            $now = time();
            if ($now - (int) $_SESSION['otp_last_sent'] < OTP_MIN_WAIT_SECONDS) {
                $errors[] = 'Please wait before requesting another OTP.';
            } else {
                // Generate 6-digit OTP
                try {
                    $otp = random_int(100000, 999999);
                } catch (Exception $e) {
                    $otp = mt_rand(100000, 999999);
                }
                $otp_hashed = password_hash((string) $otp, PASSWORD_DEFAULT);
                $_SESSION['otp_email'] = $email;
                $_SESSION['otp_hash'] = $otp_hashed;
                $_SESSION['otp_expires_at'] = $now + OTP_EXPIRE_SECONDS;
                $_SESSION['otp_last_sent'] = $now;
                $_SESSION['otp_attempts'] = 0;

                // Send mail using PHPMailer and Gmail SMTP
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = GMAIL_USER;
                    $mail->Password = GMAIL_APP_PASSWORD;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->setFrom(GMAIL_USER, 'Your App Name');
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'Your verification code (OTP)';
                    $mail->Body = "<p>Hi,</p><p>Your verification code is: <strong>{$otp}</strong></p><p>This code will expire in " . (OTP_EXPIRE_SECONDS / 60) . " minutes.</p><p>If you didn't request this, ignore this email.</p>";
                    $mail->AltBody = "Your verification code is: {$otp}. It expires in " . (OTP_EXPIRE_SECONDS / 60) . " minutes.";
                    $mail->send();
                    $_SESSION['otp_success'] = 'OTP sent. Check your email (and spam folder).';
                } catch (Exception $e) {
                    unset($_SESSION['otp_hash'], $_SESSION['otp_email'], $_SESSION['otp_expires_at']);
                    $errors[] = 'Failed to send OTP email. Mailer error: ' . $mail->ErrorInfo;
                }
                header('Location: login_totp.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row">
            <div class="col-md-6 mx-auto">
                <a href="index.php" class="btn btn-outline-primary mb-3 btn-block">home</a>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3>Login — Step 1 (Password)</h3>
                        <?php if (!empty($_SESSION['otp_success'])): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($_SESSION['otp_success']);
                                unset($_SESSION['otp_success']); ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $e)
                                    echo "<div>" . htmlspecialchars($e) . "</div>"; ?>
                        </div>
                        <?php endif; ?>
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                            <div class="mb-3">
                                <label class="form-label">Username or Email</label>
                                <input class="form-control" name="login" required />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" required />
                            </div>
                            <button class="btn btn-primary">Next → TOTP</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>