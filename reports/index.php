<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireRole('admin');
$db = getDB();

// ── CSV Downloads
$dl = clean($_GET['download'] ?? '');
if ($dl === 'stock') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="stock-summary-'.date('Y-m-d').'.csv"');
    $rows = $db->query('SELECT c.name AS category, COUNT(p.id) AS products, COALESCE(SUM(p.stock_qty),0) AS units, COALESCE(SUM(p.price*p.stock_qty),0) AS value FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.status="active" GROUP BY c.id ORDER BY value DESC')->fetchAll();
    echo "Category,Products,Total Units,Stock Value (AUD)\n";
    foreach ($rows as $r) echo '"'.($r['category']??'Uncategorised').'","'.$r['products'].'","'.$r['units'].'","'.number_format($r['value'],2).'"'."\n";
    exit;
}
if ($dl === 'lowstock') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="low-stock-'.date('Y-m-d').'.csv"');
    $rows = $db->query('SELECT p.name,p.sku,p.stock_qty,p.min_stock_level,c.name AS category,s.name AS supplier,s.phone FROM products p LEFT JOIN categories c ON p.category_id=c.id LEFT JOIN suppliers s ON p.supplier_id=s.id WHERE p.status="active" AND p.stock_qty<=p.min_stock_level ORDER BY p.stock_qty ASC')->fetchAll();
    echo "Product,SKU,Current Stock,Min Level,Category,Supplier,Phone\n";
    foreach ($rows as $r) echo '"'.$r['name'].'","'.$r['sku'].'","'.$r['stock_qty'].'","'.$r['min_stock_level'].'","'.($r['category']??'').'","'.($r['supplier']??'').'","'.($r['phone']??'').'"'."\n";
    exit;
}
if ($dl === 'suppliers') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="suppliers-'.date('Y-m-d').'.csv"');
    $rows = $db->query('SELECT s.name,s.contact_person,s.phone,s.email,COUNT(p.id) AS products,COALESCE(SUM(p.stock_qty),0) AS units,COALESCE(SUM(p.price*p.stock_qty),0) AS value FROM suppliers s LEFT JOIN products p ON p.supplier_id=s.id AND p.status="active" GROUP BY s.id ORDER BY products DESC')->fetchAll();
    echo "Supplier,Contact,Phone,Email,Products,Units,Value\n";
    foreach ($rows as $r) echo '"'.$r['name'].'","'.($r['contact_person']??'').'","'.($r['phone']??'').'","'.($r['email']??'').'","'.$r['products'].'","'.$r['units'].'","'.number_format($r['value'],2).'"'."\n";
    exit;
}
if ($dl === 'transactions') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="transactions-'.date('Y-m-d').'.csv"');
    $rows = $db->query('SELECT st.id,p.name,p.sku,st.change_type,st.quantity_changed,st.previous_qty,st.new_qty,u.name AS user,st.notes,st.created_at FROM stock_transactions st JOIN products p ON st.product_id=p.id JOIN users u ON st.user_id=u.id ORDER BY st.created_at DESC LIMIT 500')->fetchAll();
    echo "ID,Product,SKU,Type,Change,Before,After,By,Notes,Date\n";
    foreach ($rows as $r) echo '"'.$r['id'].'","'.$r['name'].'","'.$r['sku'].'","'.$r['change_type'].'","'.$r['quantity_changed'].'","'.$r['previous_qty'].'","'.$r['new_qty'].'","'.$r['user'].'","'.str_replace('"','""',$r['notes']??'').'","'.$r['created_at'].'"'."\n";
    exit;
}

