<?php
/**
 * users/add.php
 * Add a new user account. Admin only.
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f = [
        'name'     => clean($_POST['name']     ?? ''),
        'username' => clean($_POST['username'] ?? ''),
        'email'    => clean($_POST['email']    ?? ''),
        'role'     => $_POST['role'] === 'admin' ? 'admin' : 'staff',
        'password' => $_POST['password']  ?? '',
        'confirm'  => $_POST['confirm']   ?? '',
        'is_active'=> isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($f['name']     === '') $errors['name']     = 'Full name is required.';
    if ($f['username'] === '') $errors['username']  = 'Username is required.';
    if ($f['email']    === '') $errors['email']     = 'Email is required.';
    elseif (!filter_var($f['email'], FILTER_VALIDATE_EMAIL))
                               $errors['email']     = 'Enter a valid email.';
    if (strlen($f['password']) < 8)
                               $errors['password']  = 'Password must be at least 8 characters.';
    if ($f['password'] !== $f['confirm'])
                               $errors['confirm']   = 'Passwords do not match.';

    // Unique checks
    if (empty($errors['username'])) {
        $chk = $db->prepare('SELECT id FROM users WHERE username = ?');
        $chk->execute([$f['username']]);
        if ($chk->fetch()) $errors['username'] = 'This username is already taken.';
    }
    if (empty($errors['email'])) {
        $chk = $db->prepare('SELECT id FROM users WHERE email = ?');
        $chk->execute([$f['email']]);
        if ($chk->fetch()) $errors['email'] = 'This email is already registered.';
    }

    if (empty($errors)) {
        $hash = password_hash($f['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare(
            'INSERT INTO users (name, username, email, password_hash, role, is_active) VALUES (?,?,?,?,?,?)'
        )->execute([$f['name'], $f['username'], $f['email'], $hash, $f['role'], $f['is_active']]);
        setFlash('success', 'User "' . $f['name'] . '" created successfully.');
        redirect(BASE_URL . '/users/index.php');
    }
}

$pageTitle = 'Add User';
$activeNav = 'users';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main">
    <div class="sidebar-overlay"></div>
    <div class="topbar">
        <div>
            <div class="topbar-title">Add User</div>
            <div class="topbar-sub">Create a new staff or admin account</div>
        </div>
        <div class="topbar-actions">
            <button class="hamburger">☰</button>
            <a href="<?= BASE_URL ?>/users/index.php" class="btn btn-ghost btn-sm">← Back</a>
        </div>
    </div>

    <div class="page-content">
        <div class="card" style="max-width:640px;">
            <div class="card-header"><span class="card-title">New User Details</span></div>
            <div class="card-body">
                <form method="POST" novalidate>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name"
                                   class="form-control <?= isset($errors['name']) ? 'error' : '' ?>"
                                   value="<?= h($f['name'] ?? '') ?>" placeholder="e.g. Jane Smith">
                            <?php if (isset($errors['name'])): ?><div class="form-error"><?= h($errors['name']) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Username *</label>
                            <input type="text" name="username"
                                   class="form-control <?= isset($errors['username']) ? 'error' : '' ?>"
                                   value="<?= h($f['username'] ?? '') ?>" placeholder="e.g. jsmith">
                            <?php if (isset($errors['username'])): ?><div class="form-error"><?= h($errors['username']) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email"
                                   class="form-control <?= isset($errors['email']) ? 'error' : '' ?>"
                                   value="<?= h($f['email'] ?? '') ?>" placeholder="jane@store.com">
                            <?php if (isset($errors['email'])): ?><div class="form-error"><?= h($errors['email']) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Role *</label>
                            <select name="role" class="form-control">
                                <option value="staff" <?= ($f['role'] ?? 'staff') === 'staff' ? 'selected' : '' ?>>Staff</option>
                                <option value="admin" <?= ($f['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Password *</label>
                            <input type="password" name="password"
                                   class="form-control <?= isset($errors['password']) ? 'error' : '' ?>"
                                   placeholder="Min. 8 characters">
                            <?php if (isset($errors['password'])): ?><div class="form-error"><?= h($errors['password']) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm Password *</label>
                            <input type="password" name="confirm"
                                   class="form-control <?= isset($errors['confirm']) ? 'error' : '' ?>"
                                   placeholder="Repeat password">
                            <?php if (isset($errors['confirm'])): ?><div class="form-error"><?= h($errors['confirm']) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="is_active" value="1"
                                   <?= ($f['is_active'] ?? 1) ? 'checked' : '' ?>>
                            <span class="form-label" style="margin:0;">Account is active</span>
                        </label>
                        <div class="form-hint">Inactive users cannot log in.</div>
                    </div>

                    <div style="display:flex;gap:10px;margin-top:6px;">
                        <button type="submit" class="btn btn-primary">Create User</button>
                        <a href="<?= BASE_URL ?>/users/index.php" class="btn btn-ghost">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
