<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession(); requireLogin();
header('Content-Type: application/json');
$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['products'=>[]]); exit; }
$db = getDB();
$stmt = $db->prepare('SELECT id,name,sku,price,stock_qty,min_stock_level FROM products WHERE supplier_id=? AND status="active" ORDER BY name ASC');
$stmt->execute([$id]);
echo json_encode(['products' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
