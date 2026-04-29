<?php
/**
 * inventory/update_stock.php
 * Update stock quantity and log the transaction.
 * Accessible by: Admin + Staff
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireLogin(); // Staff + Admin

$db     = getDB();
$errors = [];
$id     = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) redirect(BASE_URL . '/inventory/index.php');

$stmt = $db->prepare('SELECT * FROM products WHERE id = ? AND status = "active"');
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) { setFlash('danger','Product not found.'); redirect(BASE_URL . '/inventory/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $changeType = clean($_POST['change_type'] ?? '');
    $quantity   = (int)($_POST['quantity'] ?? 0);
    $notes      = clean($_POST['notes'] ?? '');

    $validTypes = ['restock','sale','adjustment','damage','return'];

    if (!in_array($changeType, $validTypes))    $errors['change_type'] = 'Please select a valid transaction type.';
    if ($quantity === 0)                         $errors['quantity']    = 'Quantity must not be zero.';
    if ($quantity < -9999 || $quantity > 99999)  $errors['quantity']    = 'Quantity is out of allowed range.';

    // Stock can't go below 0
    $newQty = $product['stock_qty'] + $quantity;
    if ($newQty < 0) $errors['quantity'] = 'Stock cannot go below 0. Current stock is ' . $product['stock_qty'] . '.';

    if (empty($errors)) {
        // Update product stock
        $upd = $db->prepare('UPDATE products SET stock_qty = ?, updated_at = NOW() WHERE id = ?');
        $upd->execute([$newQty, $id]);

        // Log transaction
        $log = $db->prepare(
            'INSERT INTO stock_transactions (product_id, user_id, change_type, quantity_changed, previous_qty, new_qty, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $log->execute([
            $id, currentUserId(), $changeType, $quantity,
            $product['stock_qty'], $newQty, $notes,
        ]);

        setFlash('success', 'Stock updated: ' . $product['name'] . ' is now ' . $newQty . ' units.');
        redirect(BASE_URL . '/inventory/view.php?id=' . $id);
    }
}

$pageTitle = 'Update Stock';
$activeNav = 'inventory';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main">
    <div class="sidebar-overlay"></div>
    <div class="topbar">
        <div>
            <div class="topbar-title">Update Stock</div>
            <div class="topbar-sub"><?= h($product['name']) ?> — Current: <strong><?= $product['stock_qty'] ?></strong> units</div>
        </div>
        <div class="topbar-actions">
            <button class="hamburger">☰</button>
            <a href="<?= BASE_URL ?>/inventory/view.php?id=<?= $id ?>" class="btn btn-ghost btn-sm">← Back</a>
        </div>
    </div>

    <div class="page-content">
        <div class="card" style="max-width:520px;">
            <div class="card-header">
                <span class="card-title">Stock Adjustment</span>
                <span class="badge <?= stockBadgeClass($product['stock_qty'], $product['min_stock_level']) ?>">
                    <?= stockBadgeLabel($product['stock_qty'], $product['min_stock_level']) ?>
                </span>
            </div>
            <div class="card-body">

                <!-- Current stock display -->
                <div class="stock-display">
                    <div class="stock-current">
                        <div style="font-size:.75rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;">Current Stock</div>
                        <div style="font-size:2.5rem;font-weight:700;font-family:var(--font-mono);color:var(--text-primary);line-height:1.2;"><?= $product['stock_qty'] ?></div>
                        <div style="font-size:.78rem;color:var(--text-muted);">Min level: <?= $product['min_stock_level'] ?></div>
                    </div>
                </div>

                <form method="POST" action="" novalidate>
                    <input type="hidden" name="id" value="<?= $id ?>">

                    <div class="form-group">
                        <label class="form-label">Transaction Type *</label>
                        <select name="change_type" class="form-control <?= isset($errors['change_type']) ? 'error' : '' ?>">
                            <option value="">— Select Type —</option>
                            <option value="restock"    <?= ($_POST['change_type'] ?? '') === 'restock'    ? 'selected' : '' ?>>📦 Restock (add stock)</option>
                            <option value="sale"       <?= ($_POST['change_type'] ?? '') === 'sale'       ? 'selected' : '' ?>>🛒 Sale (reduce stock)</option>
                            <option value="adjustment" <?= ($_POST['change_type'] ?? '') === 'adjustment' ? 'selected' : '' ?>>✏️ Adjustment (manual fix)</option>
                            <option value="damage"     <?= ($_POST['change_type'] ?? '') === 'damage'     ? 'selected' : '' ?>>💔 Damage (reduce stock)</option>
                            <option value="return"     <?= ($_POST['change_type'] ?? '') === 'return'     ? 'selected' : '' ?>>↩️ Return (add back stock)</option>
                        </select>
                        <?php if (isset($errors['change_type'])): ?><div class="form-error"><?= h($errors['change_type']) ?></div><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Quantity *</label>
                        <input type="number" name="quantity"
                               class="form-control <?= isset($errors['quantity']) ? 'error' : '' ?>"
                               value="<?= h($_POST['quantity'] ?? '') ?>"
                               placeholder="Use positive to add, negative to reduce (e.g. -5)">
                        <div class="form-hint">Use a positive number to add stock, negative to reduce.</div>
                        <?php if (isset($errors['quantity'])): ?><div class="form-error"><?= h($errors['quantity']) ?></div><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" placeholder="Optional reason for this adjustment…"><?= h($_POST['notes'] ?? '') ?></textarea>
                    </div>

                    <div style="display:flex;gap:10px;margin-top:6px;">
                        <button type="submit" class="btn btn-primary">Confirm Update</button>
                        <a href="<?= BASE_URL ?>/inventory/view.php?id=<?= $id ?>" class="btn btn-ghost">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
