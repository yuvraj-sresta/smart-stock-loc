<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireRole('admin');

$db     = getDB();
$errors = [];
$id     = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) redirect(BASE_URL . '/categories/index.php');

$stmt = $db->prepare('SELECT * FROM categories WHERE id = ?');
$stmt->execute([$id]);
$cat = $stmt->fetch();
if (!$cat) { setFlash('danger','Category not found.'); redirect(BASE_URL . '/categories/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name'] ?? '');
    $desc = clean($_POST['description'] ?? '');

    if ($name === '') {
        $errors['name'] = 'Category name is required.';
    } else {
        $chk = $db->prepare('SELECT id FROM categories WHERE name = ? AND id != ?');
        $chk->execute([$name, $id]);
        if ($chk->fetch()) $errors['name'] = 'Another category with this name already exists.';
    }

    if (empty($errors)) {
        $db->prepare('UPDATE categories SET name=?, description=? WHERE id=?')->execute([$name, $desc, $id]);
        setFlash('success', 'Category updated.');
        redirect(BASE_URL . '/categories/index.php');
    }
    $cat['name'] = $name; $cat['description'] = $desc;
}

$pageTitle = 'Edit Category';
$activeNav = 'categories';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main">
    <div class="sidebar-overlay"></div>
    <div class="topbar">
        <div><div class="topbar-title">Edit Category</div></div>
        <div class="topbar-actions">
            <button class="hamburger">☰</button>
            <a href="<?= BASE_URL ?>/categories/index.php" class="btn btn-ghost btn-sm">← Back</a>
        </div>
    </div>
    <div class="page-content">
        <div class="card" style="max-width:480px;">
            <div class="card-header"><span class="card-title">Edit Category</span></div>
            <div class="card-body">
                <form method="POST" novalidate>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div class="form-group">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control <?= isset($errors['name'])?'error':'' ?>"
                               value="<?= h($cat['name']) ?>">
                        <?php if (isset($errors['name'])): ?><div class="form-error"><?= h($errors['name']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control"><?= h($cat['description'] ?? '') ?></textarea>
                    </div>
                    <div style="display:flex;gap:10px;">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="<?= BASE_URL ?>/categories/index.php" class="btn btn-ghost">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
