<?php
/**
 * profile/index.php
 * View and edit own profile + change password.
 * Accessible by: Admin + Staff (own profile only)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireLogin();

$db     = getDB();
$errors = [];
$success = '';

// Load current user
$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([currentUserId()]);
$user = $stmt->fetch();

// ── Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update_profile') {
        $name  = clean($_POST['name']  ?? '');
        $email = clean($_POST['email'] ?? '');

        if ($name === '')  $errors['name']  = 'Name is required.';
        if ($email === '') $errors['email'] = 'Email is required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
                           $errors['email'] = 'Please enter a valid email.';

        // Check email not taken by another user
        if (empty($errors['email'])) {
            $chk = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $chk->execute([$email, currentUserId()]);
            if ($chk->fetch()) $errors['email'] = 'This email is already used by another account.';
        }

        if (empty($errors)) {
            $db->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?')
               ->execute([$name, $email, currentUserId()]);
            // Update session name
            $_SESSION[SESSION_USER_NAME] = $name;
            $success = 'profile';
            // Reload user
            $stmt->execute([currentUserId()]);
            $user = $stmt->fetch();
        }
    }

    if ($_POST['action'] === 'change_password') {
        $current  = $_POST['current_password']  ?? '';
        $new      = $_POST['new_password']       ?? '';
        $confirm  = $_POST['confirm_password']   ?? '';

        if (!password_verify($current, $user['password_hash']))
            $errors['current_password'] = 'Current password is incorrect.';
        if (strlen($new) < 8)
            $errors['new_password'] = 'New password must be at least 8 characters.';
        if ($new !== $confirm)
            $errors['confirm_password'] = 'Passwords do not match.';

        if (empty($errors)) {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
               ->execute([$hash, currentUserId()]);
            $success = 'password';
        }
    }
}

$pageTitle = 'My Profile';
$activeNav = 'profile';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main">
    <div class="sidebar-overlay"></div>
    <div class="topbar">
        <div>
            <div class="topbar-title">My Profile</div>
            <div class="topbar-sub">Manage your account details</div>
        </div>
        <div class="topbar-actions">
            <button class="hamburger">☰</button>
        </div>
    </div>

    <div class="page-content">

        <?php if ($success === 'profile'): ?>
            <div class="alert alert-success" data-auto-dismiss>✅ Profile updated successfully.</div>
        <?php elseif ($success === 'password'): ?>
            <div class="alert alert-success" data-auto-dismiss>✅ Password changed successfully.</div>
        <?php endif; ?>

        <div class="view-grid" style="grid-template-columns:1fr 1fr;align-items:start;">

            <!-- ── Profile Info Card ── -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Account Information</span>
                    <span class="badge <?= $user['role'] === 'admin' ? 'badge-info' : 'badge-neutral' ?>">
                        <?= ucfirst(h($user['role'])) ?>
                    </span>
                </div>
                <div class="card-body">

                    <!-- Avatar display -->
                    <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--border);">
                        <div style="width:56px;height:56px;background:var(--accent);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.4rem;font-weight:700;flex-shrink:0;">
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div style="font-size:1rem;font-weight:700;"><?= h($user['name']) ?></div>
                            <div style="font-size:.82rem;color:var(--text-muted);"><?= h($user['email']) ?></div>
                            <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px;">Member since <?= formatDate($user['created_at'], 'd M Y') ?></div>
                        </div>
                    </div>

                    <form method="POST" novalidate>
                        <input type="hidden" name="action" value="update_profile">

                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name"
                                   class="form-control <?= isset($errors['name']) ? 'error' : '' ?>"
                                   value="<?= h($user['name']) ?>">
                            <?php if (isset($errors['name'])): ?>
                                <div class="form-error"><?= h($errors['name']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email Address *</label>
                            <input type="email" name="email"
                                   class="form-control <?= isset($errors['email']) ? 'error' : '' ?>"
                                   value="<?= h($user['email']) ?>">
                            <?php if (isset($errors['email'])): ?>
                                <div class="form-error"><?= h($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?= h($user['username']) ?>" disabled>
                            <div class="form-hint">Username cannot be changed.</div>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- ── Change Password Card ── -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Change Password</span>
                </div>
                <div class="card-body">
                    <form method="POST" novalidate>
                        <input type="hidden" name="action" value="change_password">

                        <div class="form-group">
                            <label class="form-label">Current Password *</label>
                            <input type="password" name="current_password"
                                   class="form-control <?= isset($errors['current_password']) ? 'error' : '' ?>"
                                   placeholder="Enter your current password">
                            <?php if (isset($errors['current_password'])): ?>
                                <div class="form-error"><?= h($errors['current_password']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label">New Password *</label>
                            <input type="password" name="new_password"
                                   class="form-control <?= isset($errors['new_password']) ? 'error' : '' ?>"
                                   placeholder="Minimum 8 characters">
                            <?php if (isset($errors['new_password'])): ?>
                                <div class="form-error"><?= h($errors['new_password']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Confirm New Password *</label>
                            <input type="password" name="confirm_password"
                                   class="form-control <?= isset($errors['confirm_password']) ? 'error' : '' ?>"
                                   placeholder="Repeat new password">
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="form-error"><?= h($errors['confirm_password']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div style="background:var(--bg);border-radius:var(--radius);padding:12px 14px;margin-bottom:18px;font-size:.8rem;color:var(--text-secondary);">
                            <strong>Password requirements:</strong><br>
                            • At least 8 characters long<br>
                            • Use a mix of letters, numbers, and symbols for best security
                        </div>

                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
