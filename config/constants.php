<?php
/**
 * config/constants.php
 * Global application constants.
 */

define('APP_NAME',    'Smart Stock');
define('APP_VERSION', '1.0.0');

// Session key names
define('SESSION_USER_ID',   'ss_user_id');
define('SESSION_USER_NAME', 'ss_user_name');
define('SESSION_USER_ROLE', 'ss_user_role');

// Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_STAFF', 'staff');

// Redirect paths (relative to project root under XAMPP htdocs)
define('BASE_URL', '/smart-stock-loc');
define('LOGIN_URL', BASE_URL . '/auth/login.php');
define('DASHBOARD_URL', BASE_URL . '/dashboard/index.php');
