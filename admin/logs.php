<?php
require_once __DIR__ . '/../app/session.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_admin();
$db = get_db();
$logs = $db->query("SELECT al.id, al.user_id, al.event, al.ip, al.user_agent, al.meta, al.created_at, u.username FROM auth_logs al LEFT JOIN users u ON u.id = al.user_id ORDER BY al.created_at DESC LIMIT 500")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Auth Logs</title>
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
</head>

<body>
    <div class="container py-4">
        <h3>Auth Logs</h3>
        <a href="index.php" class="btn btn-outline-primary mb-3">Dashboard</a>
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Event</th>
                    <th>IP</th>
                    <th>User-Agent</th>
                    <th>Meta</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $l): ?>
                <tr>
                    <td><?php echo $l['created_at']; ?></td>
                    <td><?php echo htmlspecialchars($l['username'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($l['event']); ?></td>
                    <td><?php echo htmlspecialchars($l['ip']); ?></td>
                    <td><?php echo htmlspecialchars(substr($l['user_agent'], 0, 80)); ?></td>
                    <td><?php echo htmlspecialchars($l['meta'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>