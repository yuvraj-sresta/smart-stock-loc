<?php
/**
 * users/index.php
 * User management — list all users. Admin only.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireRole('admin');

$db    = getDB();
$users = $db->query(
    'SELECT id, name, username, email, role, is_active, created_at FROM users ORDER BY role ASC, name ASC'
)->fetchAll();

$pageTitle = 'User Management';
$activeNav = 'users';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main">
    <div class="sidebar-overlay"></div>
    <div class="topbar">
        <div>
            <div class="topbar-title">User Management</div>
            <div class="topbar-sub"><?= count($users) ?> user accounts</div>
        </div>
        <div class="topbar-actions">
            <button class="hamburger">☰</button>
            <a href="<?= BASE_URL ?>/users/add.php" class="btn btn-primary btn-sm">+ Add User</a>
        </div>
    </div>

    <div class="page-content">

        <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?= h($flash['type']) ?>" data-auto-dismiss><?= h($flash['message']) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th style="text-align:center;">Role</th>
                            <th style="text-align:center;">Status</th>
                            <th>Created</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div style="width:30px;height:30px;background:<?= $u['role']==='admin'?'var(--accent)':'var(--border-strong)' ?>;border-radius:50%;display:flex;align-items:center;justify-content:center;color:<?= $u['role']==='admin'?'#fff':'var(--text-secondary)' ?>;font-size:.75rem;font-weight:700;flex-shrink:0;">
                                            <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                        </div>
                                        <span style="font-weight:600;"><?= h($u['name']) ?></span>
                                        <?php if ($u['id'] === currentUserId()): ?>
                                            <span class="badge badge-info" style="font-size:.65rem;">You</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="td-mono"><?= h($u['username']) ?></td>
                                <td class="td-muted"><?= h($u['email']) ?></td>
                                <td style="text-align:center;">
                                    <span class="badge <?= $u['role'] === 'admin' ? 'badge-info' : 'badge-neutral' ?>">
                                        <?= ucfirst(h($u['role'])) ?>
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <span class="badge <?= $u['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="td-muted" style="font-size:.78rem;"><?= formatDate($u['created_at'], 'd M Y') ?></td>
                                <td style="text-align:center;">
                                    <div class="table-actions" style="justify-content:center;">
                                        <a href="<?= BASE_URL ?>/users/edit.php?id=<?= $u['id'] ?>"
                                           class="btn btn-ghost btn-sm" title="Edit">✏️</a>
                                        <?php if ($u['id'] !== currentUserId()): ?>
                                            <a href="<?= BASE_URL ?>/users/delete.php?id=<?= $u['id'] ?>"
                                               class="btn btn-danger btn-sm" title="Delete"
                                               data-confirm="Delete user '<?= h($u['name']) ?>'? This cannot be undone.">🗑</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
