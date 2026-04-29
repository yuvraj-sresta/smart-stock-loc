<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireRole('admin');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(BASE_URL . '/categories/index.php');

$stmt = $db->prepare('SELECT name FROM categories WHERE id = ?');
$stmt->execute([$id]);
$cat = $stmt->fetch();
if (!$cat) { setFlash('danger','Category not found.'); redirect(BASE_URL . '/categories/index.php'); }

$check = $db->prepare('SELECT COUNT(*) FROM products WHERE category_id = ? AND status = "active"');
$check->execute([$id]);
if ($check->fetchColumn() > 0) {
    setFlash('danger', 'Cannot delete "' . $cat['name'] . '" — it has active products assigned to it.');
    redirect(BASE_URL . '/categories/index.php');
}

$db->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
setFlash('success', 'Category "' . $cat['name'] . '" deleted.');
redirect(BASE_URL . '/categories/index.php');
