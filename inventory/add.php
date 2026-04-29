<?php
/**
 * inventory/add.php
 * Add a new product. Admin only.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireRole('admin');

$db         = getDB();
$errors     = [];
$formData   = [];

// ── Load dropdowns
$categories = $db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$suppliers  = $db->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'name'            => clean($_POST['name']            ?? ''),
        'sku'             => clean($_POST['sku']             ?? ''),
        'category_id'     => (int)($_POST['category_id']    ?? 0),
        'supplier_id'     => (int)($_POST['supplier_id']    ?? 0),
        'price'           => $_POST['price']                ?? '',
        'stock_qty'       => $_POST['stock_qty']            ?? '',
        'min_stock_level' => $_POST['min_stock_level']      ?? '',
        'description'     => clean($_POST['description']    ?? ''),
        'status'          => $_POST['status'] === 'inactive' ? 'inactive' : 'active',
    ];

    // Validate
    if ($formData['name'] === '')           $errors['name']            = 'Product name is required.';
    if ($formData['sku']  === '')           $errors['sku']             = 'SKU is required.';
    if (!is_numeric($formData['price']) || $formData['price'] < 0)
                                            $errors['price']           = 'Enter a valid price.';
    if (!ctype_digit((string)$formData['stock_qty']))
                                            $errors['stock_qty']       = 'Stock quantity must be a whole number.';
    if (!ctype_digit((string)$formData['min_stock_level']) || $formData['min_stock_level'] < 1)
                                            $errors['min_stock_level'] = 'Minimum stock level must be at least 1.';

    // Check SKU uniqueness
    if (empty($errors['sku'])) {
        $chk = $db->prepare('SELECT id FROM products WHERE sku = ?');
        $chk->execute([$formData['sku']]);
        if ($chk->fetch()) $errors['sku'] = 'This SKU already exists.';
    }

    if (empty($errors)) {
        $stmt = $db->prepare(
            'INSERT INTO products (name, sku, category_id, supplier_id, price, stock_qty, min_stock_level, description, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $formData['name'],
            $formData['sku'],
            $formData['category_id'] ?: null,
            $formData['supplier_id'] ?: null,
            $formData['price'],
            $formData['stock_qty'],
            $formData['min_stock_level'],
            $formData['description'],
            $formData['status'],
        ]);
        setFlash('success', 'Product "' . $formData['name'] . '" added successfully.');
        redirect(BASE_URL . '/inventory/index.php');
    }
}

$pageTitle = 'Add Product';
$activeNav = 'inventory';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main">
    <div class="sidebar-overlay"></div>

    <div class="topbar">
        <div>
            <div class="topbar-title">Add Product</div>
            <div class="topbar-sub">Fill in product details below</div>
        </div>
        <div class="topbar-actions">
            <button class="hamburger">☰</button>
            <a href="<?= BASE_URL ?>/inventory/index.php" class="btn btn-ghost btn-sm">← Back</a>
        </div>
    </div>

    <div class="page-content">
        <div class="card" style="max-width:780px;">
            <div class="card-header">
                <span class="card-title">Product Information</span>
            </div>
            <div class="card-body">
                <form method="POST" action="" novalidate>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="name">Product Name *</label>
                            <input type="text" id="name" name="name" class="form-control <?= isset($errors['name']) ? 'error' : '' ?>"
                                   value="<?= h($formData['name'] ?? '') ?>" placeholder="e.g. Spring Water 600ml">
                            <?php if (isset($errors['name'])): ?>
                                <div class="form-error"><?= h($errors['name']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="sku">SKU *</label>
                            <input type="text" id="sku" name="sku" class="form-control <?= isset($errors['sku']) ? 'error' : '' ?>"
                                   value="<?= h($formData['sku'] ?? '') ?>" placeholder="e.g. BEV-001">
                            <?php if (isset($errors['sku'])): ?>
                                <div class="form-error"><?= h($errors['sku']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="category_id">Category</label>
                            <select id="category_id" name="category_id" class="form-control">
                                <option value="0">— Select Category —</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($formData['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                                        <?= h($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="supplier_id">Supplier</label>
                            <select id="supplier_id" name="supplier_id" class="form-control">
                                <option value="0">— Select Supplier —</option>
                                <?php foreach ($suppliers as $sup): ?>
                                    <option value="<?= $sup['id'] ?>" <?= ($formData['supplier_id'] ?? 0) == $sup['id'] ? 'selected' : '' ?>>
                                        <?= h($sup['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="price">Price (AUD) *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0"
                                   class="form-control <?= isset($errors['price']) ? 'error' : '' ?>"
                                   value="<?= h($formData['price'] ?? '') ?>" placeholder="0.00">
                            <?php if (isset($errors['price'])): ?>
                                <div class="form-error"><?= h($errors['price']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="active"   <?= ($formData['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($formData['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="stock_qty">Initial Stock Quantity *</label>
                            <input type="number" id="stock_qty" name="stock_qty" min="0"
                                   class="form-control <?= isset($errors['stock_qty']) ? 'error' : '' ?>"
                                   value="<?= h($formData['stock_qty'] ?? '') ?>" placeholder="0">
                            <?php if (isset($errors['stock_qty'])): ?>
                                <div class="form-error"><?= h($errors['stock_qty']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="min_stock_level">Minimum Stock Level *</label>
                            <input type="number" id="min_stock_level" name="min_stock_level" min="1"
                                   class="form-control <?= isset($errors['min_stock_level']) ? 'error' : '' ?>"
                                   value="<?= h($formData['min_stock_level'] ?? '') ?>" placeholder="5">
                            <div class="form-hint">Alert triggers when stock falls at or below this number.</div>
                            <?php if (isset($errors['min_stock_level'])): ?>
                                <div class="form-error"><?= h($errors['min_stock_level']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" placeholder="Optional product notes…"><?= h($formData['description'] ?? '') ?></textarea>
                    </div>

                    <div style="display:flex;gap:10px;margin-top:6px;">
                        <button type="submit" class="btn btn-primary">Save Product</button>
                        <a href="<?= BASE_URL ?>/inventory/index.php" class="btn btn-ghost">Cancel</a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
