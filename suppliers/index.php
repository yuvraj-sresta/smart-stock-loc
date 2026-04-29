<?php
/**
 * suppliers/index.php
 * Supplier list. Admin only.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireRole('admin');

$db     = getDB();
$search = clean($_GET['search'] ?? '');

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]  = '(s.name LIKE ? OR s.contact_person LIKE ? OR s.email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSQL  = implode(' AND ', $where);
$suppliers = $db->prepare(
    "SELECT s.*, COUNT(p.id) AS product_count
     FROM suppliers s
     LEFT JOIN products p ON p.supplier_id = s.id AND p.status = 'active'
     WHERE $whereSQL
     GROUP BY s.id
     ORDER BY s.name ASC"
);
$suppliers->execute($params);
$suppliers = $suppliers->fetchAll();

$pageTitle = 'Suppliers';
$activeNav = 'suppliers';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main">
    <div class="sidebar-overlay"></div>
    <div class="topbar">
        <div>
            <div class="topbar-title">Suppliers</div>
            <div class="topbar-sub"><?= count($suppliers) ?> supplier<?= count($suppliers) !== 1 ? 's' : '' ?> registered</div>
        </div>
        <div class="topbar-actions">
            <button class="hamburger">☰</button>
            <a href="<?= BASE_URL ?>/suppliers/add.php" class="btn btn-primary btn-sm">+ Add Supplier</a>
        </div>
    </div>

    <div class="page-content">

        <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?= h($flash['type']) ?>" data-auto-dismiss><?= h($flash['message']) ?></div>
        <?php endif; ?>

        <form method="GET" class="filter-bar">
            <input type="text" name="search" class="form-control"
                   placeholder="Search by name, contact or email…"
                   value="<?= h($search) ?>">
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
            <?php if ($search): ?>
                <a href="<?= BASE_URL ?>/suppliers/index.php" class="btn btn-ghost btn-sm">Clear</a>
            <?php endif; ?>
        </form>

        <div class="card">
            <div class="table-wrap">
                <?php if (empty($suppliers)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🏭</div>
                        <div class="empty-state-msg">No suppliers found.</div>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Supplier Name</th>
                                <th>Contact Person</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th style="text-align:center;">Products</th>
                                <th>Added</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suppliers as $s): ?>
                                <tr>
                                    <td style="font-weight:600;"><?= h($s['name']) ?></td>
                                    <td class="td-muted"><?= h($s['contact_person'] ?? '—') ?></td>
                                    <td class="td-mono"><?= h($s['phone'] ?? '—') ?></td>
                                    <td>
                                        <?php if ($s['email']): ?>
                                            <a href="mailto:<?= h($s['email']) ?>"><?= h($s['email']) ?></a>
                                        <?php else: ?>
                                            <span class="td-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <span class="badge badge-info"><?= $s['product_count'] ?></span>
                                    </td>
                                    <td class="td-muted" style="font-size:.78rem;"><?= formatDate($s['created_at'], 'd M Y') ?></td>
                                    <td style="text-align:center;">
                                        <div class="table-actions" style="justify-content:center;">
                                            <a href="<?= BASE_URL ?>/suppliers/edit.php?id=<?= $s['id'] ?>"
                                               class="btn btn-ghost btn-sm" title="Edit">✏️</a>
                                            <a href="<?= BASE_URL ?>/suppliers/delete.php?id=<?= $s['id'] ?>"
                                               class="btn btn-danger btn-sm" title="Delete"
                                               data-confirm="Delete supplier '<?= h($s['name']) ?>'? This cannot be undone.">🗑</a>
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
