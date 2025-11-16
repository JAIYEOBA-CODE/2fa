<?php
// public/dashboard.php
require_once __DIR__ . '/../app/session.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/totp.php';

require_login();

$db = get_db();
$stmt = $db->prepare("SELECT id, fullname, email, username, is_admin, created_at FROM users WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
</head>

<body class="bg-light">
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php"><?php echo APP_NAME; ?></a>
            <div>
                <a class="btn btn-outline-secondary" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>
    <main class="container py-5">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h3>Welcome, <?php echo htmlspecialchars($user['fullname']); ?></h3>
                        <p>Username: <?php echo htmlspecialchars($user['username']); ?></p>
                        <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
                        <p>Member since: <?php echo htmlspecialchars($user['created_at']); ?></p>

                        <hr />
                        <h5>Account actions</h5>
                        <ul>
                            <li><a href="account_backup_codes.php">View backup codes (download)</a></li>
                            <li><a href="account_regen_totp.php">Regenerate TOTP secret</a> (requires verification)
                            </li>
                            <li><a href="account_sessions.php">Revoke remembered devices / sessions</a></li>
                            <?php if ($user['is_admin']): ?>
                            <li><a href="admin/index.php">Admin Dashboard</a></li>
                            <?php endif; ?>
                        </ul>

                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>