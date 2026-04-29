<?php
/**
 * transactions/index.php
 * Full stock transaction log. Admin + Staff (read-only).
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireLogin();

$db = getDB();

$search = clean($_GET['search'] ?? '');
$type   = clean($_GET['type']   ?? '');

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]  = '(p.name LIKE ? OR p.sku LIKE ? OR u.name LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($type !== '') {
    $where[]  = 'st.change_type = ?';
    $params[] = $type;
}

$whereSQL = implode(' AND ', $where);

$stmt = $db->prepare(
    "SELECT st.*, p.name AS product_name, p.sku, u.name AS user_name
     FROM stock_transactions st
     JOIN products p ON st.product_id = p.id
     JOIN users u    ON st.user_id    = u.id
     WHERE $whereSQL
     ORDER BY st.created_at DESC
     LIMIT 100"
);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$pageTitle = 'Transactions';
$activeNav = 'transactions';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main">
    <div class="sidebar-overlay"></div>
    <div class="topbar">
        <div>
            <div class="topbar-title">Stock Transactions</div>
            <div class="topbar-sub">Full audit log of all stock changes</div>
        </div>
        <div class="topbar-actions">
            <button class="hamburger">☰</button>
        </div>
    </div>

    <div class="page-content">

        <form method="GET" class="filter-bar">
            <input type="text" name="search" class="form-control"
                   placeholder="Search product, SKU or user…" value="<?= h($search) ?>">
            <select name="type" class="form-control" style="max-width:170px;">
                <option value="">All Types</option>
                <?php foreach (['restock','sale','adjustment','damage','return'] as $t): ?>
                    <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <?php if ($search || $type): ?>
                <a href="<?= BASE_URL ?>/transactions/index.php" class="btn btn-ghost btn-sm">Clear</a>
            <?php endif; ?>
        </form>

        <div class="card">
            <div class="table-wrap">
                <?php if (empty($transactions)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <div class="empty-state-msg">No transactions found</div>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Type</th>
                                <th style="text-align:center;">Change</th>
                                <th style="text-align:center;">Before</th>
                                <th style="text-align:center;">After</th>
                                <th>By</th>
                                <th>Notes</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $tx): ?>
                                <tr>
                                    <td class="td-mono td-muted"><?= $tx['id'] ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/inventory/view.php?id=<?= $tx['product_id'] ?>">
                                            <?= h($tx['product_name']) ?>
                                        </a>
                                    </td>
                                    <td class="td-mono"><?= h($tx['sku']) ?></td>
                                    <td><span class="badge <?= txBadgeClass($tx['change_type']) ?>"><?= ucfirst(h($tx['change_type'])) ?></span></td>
                                    <td class="td-mono" style="text-align:center;color:<?= $tx['quantity_changed']>0?'var(--success)':'var(--danger)' ?>;font-weight:700;">
                                        <?= $tx['quantity_changed'] > 0 ? '+' : '' ?><?= $tx['quantity_changed'] ?>
                                    </td>
                                    <td class="td-mono" style="text-align:center;"><?= $tx['previous_qty'] ?></td>
                                    <td class="td-mono" style="text-align:center;font-weight:600;"><?= $tx['new_qty'] ?></td>
                                    <td class="td-muted"><?= h($tx['user_name']) ?></td>
                                    <td class="td-muted" style="font-size:.78rem;max-width:160px;"><?= h($tx['notes'] ?: '—') ?></td>
                                    <td class="td-muted" style="font-size:.75rem;white-space:nowrap;"><?= formatDate($tx['created_at'], 'd M Y, g:i A') ?></td>
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
