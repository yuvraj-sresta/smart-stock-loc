<?php
require_once __DIR__ . '/../config/constants.php';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 – Smart Stock</title>
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
    <div class="error-code">404</div>
    <div class="error-title">Page Not Found</div>
    <p class="error-msg">The page you're looking for doesn't exist or has been moved.</p>
    <a href="<?= BASE_URL ?>/dashboard/index.php" class="btn btn-primary">← Back to Dashboard</a>
</div>
</body>
</html>
