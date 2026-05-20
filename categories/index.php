<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession(); requireRole('admin');
$db = getDB(); $errors = []; $f = [];
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add') {
    $f = ['name'=>clean($_POST['name']??''),'description'=>clean($_POST['description']??'')];
    if ($f['name']==='') { $errors['name']='Category name is required.'; }
    else { $chk=$db->prepare('SELECT id FROM categories WHERE name=?'); $chk->execute([$f['name']]); if($chk->fetch()) $errors['name']='A category with this name already exists.'; }
    if (empty($errors)) { $db->prepare('INSERT INTO categories (name,description) VALUES (?,?)')->execute([$f['name'],$f['description']]); setFlash('success','Category "'.$f['name'].'" added.'); redirect(BASE_URL.'/categories/index.php'); }
}
$categories = $db->query('SELECT c.*, COUNT(p.id) AS product_count, COALESCE(SUM(p.stock_qty),0) AS total_stock, COALESCE(SUM(p.price*p.stock_qty),0) AS total_value, COALESCE(SUM(CASE WHEN p.stock_qty<=p.min_stock_level AND p.stock_qty>0 THEN 1 ELSE 0 END),0) AS low_count, COALESCE(SUM(CASE WHEN p.stock_qty=0 THEN 1 ELSE 0 END),0) AS out_count FROM categories c LEFT JOIN products p ON p.category_id=c.id AND p.status="active" GROUP BY c.id ORDER BY c.name ASC')->fetchAll();
$dotColors = ['#6366f1','#06b6d4','#10b981','#f43f5e','#f59e0b','#8b5cf6','#0891b2','#ec4899'];
$pageTitle = 'Categories'; $activeNav = 'categories';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main">
<div class="sidebar-overlay"></div>
<div class="topbar">
    <div><div class="topbar-title">Categories</div><div class="topbar-sub"><?= count($categories) ?> categories — click any row to browse products</div></div>
    <div class="topbar-actions"><button class="hamburger">☰</button></div>
</div>
<div class="page-content">
<?php $flash=getFlash(); if($flash): ?><div class="alert alert-<?= h($flash['type']) ?>" data-auto-dismiss><?= h($flash['message']) ?></div><?php endif; ?>
<div class="cat-layout">
<div class="cat-list">
<?php if (empty($categories)): ?>
    <div class="empty-state card" style="padding:48px;"><div class="empty-state-icon">🗂️</div><div class="empty-state-msg">No categories yet.</div></div>
<?php else: ?>
    <?php foreach ($categories as $i => $cat): ?>
    <?php $dc = $dotColors[$i % count($dotColors)]; ?>
    <div data-accordion-row data-load-id="<?= $cat['id'] ?>" data-load-url="/categories/get_products.php" data-renderer="products" >

        <!-- Stats strip (always visible) -->
        <div class="acc-stats">
            <div class="acc-stat"><div class="acc-stat-val"><?= $cat['product_count'] ?></div><div class="acc-stat-lbl">Products</div></div>
            <div class="acc-stat"><div class="acc-stat-val"><?= number_format($cat['total_stock']) ?></div><div class="acc-stat-lbl">Units</div></div>
            <div class="acc-stat"><div class="acc-stat-val">$<?= number_format($cat['total_value'],0) ?></div><div class="acc-stat-lbl">Value</div></div>
            <?php if ($cat['low_count'] > 0): ?><div class="acc-stat"><div class="acc-stat-val" style="color:var(--warning);"><?= $cat['low_count'] ?></div><div class="acc-stat-lbl">Low Stock</div></div><?php endif; ?>
            <?php if ($cat['out_count'] > 0): ?><div class="acc-stat"><div class="acc-stat-val" style="color:var(--danger);"><?= $cat['out_count'] ?></div><div class="acc-stat-lbl">Out of Stock</div></div><?php endif; ?>
            <div style="margin-left:auto;display:flex;gap:6px;align-items:center;">
                <a href="<?= BASE_URL ?>/categories/edit.php?id=<?= $cat['id'] ?>" class="btn btn-ghost btn-sm" onclick="event.stopPropagation();">✏️ Edit</a>
                <a href="<?= BASE_URL ?>/categories/delete.php?id=<?= $cat['id'] ?>" class="btn btn-danger btn-sm" onclick="event.stopPropagation();" data-confirm="Delete '<?= h($cat['name']) ?>'?">🗑</a>
            </div>
        </div>

        <!-- Clickable trigger -->
        <div class="accordion-trigger" onclick="toggleAccordion(this)" role="button" tabindex="0" aria-expanded="false">
            <div class="accordion-left">
                <div class="accordion-dot" style="background:<?= $dc ?>;box-shadow:0 0 0 4px <?= $dc ?>22;"></div>
                <div class="accordion-titles">
                    <div class="accordion-title"><?= h($cat['name']) ?></div>
                    <div class="accordion-sub"><?= h($cat['description'] ?: 'Click to browse products in this category') ?></div>
                </div>
            </div>
            <div class="accordion-right">
                <span style="font-size:.78rem;color:var(--muted);font-family:var(--font);">Browse products</span>
                <div class="accordion-chevron">▼</div>
            </div>
        </div>

        <div class="accordion-panel"><div class="accordion-inner" data-loaded="false"></div></div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<div class="cat-sidebar">
    <div class="card" style="position:sticky;top:90px;">
        <div class="card-header"><span class="card-title">✨ Add New Category</span></div>
        <div class="card-body">
            <form method="POST" novalidate><input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label class="form-label">Category Name *</label>
                    <input type="text" name="name" class="form-control <?= isset($errors['name'])?'error':'' ?>" value="<?= h($f['name']??'') ?>" placeholder="e.g. Beverages">
                    <?php if (isset($errors['name'])): ?><div class="form-error"><?= h($errors['name']) ?></div><?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" placeholder="Optional description…"><?= h($f['description']??'') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Add Category</button>
            </form>
        </div>
    </div>
    <div class="card" style="margin-top:16px;">
        <div class="card-body" style="padding:18px;">
            <div style="font-family:var(--font-d);font-size:.73rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.09em;margin-bottom:14px;">All Categories</div>
            <?php foreach ($categories as $i => $cat): ?>
            <div class="summary-dot-row">
                <div style="width:10px;height:10px;border-radius:50%;background:<?= $dotColors[$i%count($dotColors)] ?>;flex-shrink:0;"></div>
                <span style="font-size:.82rem;color:var(--text2);flex:1;"><?= h($cat['name']) ?></span>
                <span style="font-size:.75rem;font-family:var(--font-m);color:var(--muted);"><?= $cat['product_count'] ?> items</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</div>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
