<?php
/**
 * Login Page - RBI Engineering Suite
 */
require_once __DIR__ . '/config/app.php';

// Check database connectivity before proceeding
$dbError = '';
try {
    $auth = new Auth();
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

if (!$dbError && $auth->isLoggedIn()) {
    redirect(BASE_URL . '/dashboard.php');
}

$error = '';

if (!$dbError && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf     = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrf)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $user = $auth->login($email, $password);
        if ($user) {
            if (!empty($_POST['remember_me'])) {
                ini_set('session.cookie_lifetime', 86400 * 30);
            }
            redirect(BASE_URL . '/dashboard.php');
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RBI Engineering Suite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f2440 0%, #1a3a5c 50%, #2c5f8a 100%);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .login-container { width: 100%; max-width: 440px; padding: 15px; }
        .login-card {
            background: #fff; border-radius: 16px; padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
        }
        .brand-icon {
            width: 64px; height: 64px; background: linear-gradient(135deg, #3498db, #2c5f8a);
            border-radius: 16px; display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; color: #fff; margin: 0 auto 16px;
        }
        .form-control:focus { border-color: #3498db; box-shadow: 0 0 0 .2rem rgba(52,152,219,.25); }
        .btn-login {
            background: linear-gradient(135deg, #1a3a5c, #2c5f8a);
            border: none; padding: 12px; font-weight: 600; font-size: .95rem;
            border-radius: 10px; transition: transform .2s;
        }
        .btn-login:hover { transform: translateY(-1px); background: linear-gradient(135deg, #0f2440, #1a3a5c); }
        .bg-particles {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            overflow: hidden; z-index: -1;
        }
        .bg-particles div {
            position: absolute; border-radius: 50%; background: rgba(255,255,255,.05);
            animation: float 20s infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
        }
    </style>
</head>
<body>
    <div class="bg-particles">
        <div style="width:80px;height:80px;top:10%;left:10%;animation-delay:0s"></div>
        <div style="width:60px;height:60px;top:70%;left:80%;animation-delay:5s"></div>
        <div style="width:100px;height:100px;top:40%;left:50%;animation-delay:10s"></div>
        <div style="width:40px;height:40px;top:80%;left:20%;animation-delay:3s"></div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="text-center mb-4">
                <div class="brand-icon"><i class="bi bi-shield-check"></i></div>
                <h4 class="fw-bold mb-1" style="color:#1a3a5c">RBI Engineering Suite</h4>
                <p class="text-muted small">Risk-Based Inspection Management Platform</p>
            </div>

            <?php if ($dbError): ?>
            <div class="alert alert-danger small">
                <h6 class="alert-heading"><i class="bi bi-database-x me-1"></i> Database Connection Failed</h6>
                <p class="mb-2">MySQL is not running. Please start it:</p>
                <ol class="mb-2">
                    <li>Open <strong>XAMPP Control Panel</strong></li>
                    <li>Click <strong>Start</strong> next to <strong>MySQL</strong></li>
                    <li>Run the automated migration: open your browser and go to <a href="migrate.php" target="_blank"><code>http://localhost/rbi/migrate.php</code></a> or run <code>php migrate.php</code> in your terminal.</li>
                    <li>Refresh this page</li>
                </ol>
                <a href="" class="btn btn-sm btn-outline-danger"><i class="bi bi-arrow-clockwise me-1"></i>Retry Connection</a>
            </div>
            <?php else: ?>

            <?php if ($error): ?>
            <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle me-1"></i> <?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="you@company.com"
                               value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember_me" id="rememberMe">
                        <label class="form-check-label small" for="rememberMe">Remember me</label>
                    </div>
                    <a href="#" class="small text-decoration-none">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>
            <?php endif; ?>

            <div class="text-center mt-4">
                <small class="text-muted">Secured by API 580/581 Compliance Engine</small>
            </div>
        </div>
        <p class="text-center text-white-50 mt-3 small">&copy; <?= date('Y') ?> RBI Engineering Suite v<?= APP_VERSION ?></p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
