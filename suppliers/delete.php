<?php
/**
 * suppliers/delete.php
 * Delete supplier. Admin only.
 * Prevents deletion if supplier has active products.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireRole('admin');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(BASE_URL . '/suppliers/index.php');

$stmt = $db->prepare('SELECT name FROM suppliers WHERE id = ?');
$stmt->execute([$id]);
$supplier = $stmt->fetch();
if (!$supplier) { setFlash('danger','Supplier not found.'); redirect(BASE_URL . '/suppliers/index.php'); }

// Check if supplier has active products
$check = $db->prepare('SELECT COUNT(*) FROM products WHERE supplier_id = ? AND status = "active"');
$check->execute([$id]);
$productCount = $check->fetchColumn();

if ($productCount > 0) {
    setFlash('danger', 'Cannot delete "' . $supplier['name'] . '" — they have ' . $productCount . ' active product(s). Reassign those products first.');
    redirect(BASE_URL . '/suppliers/index.php');
}

$del = $db->prepare('DELETE FROM suppliers WHERE id = ?');
$del->execute([$id]);

setFlash('success', 'Supplier "' . $supplier['name'] . '" deleted.');
redirect(BASE_URL . '/suppliers/index.php');
