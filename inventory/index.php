<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession(); requireLogin();

$db = getDB();
$search    = clean($_GET['search']   ?? '');
$catFilter = (int)($_GET['category'] ?? 0);
$filter    = clean($_GET['filter']   ?? '');
$view      = clean($_GET['view']     ?? 'grid'); // grid or list

$where = ['p.status="active"']; $params = [];
if ($search !== '') { $where[] = '(p.name LIKE ? OR p.sku LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($catFilter > 0) { $where[] = 'p.category_id = ?'; $params[] = $catFilter; }
if ($filter === 'low') { $where[] = 'p.stock_qty <= p.min_stock_level'; }

$whereSQL = 'WHERE '.implode(' AND ', $where);
$stmt = $db->prepare("SELECT p.id,p.name,p.sku,p.price,p.stock_qty,p.min_stock_level,p.status,c.name AS category_name,s.name AS supplier_name FROM products p LEFT JOIN categories c ON p.category_id=c.id LEFT JOIN suppliers s ON p.supplier_id=s.id $whereSQL ORDER BY p.name ASC");
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $db->query('SELECT id,name FROM categories ORDER BY name')->fetchAll();

// Summary counts
$total   = count($products);
$lowCnt  = count(array_filter($products, fn($p) => $p['stock_qty'] > 0 && $p['stock_qty'] <= $p['min_stock_level']));
$outCnt  = count(array_filter($products, fn($p) => $p['stock_qty'] == 0));
$okCnt   = $total - $lowCnt - $outCnt;

$pageTitle = 'Inventory'; $activeNav = 'inventory';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main">
<div class="sidebar-overlay"></div>
<div class="topbar">
    <div>
        <div class="topbar-title">Inventory</div>
        <div class="topbar-sub"><?= $total ?> product<?= $total !== 1 ? 's' : '' ?> found</div>
    </div>
    <div class="topbar-actions">
        <button class="hamburger">☰</button>
        <?php if (isAdmin()): ?><a href="<?= BASE_URL ?>/inventory/add.php" class="btn btn-primary btn-sm">+ Add Product</a><?php endif; ?>
    </div>
</div>

<div class="page-content">

<?php $flash=getFlash(); if($flash): ?><div class="alert alert-<?= h($flash['type']) ?>" data-auto-dismiss><?= h($flash['message']) ?></div><?php endif; ?>

<!-- Summary mini-stats -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px;">
    <div style="background:var(--success-bg);border:1px solid var(--success-br);border-radius:var(--rl);padding:14px 18px;display:flex;align-items:center;gap:12px;">
        <div style="width:36px;height:36px;background:var(--success);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;">✅</div>
        <div><div style="font-family:var(--font-d);font-size:1.4rem;font-weight:800;color:var(--success);"><?= $okCnt ?></div><div style="font-size:.72rem;font-weight:600;color:var(--success);text-transform:uppercase;letter-spacing:.06em;">In Stock</div></div>
    </div>
    <div style="background:var(--warning-bg);border:1px solid var(--warning-br);border-radius:var(--rl);padding:14px 18px;display:flex;align-items:center;gap:12px;">
        <div style="width:36px;height:36px;background:var(--warning);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;">⚠️</div>
        <div><div style="font-family:var(--font-d);font-size:1.4rem;font-weight:800;color:var(--warning);"><?= $lowCnt ?></div><div style="font-size:.72rem;font-weight:600;color:var(--warning);text-transform:uppercase;letter-spacing:.06em;">Low Stock</div></div>
    </div>
    <div style="background:var(--danger-bg);border:1px solid var(--danger-br);border-radius:var(--rl);padding:14px 18px;display:flex;align-items:center;gap:12px;">
        <div style="width:36px;height:36px;background:var(--danger);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;">🚫</div>
        <div><div style="font-family:var(--font-d);font-size:1.4rem;font-weight:800;color:var(--danger);"><?= $outCnt ?></div><div style="font-size:.72rem;font-weight:600;color:var(--danger);text-transform:uppercase;letter-spacing:.06em;">Out of Stock</div></div>
    </div>
</div>

<!-- Filters + View toggle -->
<form method="GET" action="">
<div class="filter-bar">
    <input type="text" name="search" class="form-control" placeholder="Search by name or SKU…" value="<?= h($search) ?>">
    <select name="category" class="form-control" style="max-width:170px;">
        <option value="0">All Categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $catFilter==$cat['id']?'selected':'' ?>><?= h($cat['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="filter" class="form-control" style="max-width:160px;">
        <option value="">All Levels</option>
        <option value="low" <?= $filter==='low'?'selected':'' ?>>Low Stock Only</option>
    </select>
    <input type="hidden" name="view" value="<?= h($view) ?>">
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <?php if ($search||$catFilter||$filter): ?><a href="<?= BASE_URL ?>/inventory/index.php?view=<?= h($view) ?>" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>

    <!-- View toggle -->
    <div class="inv-view-toggle" style="margin-left:auto;">
        <button type="submit" name="view" value="grid" class="inv-view-btn <?= $view==='grid'?'active':'' ?>">⊞ Grid</button>
        <button type="submit" name="view" value="list" class="inv-view-btn <?= $view==='list'?'active':'' ?>">☰ List</button>
    </div>
</div>
</form>

<?php if (empty($products)): ?>
    <div class="empty-state card" style="padding:56px;">
        <div class="empty-state-icon">📦</div>
        <div class="empty-state-msg">No products found. Try adjusting your filters.</div>
    </div>

<?php elseif ($view === 'grid'): ?>
<!-- ── CARD GRID VIEW ── -->
<div class="inventory-grid">
    <?php foreach ($products as $i => $p):
        $qty = $p['stock_qty']; $min = $p['min_stock_level'];
        $status = $qty == 0 ? 'out' : ($qty <= $min ? 'low' : 'ok');
        $borderClass = $status === 'out' ? 'out-border' : ($status === 'low' ? 'low-border' : 'ok-border');
    ?>
    <div class="inv-card <?= $borderClass ?>">
        <div class="inv-card-header">
            <div style="flex:1;min-width:0;">
                <div class="inv-card-sku"><?= h($p['sku']) ?></div>
                <div class="inv-card-name"><?= h($p['name']) ?></div>
                <div class="inv-card-category"><?= h($p['category_name'] ?? '—') ?></div>
            </div>
            <span class="badge <?= stockBadgeClass($qty,$min) ?>" style="flex-shrink:0;"><?= stockBadgeLabel($qty,$min) ?></span>
        </div>
        <div class="inv-card-body">
            <div class="inv-card-stock">
                <div class="inv-card-qty <?= $status ?>"><?= $qty ?></div>
                <div class="inv-card-min">min <?= $min ?></div>
            </div>
            <div class="inv-card-price"><?= formatCurrency($p['price']) ?></div>
        </div>
        <div class="inv-card-footer">
            <div class="inv-card-actions">
                <a href="<?= BASE_URL ?>/inventory/view.php?id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="View">👁</a>
                <a href="<?= BASE_URL ?>/inventory/update_stock.php?id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Update Stock">⇅</a>
                <?php if (isAdmin()): ?>
                    <a href="<?= BASE_URL ?>/inventory/edit.php?id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Edit">✏️</a>
                    <a href="<?= BASE_URL ?>/inventory/delete.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm btn-icon" title="Delete" data-confirm="Delete '<?= h($p['name']) ?>'?">🗑</a>
                <?php endif; ?>
            </div>
            <span style="font-size:.7rem;font-family:var(--font-m);color:var(--muted);"><?= h($p['supplier_name'] ?? '—') ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php else: ?>
<!-- ── LIST VIEW ── -->
<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>SKU</th><th>Product Name</th><th>Category</th><th style="text-align:right;">Price</th><th style="text-align:center;">Stock</th><th style="text-align:center;">Min</th><th>Status</th><th style="text-align:center;">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($products as $p):
                $qty=$p['stock_qty'];$min=$p['min_stock_level'];
                $rowCls=$qty==0?'out-of-stock':($qty<=$min?'low-stock':'');
            ?>
            <tr class="<?= $rowCls ?>">
                <td class="td-mono"><?= h($p['sku']) ?></td>
                <td><a href="<?= BASE_URL ?>/inventory/view.php?id=<?= $p['id'] ?>" style="font-weight:600;"><?= h($p['name']) ?></a></td>
                <td class="td-muted"><?= h($p['category_name']??'—') ?></td>
                <td class="td-mono" style="text-align:right;"><?= formatCurrency($p['price']) ?></td>
                <td class="td-mono" style="text-align:center;font-weight:700;color:<?= $qty==0?'var(--danger)':($qty<=$min?'var(--warning)':'var(--success)') ?>;"><?= $qty ?></td>
                <td class="td-mono" style="text-align:center;"><?= $min ?></td>
                <td><span class="badge <?= stockBadgeClass($qty,$min) ?>"><?= stockBadgeLabel($qty,$min) ?></span></td>
                <td style="text-align:center;">
                    <div class="table-actions" style="justify-content:center;">
                        <a href="<?= BASE_URL ?>/inventory/view.php?id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm btn-icon">👁</a>
                        <a href="<?= BASE_URL ?>/inventory/update_stock.php?id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm btn-icon">⇅</a>
                        <?php if (isAdmin()): ?>
                            <a href="<?= BASE_URL ?>/inventory/edit.php?id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm btn-icon">✏️</a>
                            <a href="<?= BASE_URL ?>/inventory/delete.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm btn-icon" data-confirm="Delete '<?= h($p['name']) ?>'?">🗑</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

</div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
