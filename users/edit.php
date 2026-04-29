<?php
/**
 * users/edit.php
 * Edit user account. Admin only.
 * Admin can reset any user's password from here.
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
if (!$id) redirect(BASE_URL . '/users/index.php');

$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) { setFlash('danger','User not found.'); redirect(BASE_URL . '/users/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = clean($_POST['name']  ?? '');
    $email     = clean($_POST['email'] ?? '');
    $role      = $_POST['role'] === 'admin' ? 'admin' : 'staff';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $newPwd    = $_POST['new_password']     ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if ($name  === '') $errors['name']  = 'Name is required.';
    if ($email === '') $errors['email'] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
                       $errors['email'] = 'Enter a valid email.';

    // Unique email check (exclude self)
    if (empty($errors['email'])) {
        $chk = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $chk->execute([$email, $id]);
        if ($chk->fetch()) $errors['email'] = 'This email is used by another account.';
    }

    // Password reset is optional — only validate if filled in
    if ($newPwd !== '') {
        if (strlen($newPwd) < 8) $errors['new_password']     = 'Password must be at least 8 characters.';
        if ($newPwd !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';
    }

    // Prevent admin from deactivating their own account
    if ($id === currentUserId() && !$is_active) {
        $errors['is_active'] = 'You cannot deactivate your own account.';
    }

    if (empty($errors)) {
        $db->prepare('UPDATE users SET name=?, email=?, role=?, is_active=? WHERE id=?')
           ->execute([$name, $email, $role, $is_active, $id]);

        if ($newPwd !== '') {
            $hash = password_hash($newPwd, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$hash, $id]);
        }

        // Update session if editing own account
        if ($id === currentUserId()) {
            $_SESSION[SESSION_USER_NAME] = $name;
        }

        setFlash('success', 'User "' . $name . '" updated successfully.');
        redirect(BASE_URL . '/users/index.php');
    }

    $user = array_merge($user, ['name'=>$name,'email'=>$email,'role'=>$role,'is_active'=>$is_active]);
}

$pageTitle = 'Edit User';
$activeNav = 'users';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main">
    <div class="sidebar-overlay"></div>
    <div class="topbar">
        <div>
            <div class="topbar-title">Edit User</div>
            <div class="topbar-sub"><?= h($user['name']) ?></div>
        </div>
        <div class="topbar-actions">
            <button class="hamburger">☰</button>
            <a href="<?= BASE_URL ?>/users/index.php" class="btn btn-ghost btn-sm">← Back</a>
        </div>
    </div>

    <div class="page-content">
        <div class="card" style="max-width:640px;">
            <div class="card-header"><span class="card-title">Edit User Details</span></div>
            <div class="card-body">
                <form method="POST" novalidate>
                    <input type="hidden" name="id" value="<?= $id ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name"
                                   class="form-control <?= isset($errors['name']) ? 'error' : '' ?>"
                                   value="<?= h($user['name']) ?>">
                            <?php if (isset($errors['name'])): ?><div class="form-error"><?= h($errors['name']) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?= h($user['username']) ?>" disabled>
                            <div class="form-hint">Username cannot be changed.</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email"
                                   class="form-control <?= isset($errors['email']) ? 'error' : '' ?>"
                                   value="<?= h($user['email']) ?>">
                            <?php if (isset($errors['email'])): ?><div class="form-error"><?= h($errors['email']) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-control" <?= $id === currentUserId() ? 'disabled' : '' ?>>
                                <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                            <?php if ($id === currentUserId()): ?>
                                <input type="hidden" name="role" value="<?= h($user['role']) ?>">
                                <div class="form-hint">You cannot change your own role.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="is_active" value="1"
                                   <?= $user['is_active'] ? 'checked' : '' ?>
                                   <?= $id === currentUserId() ? 'disabled' : '' ?>>
                            <span class="form-label" style="margin:0;">Account is active</span>
                        </label>
                        <?php if (isset($errors['is_active'])): ?><div class="form-error"><?= h($errors['is_active']) ?></div><?php endif; ?>
                        <?php if ($id === currentUserId()): ?>
                            <input type="hidden" name="is_active" value="1">
                        <?php endif; ?>
                    </div>

                    <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">
                    <div style="font-size:.82rem;font-weight:600;color:var(--text-secondary);margin-bottom:14px;">
                        Reset Password <span style="font-weight:400;color:var(--text-muted);">(leave blank to keep current password)</span>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password"
                                   class="form-control <?= isset($errors['new_password']) ? 'error' : '' ?>"
                                   placeholder="Min. 8 characters">
                            <?php if (isset($errors['new_password'])): ?><div class="form-error"><?= h($errors['new_password']) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password"
                                   class="form-control <?= isset($errors['confirm_password']) ? 'error' : '' ?>"
                                   placeholder="Repeat new password">
                            <?php if (isset($errors['confirm_password'])): ?><div class="form-error"><?= h($errors['confirm_password']) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div style="display:flex;gap:10px;margin-top:6px;">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="<?= BASE_URL ?>/users/index.php" class="btn btn-ghost">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
