<?php
/**
 * includes/sidebar.php – Premium v2.0
 */
if (!isset($activeNav)) $activeNav = '';

function navItem(string $href, string $icon, string $label, string $activeNav, string $key): string {
    $active = ($activeNav === $key) ? ' active' : '';
    return sprintf(
        '<a href="%s" class="nav-item%s"><span class="nav-icon">%s</span><span class="nav-label">%s</span></a>',
        BASE_URL . $href, $active, $icon, htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
    );
}
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon-wrap">📦</div>
        <span class="brand-name">Smart Stock</span>
        <span class="brand-version">v1.0</span>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <?= navItem('/dashboard/index.php',    '◈',  'Dashboard',    $activeNav, 'dashboard') ?>
        <?= navItem('/inventory/index.php',    '▦',  'Inventory',    $activeNav, 'inventory') ?>
        <?= navItem('/transactions/index.php', '⇅',  'Transactions', $activeNav, 'transactions') ?>

        <?php if (isAdmin()): ?>
        <div class="nav-section-label">Manage</div>
        <?= navItem('/suppliers/index.php',  '🏭', 'Suppliers',  $activeNav, 'suppliers') ?>
        <?= navItem('/categories/index.php', '🗂️', 'Categories', $activeNav, 'categories') ?>
        <?= navItem('/reports/index.php',    '▤',  'Reports',    $activeNav, 'reports') ?>
        <?= navItem('/users/index.php',      '👥', 'Users',      $activeNav, 'users') ?>
        <?php endif; ?>

        <div class="nav-section-label">Account</div>
        <?= navItem('/profile/index.php', '👤', 'My Profile', $activeNav, 'profile') ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-pill">
            <div class="user-avatar"><?= strtoupper(substr(currentUserName(), 0, 1)) ?></div>
            <div class="user-info">
                <span class="user-name"><?= h(currentUserName()) ?></span>
                <span class="user-role"><?= ucfirst(currentUserRole()) ?></span>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="logout-btn" title="Logout">⏻</a>
    </div>
</aside>
