<?php
/**
 * dashboard/index.php – Premium v2.0
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireLogin();

$db = getDB();

$totalProducts  = $db->query('SELECT COUNT(*) FROM products WHERE status="active"')->fetchColumn();
$totalSuppliers = $db->query('SELECT COUNT(*) FROM suppliers')->fetchColumn();
$totalCategories= $db->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$lowStockCount  = $db->query('SELECT COUNT(*) FROM products WHERE status="active" AND stock_qty<=min_stock_level')->fetchColumn();

$lowStockProducts = $db->query(
    'SELECT p.id,p.name,p.sku,p.stock_qty,p.min_stock_level,c.name AS category_name
     FROM products p LEFT JOIN categories c ON p.category_id=c.id
     WHERE p.status="active" AND p.stock_qty<=p.min_stock_level
     ORDER BY p.stock_qty ASC LIMIT 8'
)->fetchAll();

$recentTx = $db->query(
    'SELECT st.created_at,st.change_type,st.quantity_changed,st.new_qty,
            p.name AS product_name,p.sku,u.name AS user_name
     FROM stock_transactions st
     JOIN products p ON st.product_id=p.id
     JOIN users u ON st.user_id=u.id
     ORDER BY st.created_at DESC LIMIT 6'
)->fetchAll();

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main">
    <div class="sidebar-overlay"></div>

    <div class="topbar">
        <div>
            <div class="topbar-title">Dashboard</div>
            <div class="topbar-sub">Welcome back, <?= h(currentUserName()) ?> 👋</div>
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

        <!-- ── Gradient Stat Cards ── -->
        <div class="stat-grid">
            <div class="stat-card grad-blue">
                <div class="stat-icon">▦</div>
                <div class="stat-label">Total Products</div>
                <div class="stat-value"><?= $totalProducts ?></div>
                <div class="stat-meta">Active in inventory</div>
            </div>
            <div class="stat-card grad-teal">
                <div class="stat-icon">🏭</div>
                <div class="stat-label">Suppliers</div>
                <div class="stat-value"><?= $totalSuppliers ?></div>
                <div class="stat-meta">Registered suppliers</div>
            </div>
            <div class="stat-card grad-green">
                <div class="stat-icon">🗂️</div>
                <div class="stat-label">Categories</div>
                <div class="stat-value"><?= $totalCategories ?></div>
                <div class="stat-meta">Product categories</div>
            </div>
            <div class="stat-card <?= $lowStockCount > 0 ? 'grad-rose' : 'grad-green' ?>">
                <div class="stat-icon">⚠️</div>
                <div class="stat-label">Low Stock</div>
                <div class="stat-value"><?= $lowStockCount ?></div>
                <div class="stat-meta"><?= $lowStockCount > 0 ? 'Needs attention' : 'All levels healthy' ?></div>
            </div>
        </div>

        <!-- ── Two column grid ── -->
        <div class="dash-grid">

            <div class="card">
                <div class="card-header">
                    <span class="card-title"><?= $lowStockCount > 0 ? '⚠️ Low Stock Alert' : '✅ Stock Levels' ?></span>
                    <a href="<?= BASE_URL ?>/inventory/index.php?filter=low" class="btn btn-ghost btn-sm">View All</a>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($lowStockProducts)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">✅</div>
                            <div class="empty-state-msg">All products are well stocked</div>
                        </div>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table>
                                <thead><tr><th>Product</th><th>SKU</th><th>Stock</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($lowStockProducts as $p): ?>
                                        <tr class="<?= $p['stock_qty']==0?'out-of-stock':'low-stock' ?>">
                                            <td>
                                                <a href="<?= BASE_URL ?>/inventory/view.php?id=<?= $p['id'] ?>" style="font-weight:600;"><?= h($p['name']) ?></a>
                                                <div style="font-size:.72rem;color:var(--text-muted);"><?= h($p['category_name']??'—') ?></div>
                                            </td>
                                            <td class="td-mono"><?= h($p['sku']) ?></td>
                                            <td class="td-mono" style="font-weight:700;"><?= $p['stock_qty'] ?> / <?= $p['min_stock_level'] ?></td>
                                            <td><span class="badge <?= stockBadgeClass($p['stock_qty'],$p['min_stock_level']) ?>"><?= stockBadgeLabel($p['stock_qty'],$p['min_stock_level']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">🕐 Recent Activity</span>
                    <a href="<?= BASE_URL ?>/transactions/index.php" class="btn btn-ghost btn-sm">View All</a>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($recentTx)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📋</div>
                            <div class="empty-state-msg">No transactions recorded yet</div>
                        </div>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table>
                                <thead><tr><th>Product</th><th>Type</th><th>Qty</th><th>By</th><th>Date</th></tr></thead>
                                <tbody>
                                    <?php foreach ($recentTx as $tx): ?>
                                        <tr>
                                            <td>
                                                <?= h($tx['product_name']) ?>
                                                <div class="td-mono" style="font-size:.72rem;color:var(--text-muted);"><?= h($tx['sku']) ?></div>
                                            </td>
                                            <td><span class="badge <?= txBadgeClass($tx['change_type']) ?>"><?= ucfirst(h($tx['change_type'])) ?></span></td>
                                            <td class="td-mono"><?= $tx['quantity_changed']>0?'+':'' ?><?= $tx['quantity_changed'] ?></td>
                                            <td class="td-muted"><?= h($tx['user_name']) ?></td>
                                            <td class="td-muted" style="font-size:.75rem;"><?= formatDate($tx['created_at'],'d M, g:i A') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <?php if (isAdmin()): ?>
        <div class="quick-links">
            <a href="<?= BASE_URL ?>/inventory/add.php"      class="quick-link"><span class="ql-icon">＋</span><span class="ql-label">Add Product</span></a>
            <a href="<?= BASE_URL ?>/suppliers/add.php"      class="quick-link"><span class="ql-icon">🏭</span><span class="ql-label">Add Supplier</span></a>
            <a href="<?= BASE_URL ?>/reports/index.php"      class="quick-link"><span class="ql-icon">▤</span><span class="ql-label">View Reports</span></a>
            <a href="<?= BASE_URL ?>/transactions/index.php" class="quick-link"><span class="ql-icon">⇅</span><span class="ql-label">Transactions</span></a>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
