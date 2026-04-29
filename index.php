<?php
/**
 * index.php
 * Project root. Redirect to dashboard if logged in, else to login.
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth_check.php';

startSecureSession();

if (isLoggedIn()) {
    header('Location: ' . DASHBOARD_URL);
} else {
    header('Location: ' . LOGIN_URL);
}
exit;
