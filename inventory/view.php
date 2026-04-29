<?php
/**
 * inventory/view.php
 * Product detail page. Admin + Staff.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(BASE_URL . '/inventory/index.php');

$stmt = $db->prepare(
    'SELECT p.*, c.name AS category_name, s.name AS supplier_name,
            s.contact_person, s.phone, s.email
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN suppliers  s ON p.supplier_id  = s.id
     WHERE p.id = ?'
);
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { setFlash('danger','Product not found.'); redirect(BASE_URL . '/inventory/index.php'); }

// Recent transactions for this product
$txStmt = $db->prepare(
    'SELECT st.*, u.name AS user_name FROM stock_transactions st
     JOIN users u ON st.user_id = u.id
     WHERE st.product_id = ? ORDER BY st.created_at DESC LIMIT 10'
);
$txStmt->execute([$id]);
$transactions = $txStmt->fetchAll();

$pageTitle = h($p['name']);
$activeNav = 'inventory';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main">
    <div class="sidebar-overlay"></div>
    <div class="topbar">
        <div>
            <div class="topbar-title"><?= h($p['name']) ?></div>
            <div class="topbar-sub">SKU: <?= h($p['sku']) ?></div>
        </div>
        <div class="topbar-actions">
            <button class="hamburger">☰</button>
            <a href="<?= BASE_URL ?>/inventory/update_stock.php?id=<?= $id ?>" class="btn btn-primary btn-sm">⇅ Update Stock</a>
            <?php if (isAdmin()): ?>
                <a href="<?= BASE_URL ?>/inventory/edit.php?id=<?= $id ?>" class="btn btn-ghost btn-sm">✏️ Edit</a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/inventory/index.php" class="btn btn-ghost btn-sm">← Back</a>
        </div>
    </div>

    <div class="page-content">

        <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?= h($flash['type']) ?>" data-auto-dismiss><?= h($flash['message']) ?></div>
        <?php endif; ?>

        <div class="view-grid">
            <!-- Left: Product details -->
            <div style="display:flex;flex-direction:column;gap:20px;">

                <!-- Stock status banner -->
                <?php if ($p['stock_qty'] == 0): ?>
                    <div class="alert alert-danger">⚠️ This product is <strong>out of stock</strong>. Immediate restocking required.</div>
                <?php elseif ($p['stock_qty'] <= $p['min_stock_level']): ?>
                    <div class="alert alert-warning">⚠️ Stock is <strong>low</strong> (<?= $p['stock_qty'] ?> remaining, minimum is <?= $p['min_stock_level'] ?>). Consider restocking.</div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header"><span class="card-title">Product Details</span></div>
                    <div class="card-body">
                        <div class="detail-grid">
                            <div class="detail-row">
                                <span class="detail-label">Product Name</span>
                                <span class="detail-value"><?= h($p['name']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">SKU</span>
                                <span class="detail-value td-mono"><?= h($p['sku']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Category</span>
                                <span class="detail-value"><?= h($p['category_name'] ?? '—') ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Supplier</span>
                                <span class="detail-value"><?= h($p['supplier_name'] ?? '—') ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Price</span>
                                <span class="detail-value td-mono"><?= formatCurrency($p['price']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Status</span>
                                <span class="detail-value">
                                    <span class="badge <?= $p['status'] === 'active' ? 'badge-success' : 'badge-neutral' ?>">
                                        <?= ucfirst(h($p['status'])) ?>
                                    </span>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Current Stock</span>
                                <span class="detail-value" style="font-size:1.3rem;font-weight:700;font-family:var(--font-mono);color:<?= $p['stock_qty']==0?'var(--danger)':($p['stock_qty']<=$p['min_stock_level']?'var(--warning)':'var(--success)') ?>">
                                    <?= $p['stock_qty'] ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Min Stock Level</span>
                                <span class="detail-value td-mono"><?= $p['min_stock_level'] ?></span>
                            </div>
                            <?php if ($p['description']): ?>
                            <div class="detail-row" style="align-items:flex-start;">
                                <span class="detail-label">Description</span>
                                <span class="detail-value" style="color:var(--text-secondary);"><?= h($p['description']) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="detail-row">
                                <span class="detail-label">Added</span>
                                <span class="detail-value td-muted"><?= formatDate($p['created_at']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Last Updated</span>
                                <span class="detail-value td-muted"><?= formatDate($p['updated_at']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right: Transaction history -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">🕐 Stock History</span>
                    <span style="font-size:.75rem;color:var(--text-muted);">Last 10 changes</span>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($transactions)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📋</div>
                            <div class="empty-state-msg">No stock changes recorded yet</div>
                        </div>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Change</th>
                                        <th>New Qty</th>
                                        <th>By</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $tx): ?>
                                        <tr>
                                            <td><span class="badge <?= txBadgeClass($tx['change_type']) ?>"><?= ucfirst(h($tx['change_type'])) ?></span></td>
                                            <td class="td-mono" style="color:<?= $tx['quantity_changed']>0?'var(--success)':'var(--danger)' ?>">
                                                <?= $tx['quantity_changed'] > 0 ? '+' : '' ?><?= $tx['quantity_changed'] ?>
                                            </td>
                                            <td class="td-mono"><?= $tx['new_qty'] ?></td>
                                            <td class="td-muted"><?= h($tx['user_name']) ?></td>
                                            <td class="td-muted" style="font-size:.75rem;"><?= formatDate($tx['created_at'], 'd M Y') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /.view-grid -->
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
