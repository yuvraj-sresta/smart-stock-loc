<?php
/**
 * inventory/edit.php
 * Edit existing product. Admin only.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireRole('admin');

$db     = getDB();
$errors = [];
$id     = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$id) { redirect(BASE_URL . '/inventory/index.php'); }

// Load product
$stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) {
    setFlash('danger', 'Product not found.');
    redirect(BASE_URL . '/inventory/index.php');
}

$categories = $db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$suppliers  = $db->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'name'            => clean($_POST['name']            ?? ''),
        'sku'             => clean($_POST['sku']             ?? ''),
        'category_id'     => (int)($_POST['category_id']    ?? 0),
        'supplier_id'     => (int)($_POST['supplier_id']    ?? 0),
        'price'           => $_POST['price']                ?? '',
        'min_stock_level' => $_POST['min_stock_level']      ?? '',
        'description'     => clean($_POST['description']    ?? ''),
        'status'          => $_POST['status'] === 'inactive' ? 'inactive' : 'active',
    ];

    if ($formData['name'] === '')  $errors['name']  = 'Product name is required.';
    if ($formData['sku']  === '')  $errors['sku']   = 'SKU is required.';
    if (!is_numeric($formData['price']) || $formData['price'] < 0)
                                   $errors['price'] = 'Enter a valid price.';
    if (!ctype_digit((string)$formData['min_stock_level']) || $formData['min_stock_level'] < 1)
                                   $errors['min_stock_level'] = 'Minimum stock level must be at least 1.';

    // SKU unique check (exclude self)
    if (empty($errors['sku'])) {
        $chk = $db->prepare('SELECT id FROM products WHERE sku = ? AND id != ?');
        $chk->execute([$formData['sku'], $id]);
        if ($chk->fetch()) $errors['sku'] = 'This SKU is already used by another product.';
    }

    if (empty($errors)) {
        $stmt = $db->prepare(
            'UPDATE products SET name=?, sku=?, category_id=?, supplier_id=?, price=?,
             min_stock_level=?, description=?, status=?, updated_at=NOW()
             WHERE id=?'
        );
        $stmt->execute([
            $formData['name'], $formData['sku'],
            $formData['category_id'] ?: null,
            $formData['supplier_id'] ?: null,
            $formData['price'], $formData['min_stock_level'],
            $formData['description'], $formData['status'], $id,
        ]);
        setFlash('success', 'Product updated successfully.');
        redirect(BASE_URL . '/inventory/view.php?id=' . $id);
    }
    // Re-populate from POST on error
    $product = array_merge($product, $formData);
}

$pageTitle = 'Edit Product';
$activeNav = 'inventory';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main">
    <div class="sidebar-overlay"></div>
    <div class="topbar">
        <div>
            <div class="topbar-title">Edit Product</div>
            <div class="topbar-sub"><?= h($product['name']) ?></div>
        </div>
        <div class="topbar-actions">
            <button class="hamburger">☰</button>
            <a href="<?= BASE_URL ?>/inventory/view.php?id=<?= $id ?>" class="btn btn-ghost btn-sm">← Back</a>
        </div>
    </div>

    <div class="page-content">
        <div class="card" style="max-width:780px;">
            <div class="card-header"><span class="card-title">Edit Product Details</span></div>
            <div class="card-body">
                <form method="POST" action="" novalidate>
                    <input type="hidden" name="id" value="<?= $id ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Product Name *</label>
                            <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'error' : '' ?>"
                                   value="<?= h($product['name']) ?>">
                            <?php if (isset($errors['name'])): ?><div class="form-error"><?= h($errors['name']) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">SKU *</label>
                            <input type="text" name="sku" class="form-control <?= isset($errors['sku']) ? 'error' : '' ?>"
                                   value="<?= h($product['sku']) ?>">
                            <?php if (isset($errors['sku'])): ?><div class="form-error"><?= h($errors['sku']) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-control">
                                <option value="0">— Select Category —</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" class="form-control">
                                <option value="0">— Select Supplier —</option>
                                <?php foreach ($suppliers as $sup): ?>
                                    <option value="<?= $sup['id'] ?>" <?= $product['supplier_id'] == $sup['id'] ? 'selected' : '' ?>><?= h($sup['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Price (AUD) *</label>
                            <input type="number" name="price" step="0.01" min="0"
                                   class="form-control <?= isset($errors['price']) ? 'error' : '' ?>"
                                   value="<?= h($product['price']) ?>">
                            <?php if (isset($errors['price'])): ?><div class="form-error"><?= h($errors['price']) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="active"   <?= $product['status'] === 'active'   ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="max-width:340px;">
                        <label class="form-label">Minimum Stock Level *</label>
                        <input type="number" name="min_stock_level" min="1"
                               class="form-control <?= isset($errors['min_stock_level']) ? 'error' : '' ?>"
                               value="<?= h($product['min_stock_level']) ?>">
                        <div class="form-hint">Note: Use Update Stock to change current stock quantity.</div>
                        <?php if (isset($errors['min_stock_level'])): ?><div class="form-error"><?= h($errors['min_stock_level']) ?></div><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control"><?= h($product['description']) ?></textarea>
                    </div>

                    <div style="display:flex;gap:10px;margin-top:6px;">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="<?= BASE_URL ?>/inventory/view.php?id=<?= $id ?>" class="btn btn-ghost">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
