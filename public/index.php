<?php
require_once __DIR__ . '/../app/session.php';
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars(constant('APP_NAME') ?? 'MFA-App'); ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
    <link href="assets/css/app.css" rel="stylesheet" />
</head>


<body
    style="background: linear-gradient(135deg, #0d6efd 0%, #212529 100%); font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, 'Liberation Sans', sans-serif; min-height: 100vh;">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php"><?php echo APP_NAME; ?></a>
            <div>
                <?php if (is_logged_in()): ?>
                    <a class="btn btn-outline-primary" href="dashboard.php">Dashboard</a>
                    <a class="btn btn-outline-secondary" href="logout.php">Logout</a>
                <?php else: ?>
                    <a class="btn btn-primary" href="login.php">Login</a>
                    <a class="btn btn-outline-primary" href="register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container d-flex flex-column justify-content-center align-items-center" style="min-height: 90vh;">
        <div class="row w-100 justify-content-center align-items-center flex-grow-1">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card shadow-lg border-0 bg-dark text-light rounded-4">
                    <div class="card-body p-5">
                        <h1 class="card-title fw-bold mb-4 text-center display-5" style="letter-spacing: 1px;">
                            <i class="bi bi-shield-lock-fill text-primary me-2"></i>
                            <span style="font-size:2.2rem;">Multi-Factor Authentication Web Application</span>
                        </h1>
                        <hr class="border-primary opacity-75 mb-4">
                        <ul class="list-unstyled fs-5 mb-0">
                            <li class="mb-3">
                                <span class="fw-semibold text-primary"><i class="bi bi-person-badge me-2"></i>Student
                                    Name:</span> Sanni RidWanullahi Abiodun
                            </li>
                            <li class="mb-3">
                                <span class="fw-semibold text-primary"><i class="bi bi-hash me-2"></i>Matric
                                    Number:</span> <span class="text-info">CY/HND/F23/0039</span>
                            </li>
                            <li class="mb-3">
                                <span class="fw-semibold text-primary"><i
                                        class="bi bi-mortarboard-fill me-2"></i>Department:</span> Cybersecurity
                                Security and Data Protection
                            </li>
                            <li class="mb-3">
                                <span class="fw-semibold text-primary"><i class="bi bi-building me-2"></i>School:</span>
                                <span class="text-info">Computing</span>
                            </li>
                            <li class="mb-3">
                                <span class="fw-semibold text-primary"><i
                                        class="bi bi-calendar-event me-2"></i>Year:</span> 2025
                            </li>
                            <li>
                                <span class="fw-semibold text-primary"><i
                                        class="bi bi-person-vcard me-2"></i>Supervisor:</span> <span
                                    class="text-info">Dr. Mrs Taofeq</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>