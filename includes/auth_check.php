<?php
/**
 * includes/auth_check.php
 * Include at the top of every protected page.
 * Usage:
 *   require_once __DIR__ . '/../includes/auth_check.php';
 *   requireLogin();           // any logged-in user
 *   requireRole('admin');     // admin only
 */

require_once __DIR__ . '/../config/constants.php';

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false,   // set true when on HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function isLoggedIn(): bool {
    startSecureSession();
    return !empty($_SESSION[SESSION_USER_ID]);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . LOGIN_URL);
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    if ($_SESSION[SESSION_USER_ROLE] !== $role) {
        header('Location: ' . BASE_URL . '/errors/403.php');
        exit;
    }
}

function currentUserRole(): string {
    return $_SESSION[SESSION_USER_ROLE] ?? '';
}

function currentUserId(): int {
    return (int)($_SESSION[SESSION_USER_ID] ?? 0);
}

function currentUserName(): string {
    return $_SESSION[SESSION_USER_NAME] ?? 'Unknown';
}

function isAdmin(): bool {
    return currentUserRole() === ROLE_ADMIN;
}
