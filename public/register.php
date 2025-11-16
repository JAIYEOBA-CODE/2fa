<?php
// public/register.php


require_once __DIR__ . '/../app/session.php';
require_once __DIR__ . '/../app/csrf.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/qr.php';
require_once __DIR__ . '/../app/config.php';


$errors = [];
$show_qr = false;
$qr_html = '';
$backup_codes = [];
$manual_secret = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // basic password strength check (client-side too)
        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must be at least 8 chars, include an uppercase letter and a number.';
        }

        if (empty($fullname) || empty($email) || empty($username)) {
            $errors[] = 'Fill all required fields';
        }

        if (empty($errors)) {
            try {
                $res = register_user($fullname, $email, $username, password: $password);
                $secret = $res['secret'];
                $backup_codes = $res['backup_codes'];
                $uri = totp_get_provisioning_uri($email, 'MFA-App', $secret);

                // Use QRServer.com API instead of Google Charts
                $qr_src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($uri);
                $qr_html = '<img id="qr_img" src="' . htmlspecialchars($qr_src, ENT_QUOTES, 'UTF-8') .
                    '" alt="QR Code" style="width:200px; height:200px;" class="img-fluid" />';

                $show_qr = true;
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
    <title>Register - <?php echo APP_NAME; ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
    <link href="assets/css/app.css" rel="stylesheet" />
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <a href="index.php" class="btn btn-outline-primary mb-3 btn-block">home</a>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3>Register</h3>
                        <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $err)
                                    echo "<div>" . htmlspecialchars($err) . "</div>"; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($show_qr): ?>
                        <div class="alert alert-success">
                            Registration complete — scan the QR code with Google Authenticator / Authy, or enter the
                            manual code below.
                        </div>
                        <div class="d-flex gap-3 align-items-center">
                            <?php echo $qr_html; ?>
                            <div>
                                <p><strong>Manual code:</strong> <?php echo htmlspecialchars($manual_secret); ?></p>
                                <p><strong>Backup codes (store securely):</strong></p>
                                <ul>
                                    <?php foreach ($backup_codes as $b): ?>
                                    <li><code><?php echo htmlspecialchars($b); ?></code></li>
                                    <?php endforeach; ?>
                                </ul>
                                <p>Each backup code can be used once to log in when you have no TOTP device.</p>
                                <a href="login.php" class="btn btn-primary">Proceed to Login</a>
                            </div>
                        </div>
                        <?php else: ?>

                        <form method="post" novalidate>
                            <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                            <div class="mb-3">
                                <label class="form-label">Full name</label>
                                <input class="form-control" name="fullname" required />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input class="form-control" name="username" required />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input id="password" type="password" class="form-control" name="password" required />
                                <div id="pw-help" class="form-text">Min 8 chars, 1 uppercase, 1 number</div>
                                <div id="pw-strength" class="mt-2"></div>
                            </div>
                            <button class="btn btn-primary">Register</button>
                        </form>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php if ($show_qr && !empty($uri)): ?>
    <script>
    // filepath: c:\xampp\htdocs\MFA\public\register.php
    // Small client-side fallback: if the QR image fails to load, replace with Google Charts QR.
    document.addEventListener('DOMContentLoaded', function() {
        var img = document.getElementById('qr_img');
        if (!img) return;
        img.addEventListener('error', function() {
            var uri = <?php echo json_encode($uri); ?>;
            if (!uri) return;
            // Try alternate QR API if first one fails
            var fallback = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' +
                encodeURIComponent(uri);
            if (img.src !== fallback) img.src = fallback;
        });
        if (img.complete && img.naturalWidth === 0) {
            img.dispatchEvent(new Event('error'));
        }
    });
    </script>
    <?php endif; ?>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>

</html>