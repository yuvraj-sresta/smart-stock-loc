<?php
/**
 * users/delete.php
 * Delete a user. Admin only. Cannot delete own account.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireRole('admin');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(BASE_URL . '/users/index.php');

// Cannot delete self
if ($id === currentUserId()) {
    setFlash('danger', 'You cannot delete your own account.');
    redirect(BASE_URL . '/users/index.php');
}

$stmt = $db->prepare('SELECT name FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) { setFlash('danger','User not found.'); redirect(BASE_URL . '/users/index.php'); }

$db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
setFlash('success', 'User "' . $user['name'] . '" deleted.');
redirect(BASE_URL . '/users/index.php');