// ── Page Data
$stockByCategory = $db->query('SELECT c.name AS category_name, COUNT(p.id) AS total_products, COALESCE(SUM(p.stock_qty),0) AS total_stock, COALESCE(SUM(p.price*p.stock_qty),0) AS stock_value FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.status="active" GROUP BY c.id, c.name ORDER BY stock_value DESC')->fetchAll();
$lowStock        = $db->query('SELECT p.name,p.sku,p.stock_qty,p.min_stock_level,c.name AS category_name,s.name AS supplier_name,s.phone AS supplier_phone FROM products p LEFT JOIN categories c ON p.category_id=c.id LEFT JOIN suppliers s ON p.supplier_id=s.id WHERE p.status="active" AND p.stock_qty<=p.min_stock_level ORDER BY p.stock_qty ASC')->fetchAll();
$supplierSummary = $db->query('SELECT s.name AS supplier_name,s.contact_person,s.phone,s.email,COUNT(p.id) AS product_count,COALESCE(SUM(p.stock_qty),0) AS total_stock,COALESCE(SUM(p.price*p.stock_qty),0) AS stock_value FROM suppliers s LEFT JOIN products p ON p.supplier_id=s.id AND p.status="active" GROUP BY s.id ORDER BY product_count DESC')->fetchAll();
$txSummary       = $db->query('SELECT change_type, COUNT(*) AS total_transactions, COALESCE(SUM(ABS(quantity_changed)),0) AS total_units FROM stock_transactions WHERE created_at>=DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY change_type ORDER BY total_transactions DESC')->fetchAll();
$totals          = $db->query('SELECT COUNT(*) AS total_products, COALESCE(SUM(stock_qty),0) AS total_units, COALESCE(SUM(price*stock_qty),0) AS total_value FROM products WHERE status="active"')->fetch();

$pageTitle = 'Reports'; $activeNav = 'reports';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main">
<div class="sidebar-overlay"></div>
<div class="topbar">
    <div>
        <div class="topbar-title">Reports</div>
        <div class="topbar-sub">Generated on <?= date('d M Y, g:i A') ?></div>
    </div>
    <div class="topbar-actions"><button class="hamburger">☰</button></div>
</div>

