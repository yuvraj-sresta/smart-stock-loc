<?php
/**
 * categories/index.php
 * Category list and inline add. Admin only.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireRole('admin');

$db     = getDB();
$errors = [];
$f      = [];

// Handle inline add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $f = [
        'name'        => clean($_POST['name']        ?? ''),
        'description' => clean($_POST['description'] ?? ''),
    ];
    if ($f['name'] === '') {
        $errors['name'] = 'Category name is required.';
    } else {
        $chk = $db->prepare('SELECT id FROM categories WHERE name = ?');
        $chk->execute([$f['name']]);
        if ($chk->fetch()) {
            $errors['name'] = 'A category with this name already exists.';
        }
    }
    if (empty($errors)) {
        $ins = $db->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
        $ins->execute([$f['name'], $f['description']]);
        setFlash('success', 'Category "' . $f['name'] . '" added.');
        redirect(BASE_URL . '/categories/index.php');
    }
}

$categories = $db->query(
    'SELECT c.*, COUNT(p.id) AS product_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id AND p.status = "active"
     GROUP BY c.id ORDER BY c.name ASC'
)->fetchAll();

$pageTitle = 'Categories';
$activeNav = 'categories';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main">
    <div class="sidebar-overlay"></div>
    <div class="topbar">
        <div>
            <div class="topbar-title">Categories</div>
            <div class="topbar-sub"><?= count($categories) ?> categories</div>
        </div>
        <div class="topbar-actions">
            <button class="hamburger">☰</button>
        </div>
    </div>

    <div class="page-content">

        <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?= h($flash['type']) ?>" data-auto-dismiss><?= h($flash['message']) ?></div>
        <?php endif; ?>

        <div class="view-grid" style="grid-template-columns:1fr 380px;">

            <!-- Category table -->
            <div class="card">
                <div class="card-header"><span class="card-title">All Categories</span></div>
                <div class="table-wrap">
                    <?php if (empty($categories)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">🗂️</div>
                            <div class="empty-state-msg">No categories yet.</div>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th style="text-align:center;">Products</th>
                                    <th style="text-align:center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td style="font-weight:600;"><?= h($cat['name']) ?></td>
                                        <td class="td-muted" style="font-size:.82rem;"><?= h($cat['description'] ?? '—') ?></td>
                                        <td style="text-align:center;">
                                            <span class="badge badge-info"><?= $cat['product_count'] ?></span>
                                        </td>
                                        <td style="text-align:center;">
                                            <div class="table-actions" style="justify-content:center;">
                                                <a href="<?= BASE_URL ?>/categories/edit.php?id=<?= $cat['id'] ?>"
                                                   class="btn btn-ghost btn-sm">✏️</a>
                                                <a href="<?= BASE_URL ?>/categories/delete.php?id=<?= $cat['id'] ?>"
                                                   class="btn btn-danger btn-sm"
                                                   data-confirm="Delete category '<?= h($cat['name']) ?>'?">🗑</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Inline Add form -->
            <div class="card" style="align-self:start;">
                <div class="card-header"><span class="card-title">Add New Category</span></div>
                <div class="card-body">
                    <form method="POST" novalidate>
                        <input type="hidden" name="action" value="add">
                        <div class="form-group">
                            <label class="form-label">Category Name *</label>
                            <input type="text" name="name"
                                   class="form-control <?= isset($errors['name']) ? 'error' : '' ?>"
                                   value="<?= h($f['name'] ?? '') ?>"
                                   placeholder="e.g. Beverages">
                            <?php if (isset($errors['name'])): ?><div class="form-error"><?= h($errors['name']) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control"
                                      placeholder="Optional description…"><?= h($f['description'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%;">Add Category</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
