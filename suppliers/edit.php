<?php
/**
 * suppliers/edit.php
 * Edit existing supplier. Admin only.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireRole('admin');

$db     = getDB();
$errors = [];
$id     = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) redirect(BASE_URL . '/suppliers/index.php');

$stmt = $db->prepare('SELECT * FROM suppliers WHERE id = ?');
$stmt->execute([$id]);
$supplier = $stmt->fetch();
if (!$supplier) { setFlash('danger', 'Supplier not found.'); redirect(BASE_URL . '/suppliers/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f = [
        'name'           => clean($_POST['name']           ?? ''),
        'contact_person' => clean($_POST['contact_person'] ?? ''),
        'phone'          => clean($_POST['phone']          ?? ''),
        'email'          => clean($_POST['email']          ?? ''),
        'address'        => clean($_POST['address']        ?? ''),
    ];

    if ($f['name'] === '') $errors['name'] = 'Supplier name is required.';
    if ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL))
        $errors['email'] = 'Please enter a valid email address.';

    if (empty($errors)) {
        $upd = $db->prepare(
            'UPDATE suppliers SET name=?, contact_person=?, phone=?, email=?, address=? WHERE id=?'
        );
        $upd->execute([$f['name'], $f['contact_person'], $f['phone'], $f['email'], $f['address'], $id]);
        setFlash('success', 'Supplier updated successfully.');
        redirect(BASE_URL . '/suppliers/index.php');
    }
    $supplier = array_merge($supplier, $f);
}

$pageTitle = 'Edit Supplier';
$activeNav = 'suppliers';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main">
    <div class="sidebar-overlay"></div>
    <div class="topbar">
        <div>
            <div class="topbar-title">Edit Supplier</div>
            <div class="topbar-sub"><?= h($supplier['name']) ?></div>
        </div>
        <div class="topbar-actions">
            <button class="hamburger">☰</button>
            <a href="<?= BASE_URL ?>/suppliers/index.php" class="btn btn-ghost btn-sm">← Back</a>
        </div>
    </div>

    <div class="page-content">
        <div class="card" style="max-width:680px;">
            <div class="card-header"><span class="card-title">Edit Supplier Details</span></div>
            <div class="card-body">
                <form method="POST" novalidate>
                    <input type="hidden" name="id" value="<?= $id ?>">

                    <div class="form-group">
                        <label class="form-label">Supplier Name *</label>
                        <input type="text" name="name"
                               class="form-control <?= isset($errors['name']) ? 'error' : '' ?>"
                               value="<?= h($supplier['name']) ?>">
                        <?php if (isset($errors['name'])): ?><div class="form-error"><?= h($errors['name']) ?></div><?php endif; ?>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control"
                                   value="<?= h($supplier['contact_person'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control"
                                   value="<?= h($supplier['phone'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email"
                               class="form-control <?= isset($errors['email']) ? 'error' : '' ?>"
                               value="<?= h($supplier['email'] ?? '') ?>">
                        <?php if (isset($errors['email'])): ?><div class="form-error"><?= h($errors['email']) ?></div><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control"><?= h($supplier['address'] ?? '') ?></textarea>
                    </div>

                    <div style="display:flex;gap:10px;margin-top:6px;">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="<?= BASE_URL ?>/suppliers/index.php" class="btn btn-ghost">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
