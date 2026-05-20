<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession(); requireRole('admin');
$db = getDB();
$search = clean($_GET['search'] ?? '');
$where = ['1=1']; $params = [];
if ($search !== '') { $where[] = '(s.name LIKE ? OR s.contact_person LIKE ? OR s.email LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
$stmt = $db->prepare("SELECT s.*, COUNT(p.id) AS product_count, COALESCE(SUM(p.stock_qty),0) AS total_stock, COALESCE(SUM(p.price*p.stock_qty),0) AS total_value, COALESCE(SUM(CASE WHEN p.stock_qty<=p.min_stock_level AND p.stock_qty>0 THEN 1 ELSE 0 END),0) AS low_count, COALESCE(SUM(CASE WHEN p.stock_qty=0 THEN 1 ELSE 0 END),0) AS out_count FROM suppliers s LEFT JOIN products p ON p.supplier_id=s.id AND p.status='active' WHERE ".implode(' AND ',$where)." GROUP BY s.id ORDER BY s.name ASC");
$stmt->execute($params); $suppliers = $stmt->fetchAll();
$dotColors = ['#6366f1','#06b6d4','#10b981','#f43f5e','#f59e0b','#8b5cf6','#0891b2','#ec4899'];
$pageTitle = 'Suppliers'; $activeNav = 'suppliers';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main">
<div class="sidebar-overlay"></div>
<div class="topbar">
    <div><div class="topbar-title">Suppliers</div><div class="topbar-sub"><?= count($suppliers) ?> suppliers — click any row to see their products</div></div>
    <div class="topbar-actions">
        <button class="hamburger">☰</button>
        <a href="<?= BASE_URL ?>/suppliers/add.php" class="btn btn-primary btn-sm">+ Add Supplier</a>
    </div>
</div>
<div class="page-content">
<?php $flash=getFlash(); if($flash): ?><div class="alert alert-<?= h($flash['type']) ?>" data-auto-dismiss><?= h($flash['message']) ?></div><?php endif; ?>

<form method="GET" style="display:flex;gap:10px;margin-bottom:20px;background:var(--surface);border:1px solid var(--border);border-radius:var(--rl);padding:14px 18px;box-shadow:var(--sh-xs);">
    <input type="text" name="search" class="form-control" placeholder="Search by name, contact or email…" value="<?= h($search) ?>" style="flex:1;">
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
    <?php if ($search): ?><a href="<?= BASE_URL ?>/suppliers/index.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
</form>

<?php if (empty($suppliers)): ?>
    <div class="empty-state card" style="padding:48px;"><div class="empty-state-icon">🏭</div><div class="empty-state-msg">No suppliers found.</div></div>
<?php else: ?>
    <?php foreach ($suppliers as $i => $s): ?>
    <?php $dc = $dotColors[$i % count($dotColors)]; ?>
    <div data-accordion-row data-load-id="<?= $s['id'] ?>" data-load-url="/suppliers/get_products.php" data-renderer="supplier_products" >

        <!-- Stats strip -->
        <div class="acc-stats">
            <div class="acc-stat"><div class="acc-stat-val"><?= $s['product_count'] ?></div><div class="acc-stat-lbl">Products</div></div>
            <div class="acc-stat"><div class="acc-stat-val"><?= number_format($s['total_stock']) ?></div><div class="acc-stat-lbl">Units</div></div>
            <div class="acc-stat"><div class="acc-stat-val">$<?= number_format($s['total_value'],0) ?></div><div class="acc-stat-lbl">Stock Value</div></div>
            <?php if ($s['low_count'] > 0): ?><div class="acc-stat"><div class="acc-stat-val" style="color:var(--warning);"><?= $s['low_count'] ?></div><div class="acc-stat-lbl">Low</div></div><?php endif; ?>
            <?php if ($s['out_count'] > 0): ?><div class="acc-stat"><div class="acc-stat-val" style="color:var(--danger);"><?= $s['out_count'] ?></div><div class="acc-stat-lbl">Out</div></div><?php endif; ?>
            <div style="margin-left:auto;display:flex;gap:6px;align-items:center;">
                <a href="<?= BASE_URL ?>/suppliers/edit.php?id=<?= $s['id'] ?>" class="btn btn-ghost btn-sm" onclick="event.stopPropagation();">✏️ Edit</a>
                <a href="<?= BASE_URL ?>/suppliers/delete.php?id=<?= $s['id'] ?>" class="btn btn-danger btn-sm" onclick="event.stopPropagation();" data-confirm="Delete '<?= h($s['name']) ?>'?">🗑</a>
            </div>
        </div>

        <!-- Trigger -->
        <div class="accordion-trigger" onclick="toggleAccordion(this)" role="button" tabindex="0" aria-expanded="false">
            <div class="accordion-left">
                <div class="accordion-dot" style="background:<?= $dc ?>;box-shadow:0 0 0 4px <?= $dc ?>22;width:16px;height:16px;"></div>
                <div class="accordion-titles">
                    <div class="accordion-title"><?= h($s['name']) ?></div>
                    <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:3px;">
                        <?php if ($s['contact_person']): ?><span style="font-size:.78rem;color:var(--text2);">👤 <?= h($s['contact_person']) ?></span><?php endif; ?>
                        <?php if ($s['phone']): ?><span style="font-size:.78rem;font-family:var(--font-m);color:var(--muted);">📞 <?= h($s['phone']) ?></span><?php endif; ?>
                        <?php if ($s['email']): ?><span style="font-size:.78rem;color:var(--accent);">✉️ <?= h($s['email']) ?></span><?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="accordion-right">
                <span style="font-size:.78rem;color:var(--muted);">Browse products</span>
                <div class="accordion-chevron">▼</div>
            </div>
        </div>

        <div class="accordion-panel"><div class="accordion-inner" data-loaded="false"></div></div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
