<?php
/**
 * errors/403.php – Unauthorized access page.
 */
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
http_response_code(403);
$pageTitle = '403 – Access Denied';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 – Smart Stock</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body { display:flex; align-items:center; justify-content:center; min-height:100vh; }
        .error-box { text-align:center; max-width:400px; padding:40px 20px; }
        .error-code { font-size:5rem; font-weight:700; color:#e2e8f0; line-height:1; font-family:var(--font-mono); }
        .error-title { font-size:1.3rem; font-weight:700; margin:12px 0 8px; }
        .error-msg { color:var(--text-secondary); font-size:.9rem; margin-bottom:24px; }
    </style>
</head>
<body>
<div class="error-box">
    <div class="error-code">403</div>
    <div class="error-title">Access Denied</div>
    <p class="error-msg">You don't have permission to view this page. Please contact your administrator if you believe this is an error.</p>
    <a href="<?= DASHBOARD_URL ?>" class="btn btn-primary">← Back to Dashboard</a>
</div>
</body>
</html>
