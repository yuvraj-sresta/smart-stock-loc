<?php
/**
 * includes/functions.php
 * Shared utility functions used across the application.
 */

/**
 * Safely output HTML-escaped string.
 */
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect and exit.
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Set a one-time flash message in session.
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieve and clear the flash message.
 */
function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Format a datetime string for display.
 */
function formatDate(string $datetime, string $format = 'd M Y, g:i A'): string {
    return date($format, strtotime($datetime));
}

/**
 * Format currency in AUD.
 */
function formatCurrency(float $amount): string {
    return '$' . number_format($amount, 2);
}

/**
 * Return CSS class for stock level badge.
 */
function stockBadgeClass(int $qty, int $minLevel): string {
    if ($qty === 0)           return 'badge-danger';
    if ($qty <= $minLevel)    return 'badge-warning';
    return 'badge-success';
}

/**
 * Return label for stock level badge.
 */
function stockBadgeLabel(int $qty, int $minLevel): string {
    if ($qty === 0)        return 'Out of Stock';
    if ($qty <= $minLevel) return 'Low Stock';
    return 'In Stock';
}

/**
 * Sanitize and trim input string.
 */
function clean(string $input): string {
    return trim(strip_tags($input));
}

/**
 * Return CSS badge class for transaction change type.
 */
function txBadgeClass(string $type): string {
    return match($type) {
        'restock'    => 'badge-success',
        'sale'       => 'badge-info',
        'damage'     => 'badge-danger',
        'return'     => 'badge-warning',
        'adjustment' => 'badge-neutral',
        default      => 'badge-neutral',
    };
}