<div class="page-content">

    <!-- Download Bar -->
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:24px;padding:14px 18px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow-xs);">
        <span style="font-family:var(--font-display);font-size:.82rem;font-weight:600;color:var(--text-secondary);flex:1;">📥 Download as CSV</span>
        <a href="?download=stock"        class="btn btn-ghost btn-sm">⬇ Stock Summary</a>
        <a href="?download=lowstock"     class="btn btn-ghost btn-sm">⬇ Low Stock</a>
        <a href="?download=suppliers"    class="btn btn-ghost btn-sm">⬇ Suppliers</a>
        <a href="?download=transactions" class="btn btn-ghost btn-sm">⬇ Transactions</a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">🖨 Print / PDF</button>
    </div>

    <!-- Stat Cards -->
    <div class="stat-grid" style="margin-bottom:24px;">
        <div class="stat-card accent">
            <div class="stat-label">Total Active Products</div>
            <div class="stat-value"><?= number_format($totals['total_products']) ?></div>
            <div class="stat-meta">Across all categories</div>
        </div>
        <div class="stat-card success">
            <div class="stat-label">Total Stock Units</div>
            <div class="stat-value"><?= number_format($totals['total_units']) ?></div>
            <div class="stat-meta">Combined inventory</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Stock Value</div>
            <div class="stat-value" style="font-size:1.4rem;"><?= formatCurrency($totals['total_value']) ?></div>
            <div class="stat-meta">At current prices (AUD)</div>
        </div>
        <div class="stat-card <?= count($lowStock)>0?'danger':'success' ?>">
            <div class="stat-label">Low Stock Items</div>
            <div class="stat-value"><?= count($lowStock) ?></div>
            <div class="stat-meta"><?= count($lowStock)>0?'Needs restocking':'All healthy' ?></div>
        </div>
    </div>

    <!-- Stock by Category -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <span class="card-title">📦 Stock Summary by Category</span>
            <span class="badge badge-neutral"><?= count($stockByCategory) ?> categories</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Category</th><th style="text-align:center;">Products</th><th style="text-align:center;">Total Units</th><th style="text-align:right;">Stock Value (AUD)</th></tr></thead>
                <tbody>
                <?php foreach ($stockByCategory as $row): ?>
                <tr>
                    <td style="font-weight:600;"><?= h($row['category_name']??'Uncategorised') ?></td>
                    <td class="td-mono" style="text-align:center;"><?= $row['total_products'] ?></td>
                    <td class="td-mono" style="text-align:center;"><?= number_format($row['total_stock']) ?></td>
                    <td class="td-mono" style="text-align:right;font-weight:600;"><?= formatCurrency($row['stock_value']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:var(--bg);font-weight:700;">
                        <td>Total</td>
                        <td class="td-mono" style="text-align:center;"><?= $totals['total_products'] ?></td>
                        <td class="td-mono" style="text-align:center;"><?= number_format($totals['total_units']) ?></td>
                        <td class="td-mono" style="text-align:right;"><?= formatCurrency($totals['total_value']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Low Stock -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <span class="card-title">⚠️ Low Stock Report</span>
            <?php if (count($lowStock)>0): ?><span class="badge badge-danger"><?= count($lowStock) ?> items need attention</span><?php endif; ?>
        </div>
        <div class="table-wrap">
        <?php if (empty($lowStock)): ?>
            <div class="empty-state"><div class="empty-state-icon">✅</div><div class="empty-state-msg">All products above minimum stock levels</div></div>
        <?php else: ?>
            <table>
                <thead><tr><th>Product</th><th>SKU</th><th>Category</th><th style="text-align:center;">Stock</th><th style="text-align:center;">Min</th><th>Status</th><th>Supplier</th><th>Phone</th></tr></thead>
                <tbody>
                <?php foreach ($lowStock as $p): ?>
                <tr class="<?= $p['stock_qty']==0?'out-of-stock':'low-stock' ?>">
                    <td style="font-weight:600;"><?= h($p['name']) ?></td>
                    <td class="td-mono"><?= h($p['sku']) ?></td>
                    <td class="td-muted"><?= h($p['category_name']??'—') ?></td>
                    <td class="td-mono" style="text-align:center;font-weight:700;color:<?= $p['stock_qty']==0?'var(--danger)':'var(--warning)' ?>;"><?= $p['stock_qty'] ?></td>
                    <td class="td-mono" style="text-align:center;"><?= $p['min_stock_level'] ?></td>
                    <td><span class="badge <?= stockBadgeClass($p['stock_qty'],$p['min_stock_level']) ?>"><?= stockBadgeLabel($p['stock_qty'],$p['min_stock_level']) ?></span></td>
                    <td><?= h($p['supplier_name']??'—') ?></td>
                    <td class="td-mono"><?= h($p['supplier_phone']??'—') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        </div>
    </div>

    <!-- Supplier Summary -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><span class="card-title">🏭 Supplier Summary</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Supplier</th><th>Contact</th><th>Phone</th><th>Email</th><th style="text-align:center;">Products</th><th style="text-align:center;">Units</th><th style="text-align:right;">Value</th></tr></thead>
                <tbody>
                <?php foreach ($supplierSummary as $s): ?>
                <tr>
                    <td style="font-weight:600;"><?= h($s['supplier_name']) ?></td>
                    <td class="td-muted"><?= h($s['contact_person']??'—') ?></td>
                    <td class="td-mono"><?= h($s['phone']??'—') ?></td>
                    <td style="font-size:.82rem;"><?= h($s['email']??'—') ?></td>
                    <td class="td-mono" style="text-align:center;"><?= $s['product_count'] ?></td>
                    <td class="td-mono" style="text-align:center;"><?= number_format($s['total_stock']) ?></td>
                    <td class="td-mono" style="text-align:right;font-weight:600;"><?= formatCurrency($s['stock_value']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Transaction Summary -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">⇅ Transaction Summary</span>
            <span style="font-size:.78rem;color:var(--text-muted);">Last 30 days</span>
        </div>
        <div class="table-wrap">
        <?php if (empty($txSummary)): ?>
            <div class="empty-state"><div class="empty-state-icon">📋</div><div class="empty-state-msg">No transactions in the last 30 days</div></div>
        <?php else: ?>
            <table>
                <thead><tr><th>Transaction Type</th><th style="text-align:center;">Count</th><th style="text-align:center;">Total Units</th></tr></thead>
                <tbody>
                <?php foreach ($txSummary as $tx): ?>
                <tr>
                    <td><span class="badge <?= txBadgeClass($tx['change_type']) ?>"><?= ucfirst(h($tx['change_type'])) ?></span></td>
                    <td class="td-mono" style="text-align:center;"><?= $tx['total_transactions'] ?></td>
                    <td class="td-mono" style="text-align:center;"><?= number_format($tx['total_units']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        </div>
    </div>

</div>
</div>

<style>
@media print {
    .sidebar,.topbar,.hamburger,.sidebar-overlay,[style*="display:flex;gap:10px;flex-wrap:wrap"] { display:none !important; }
    .main { margin-left:0 !important; }
    .card { box-shadow:none !important; break-inside:avoid; }
    .page-content { padding:0 !important; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
