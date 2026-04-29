<?php
/**
 * inventory/delete.php
 * Soft-delete (set status=inactive) or hard delete. Admin only.
 * Triggered via GET with data-confirm JS dialog.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireRole('admin');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(BASE_URL . '/inventory/index.php');

$stmt = $db->prepare('SELECT name FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('danger', 'Product not found.');
    redirect(BASE_URL . '/inventory/index.php');
}

// Soft delete — set status to inactive (preserves transaction history)
$del = $db->prepare('UPDATE products SET status = "inactive", updated_at = NOW() WHERE id = ?');
$del->execute([$id]);

setFlash('success', '"' . $product['name'] . '" has been removed from inventory.');
redirect(BASE_URL . '/inventory/index.php');
