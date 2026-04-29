<?php
/**
 * auth/logout.php
 * Destroys session and redirects to login.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';

startSecureSession();
$_SESSION = [];
session_destroy();
header('Location: ' . LOGIN_URL);
exit;
