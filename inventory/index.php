<?php
/**
 * inventory/index.php
 * Product inventory list with search, category filter, and low-stock filter.
 * Accessible by: Admin + Staff
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireLogin();

$db = getDB();

// ── Filters from GET
$search    = clean($_GET['search']    ?? '');
$catFilter = (int)($_GET['category']  ?? 0);
$filter    = clean($_GET['filter']    ?? '');  // 'low' for low stock only

// ── Build query dynamically (still using prepared statements)
$where  = ['p.status = "active"'];
$params = [];

if ($search !== '') {
    $where[]  = '(p.name LIKE ? OR p.sku LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($catFilter > 0) {
    $where[]  = 'p.category_id = ?';
    $params[] = $catFilter;
}
if ($filter === 'low') {
    $where[] = 'p.stock_qty <= p.min_stock_level';
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$stmt = $db->prepare(
    "SELECT p.id, p.name, p.sku, p.price, p.stock_qty, p.min_stock_level, p.status,
            c.name AS category_name, s.name AS supplier_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN suppliers  s ON p.supplier_id  = s.id
     $whereSQL
     ORDER BY p.name ASC"
);
$stmt->execute($params);
$products = $stmt->fetchAll();

// ── Categories for filter dropdown
$categories = $db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

$pageTitle = 'Inventory';
$activeNav = 'inventory';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main">
    <div class="sidebar-overlay"></div>

    <div class="topbar">
        <div>
            <div class="topbar-title">Inventory</div>
            <div class="topbar-sub"><?= count($products) ?> product<?= count($products) !== 1 ? 's' : '' ?> found</div>
        </div>
        <div class="topbar-actions">
            <button class="hamburger">☰</button>
            <?php if (isAdmin()): ?>
                <a href="<?= BASE_URL ?>/inventory/add.php" class="btn btn-primary btn-sm">+ Add Product</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="page-content">

        <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?= h($flash['type']) ?>" data-auto-dismiss><?= h($flash['message']) ?></div>
        <?php endif; ?>

        <!-- ── Filter Bar ── -->
        <form method="GET" action="" class="filter-bar">
            <input
                type="text"
                name="search"
                class="form-control"
                placeholder="Search by name or SKU…"
                value="<?= h($search) ?>"
            >
            <select name="category" class="form-control" style="max-width:180px;">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $catFilter == $cat['id'] ? 'selected' : '' ?>>
                        <?= h($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="filter" class="form-control" style="max-width:160px;">
                <option value="">All Stock Levels</option>
                <option value="low" <?= $filter === 'low' ? 'selected' : '' ?>>Low Stock Only</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <?php if ($search || $catFilter || $filter): ?>
                <a href="<?= BASE_URL ?>/inventory/index.php" class="btn btn-ghost btn-sm">Clear</a>
            <?php endif; ?>
        </form>

        <!-- ── Products Table ── -->
        <div class="card">
            <div class="table-wrap">
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📦</div>
                        <div class="empty-state-msg">No products found. Try adjusting your filters.</div>
                    </div>
                <?php else: ?>
                    <table id="productsTable">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th style="text-align:right;">Price</th>
                                <th style="text-align:center;">Stock</th>
                                <th style="text-align:center;">Min Level</th>
                                <th style="text-align:center;">Status</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                                <?php
                                    $outOfStock = $p['stock_qty'] == 0;
                                    $isLow      = $p['stock_qty'] <= $p['min_stock_level'];
                                    $rowClass   = $outOfStock ? 'out-of-stock' : ($isLow ? 'low-stock' : '');
                                ?>
                                <tr class="<?= $rowClass ?>">
                                    <td class="td-mono"><?= h($p['sku']) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/inventory/view.php?id=<?= $p['id'] ?>" style="font-weight:600;">
                                            <?= h($p['name']) ?>
                                        </a>
                                    </td>
                                    <td class="td-muted"><?= h($p['category_name'] ?? '—') ?></td>
                                    <td class="td-mono" style="text-align:right;"><?= formatCurrency($p['price']) ?></td>
                                    <td style="text-align:center;">
                                        <span style="font-family:var(--font-mono);font-weight:700;color:<?= $outOfStock ? 'var(--danger)' : ($isLow ? 'var(--warning)' : 'var(--success)') ?>;">
                                            <?= $p['stock_qty'] ?>
                                        </span>
                                    </td>
                                    <td class="td-mono" style="text-align:center;"><?= $p['min_stock_level'] ?></td>
                                    <td style="text-align:center;">
                                        <span class="badge <?= stockBadgeClass($p['stock_qty'], $p['min_stock_level']) ?>">
                                            <?= stockBadgeLabel($p['stock_qty'], $p['min_stock_level']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align:center;">
                                        <div class="table-actions" style="justify-content:center;">
                                            <a href="<?= BASE_URL ?>/inventory/view.php?id=<?= $p['id'] ?>"
                                               class="btn btn-ghost btn-sm" title="View">👁</a>
                                            <a href="<?= BASE_URL ?>/inventory/update_stock.php?id=<?= $p['id'] ?>"
                                               class="btn btn-ghost btn-sm" title="Update Stock">⇅</a>
                                            <?php if (isAdmin()): ?>
                                                <a href="<?= BASE_URL ?>/inventory/edit.php?id=<?= $p['id'] ?>"
                                                   class="btn btn-ghost btn-sm" title="Edit">✏️</a>
                                                <a href="<?= BASE_URL ?>/inventory/delete.php?id=<?= $p['id'] ?>"
                                                   class="btn btn-danger btn-sm" title="Delete"
                                                   data-confirm="Delete '<?= h($p['name']) ?>'? This cannot be undone.">🗑</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
