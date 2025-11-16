<?php
require_once __DIR__ . '/../app/session.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

require_admin();

$db = get_db();
$users = $db->query("SELECT id, fullname, email, username, is_locked, failed_attempts, last_failed_at, created_at FROM users ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Admin - Users</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet" />
</head>

<body>
    <div class="container py-4">
        <h3>Admin Dashboard</h3>
        <a href="logs.php" class="btn btn-outline-primary mb-3">View Logs</a>
        <a href="../public/logout.php" class="btn btn-outline-primary mb-3">logout</a>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Locked</th>
                    <th>Failed</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo $u['id']; ?></td>
                    <td><?php echo htmlspecialchars($u['fullname']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td><?php echo $u['is_locked'] ? 'Yes' : 'No'; ?></td>
                    <td><?php echo $u['failed_attempts']; ?></td>
                    <td><?php echo $u['created_at']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>