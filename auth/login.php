<?php
/**
 * auth/login.php
 * Login page. Handles GET (show form) and POST (process login).
 * Security: prepared statements, password_verify(), session regeneration.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();

// Already logged in → go to dashboard
if (isLoggedIn()) {
    redirect(DASHBOARD_URL);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare(
            'SELECT id, name, password_hash, role, is_active FROM users WHERE username = ? LIMIT 1'
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $user['is_active'] && password_verify($password, $user['password_hash'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            $_SESSION[SESSION_USER_ID]   = $user['id'];
            $_SESSION[SESSION_USER_NAME] = $user['name'];
            $_SESSION[SESSION_USER_ROLE] = $user['role'];

            redirect(DASHBOARD_URL);
        } else {
            // Generic error — do not reveal whether username or password is wrong
            $error = 'Invalid username or password.';
            // Small sleep to slow brute force
            sleep(1);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Smart Stock</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
</head>
<body class="auth-page">

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo-wrap">📦</div>
            <div class="auth-title">Smart Stock</div>
            <div class="auth-subtitle">Inventory Management System</div>
        </div>

        <div class="auth-body">
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" novalidate id="loginForm">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="Enter your username"
                        value="<?= h($_POST['username'] ?? '') ?>"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >
                        <span class="input-icon" id="togglePwd" title="Show/hide password">👁</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    Sign In
                </button>
            </form>

            <div class="auth-footer">
                Smart Stock v1.0 &nbsp;|&nbsp; ICT313 Capstone Project
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
document.getElementById('togglePwd').addEventListener('click', function () {
    const pwd = document.getElementById('password');
    pwd.type = pwd.type === 'password' ? 'text' : 'password';
    this.textContent = pwd.type === 'password' ? '👁' : '🙈';
});

// Basic client-side validation (server still validates too)
document.getElementById('loginForm').addEventListener('submit', function (e) {
    const u = document.getElementById('username').value.trim();
    const p = document.getElementById('password').value;
    if (!u || !p) {
        e.preventDefault();
        alert('Please fill in all fields.');
    }
});
</script>
</body>
</html>
