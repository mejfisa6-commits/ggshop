<?php
require_once '../config.php';
requireLogin();
requireAdmin();
$pageTitle = 'Admin Dashboard - ' . SITE_NAME;

$pdo = getDB();

// สร้างตาราง categories และ column ถ้ายังไม่มี
$pdo->exec("CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(10) DEFAULT '🎮',
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");
try { $pdo->exec("ALTER TABLE products ADD COLUMN category_id INT DEFAULT NULL"); } catch(Exception $e) {}

$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, id ASC")->fetchAll();

// ---- ฟังก์ชัน Admin ----

// อนุมัติ / ปฏิเสธ topup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $topupId = (int)($_POST['topup_id'] ?? 0);
    $action  = $_POST['action'];
    $note    = trim($_POST['note'] ?? '');

    $topup = $pdo->prepare("SELECT * FROM topups WHERE id = ?");
    $topup->execute([$topupId]);
    $t = $topup->fetch();

    if ($t && $t['status'] === 'pending') {
        if ($action === 'approve') {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE topups SET status='approved', note=? WHERE id=?")->execute([$note, $topupId]);
            $pdo->prepare("UPDATE users SET coin = coin + ? WHERE id = ?")->execute([$t['amount'], $t['user_id']]);
            $pdo->commit();
            flash('อนุมัติการเติมเงิน ' . formatCoin($t['amount']) . ' coin สำเร็จ', 'success');
        } elseif ($action === 'reject') {
            $pdo->prepare("UPDATE topups SET status='rejected', note=? WHERE id=?")->execute([$note, $topupId]);
            flash('ปฏิเสธรายการเติมเงินแล้ว', 'warning');
        }
    }
    header('Location: ' . BASE_URL . '/admin/admin.php');
    exit;
}

// ---- ดึงข้อมูล ----
$stats = [
    'users'         => $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
    'products'      => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'orders_today'  => $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(date)=CURDATE()")->fetchColumn(),
    'revenue_total' => $pdo->query("SELECT COALESCE(SUM(price),0) FROM orders")->fetchColumn(),
    'pending_topups'=> $pdo->query("SELECT COUNT(*) FROM topups WHERE status='pending'")->fetchColumn(),
];

$pendingTopups = $pdo->query("
    SELECT t.*, u.username FROM topups t JOIN users u ON t.user_id = u.id
    WHERE t.status = 'pending' ORDER BY t.created_at ASC
")->fetchAll();

$products = $pdo->query("
    SELECT p.*,
        (SELECT COUNT(*) FROM product_codes pc WHERE pc.product_id = p.id AND pc.status='available') AS avail
    FROM products p ORDER BY p.id DESC
")->fetchAll();

$recentOrders = $pdo->query("
    SELECT o.*, u.username, p.name AS product_name
    FROM orders o JOIN users u ON o.user_id=u.id JOIN products p ON o.product_id=p.id
    ORDER BY o.date DESC LIMIT 20
")->fetchAll();

include '../header.php';
?>
<style>
.stat-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:1rem; margin-bottom:1.5rem; }
.stat-card { background:var(--bg2); border:1px solid var(--border); border-radius:var(--radius); padding:1.25rem; }
.stat-label { font-size:0.8rem; color:var(--muted); text-transform:uppercase; letter-spacing:.5px; margin-bottom:.4rem; }
.stat-val { font-size:1.8rem; font-weight:700; color:var(--gold); }
.tabs { display:flex; gap:.5rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.tab { padding:.5rem 1.2rem; border-radius:8px; border:1px solid var(--border); background:transparent; color:var(--muted); cursor:pointer; font-size:.9rem; transition:all .2s; }
.tab.active { background:var(--accent); color:#fff; border-color:var(--accent); }
.tab-content { display:none; }
.tab-content.active { display:block; }
.slip-img { max-width:220px; max-height:200px; border-radius:8px; border:1px solid var(--border); cursor:zoom-in; }
</style>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
    <div class="page-title" style="margin:0;">👑 Admin Dashboard</div>
    <div style="display:flex; gap:.5rem;">
        <a href="settings.php" class="btn btn-outline btn-sm">⚙️ ตั้งค่าเว็บ</a>
        <a href="categories.php" class="btn btn-outline btn-sm">🗂️ หมวดหมู่</a>
        <a href="bank_accounts.php" class="btn btn-gold btn-sm">🏦 บัญชีธนาคาร</a>
        <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline btn-sm">← ไปหน้าเว็บ</a>
    </div>
</div>

<!-- Stats -->
<div class="stat-grid">
    <div class="stat-card"><div class="stat-label">👤 สมาชิก</div><div class="stat-val"><?= $stats['users'] ?></div></div>
    <div class="stat-card"><div class="stat-label">📦 สินค้า</div><div class="stat-val"><?= $stats['products'] ?></div></div>
    <div class="stat-card"><div class="stat-label">🛒 ออเดอร์วันนี้</div><div class="stat-val"><?= $stats['orders_today'] ?></div></div>
    <div class="stat-card"><div class="stat-label">💰 รายได้รวม</div><div class="stat-val" style="font-size:1.3rem;"><?= formatCoin($stats['revenue_total']) ?></div></div>
    <div class="stat-card" style="<?= $stats['pending_topups'] > 0 ? 'border-color:var(--warning)' : '' ?>">
        <div class="stat-label">⏳ รอตรวจสอบ</div>
        <div class="stat-val" style="color:var(--warning);"><?= $stats['pending_topups'] ?></div>
    </div>
</div>

<!-- Tabs -->
<div class="tabs">
    <button class="tab active" onclick="switchTab('topups', this)">💰 เติมเงิน <?= $stats['pending_topups'] > 0 ? '<span style="background:var(--danger);color:#fff;border-radius:20px;padding:0 7px;font-size:.75rem;margin-left:4px;">' . $stats['pending_topups'] . '</span>' : '' ?></button>
    <button class="tab" onclick="switchTab('products', this)">📦 สินค้า</button>
    <button class="tab" onclick="switchTab('orders', this)">📋 ออเดอร์ล่าสุด</button>
    <button class="tab" onclick="switchTab('addproduct', this)">➕ เพิ่มสินค้า</button>
</div>

<!-- Tab: Topups -->
<div id="tab-topups" class="tab-content active">
    <div class="card" style="padding:0;">
        <?php if (empty($pendingTopups)): ?>
            <p style="text-align:center; padding:2.5rem; color:var(--muted);">✅ ไม่มีรายการรอตรวจสอบ</p>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:0;">
                <?php foreach ($pendingTopups as $t): ?>
                <div style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border);">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap;">
                        <div>
                            <div style="font-weight:600; margin-bottom:.3rem;">
                                👤 <?= htmlspecialchars($t['username']) ?> 
                                — <span style="color:var(--gold);"><?= formatCoin($t['amount']) ?> coin</span>
                            </div>
                            <div style="color:var(--muted); font-size:.82rem;"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></div>
                            <?php if ($t['slip']): ?>
                                <div style="margin-top:.75rem;">
                                    <img src="<?= BASE_URL ?>/slips/<?= htmlspecialchars($t['slip']) ?>"
                                         class="slip-img"
                                         onclick="window.open(this.src,'_blank')"
                                         alt="สลิป">
                                </div>
                            <?php endif; ?>
                        </div>
                        <form method="POST" style="min-width:220px;">
                            <input type="hidden" name="topup_id" value="<?= $t['id'] ?>">
                            <div class="form-group" style="margin-bottom:.6rem;">
                                <input type="text" name="note" class="form-control" placeholder="หมายเหตุ (ถ้ามี)" style="font-size:.85rem;">
                            </div>
                            <div style="display:flex; gap:.5rem;">
                                <button type="submit" name="action" value="approve" class="btn btn-success btn-sm" style="flex:1;"
                                        onclick="return confirm('อนุมัติการเติมเงิน <?= formatCoin($t['amount']) ?> coin?')">
                                    ✅ อนุมัติ
                                </button>
                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm" style="flex:1;"
                                        onclick="return confirm('ปฏิเสธรายการนี้?')">
                                    ❌ ปฏิเสธ
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tab: Products -->
<div id="tab-products" class="tab-content">
    <div class="card" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>ชื่อสินค้า</th><th>ราคา</th><th>Code ที่เหลือ</th><th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td style="color:var(--gold);"><?= formatCoin($p['price']) ?> 🪙</td>
                        <td>
                            <span class="badge <?= $p['avail'] > 0 ? 'badge-success' : 'badge-danger' ?>">
                                <?= $p['avail'] ?> ชิ้น
                            </span>
                        </td>
                        <td style="display:flex; gap:.5rem; flex-wrap:wrap;">
                            <a href="add_codes.php?product_id=<?= $p['id'] ?>" class="btn btn-primary btn-sm">➕ เพิ่ม Code</a>
                            <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">✏️ แก้ไข</a>
                            <a href="delete_product.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('ลบสินค้า <?= htmlspecialchars(addslashes($p['name'])) ?> และ Code ทั้งหมด?')">🗑️</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tab: Orders -->
<div id="tab-orders" class="tab-content">
    <div class="card" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>วันที่</th><th>ผู้ซื้อ</th><th>สินค้า</th><th>Code</th><th>ราคา</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $o): ?>
                    <tr>
                        <td style="font-size:.82rem; color:var(--muted);"><?= date('d/m/Y H:i', strtotime($o['date'])) ?></td>
                        <td><?= htmlspecialchars($o['username']) ?></td>
                        <td><?= htmlspecialchars($o['product_name']) ?></td>
                        <td><code style="color:var(--gold); font-size:.85rem;"><?= htmlspecialchars($o['code']) ?></code></td>
                        <td style="color:var(--gold);"><?= formatCoin($o['price']) ?> 🪙</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tab: Add Product -->
<div id="tab-addproduct" class="tab-content">
    <div class="card" style="max-width:520px;">
        <div class="card-header">➕ เพิ่มสินค้าใหม่</div>
        <?php
        // Handle add product form
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
            $name     = trim($_POST['prod_name'] ?? '');
            $price    = (float)($_POST['prod_price'] ?? 0);
            $desc     = trim($_POST['prod_desc'] ?? '');
            $cat_id   = $_POST['prod_cat'] !== '' ? (int)$_POST['prod_cat'] : null;
            if ($name && $price > 0) {
                $pdo->prepare("INSERT INTO products (name, price, description, category_id) VALUES (?, ?, ?, ?)")
                    ->execute([$name, $price, $desc, $cat_id]);
                flash('เพิ่มสินค้าสำเร็จ!', 'success');
                header('Location: ' . BASE_URL . '/admin/admin.php#products');
                exit;
            }
        }
        ?>
        <form method="POST">
            <input type="hidden" name="add_product" value="1">
            <div class="form-group">
                <label class="form-label">หมวดหมู่</label>
                <select name="prod_cat" class="form-control">
                    <option value="">-- ไม่ระบุหมวดหมู่ --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['icon'] . ' ' . $cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">ชื่อสินค้า *</label>
                <input type="text" name="prod_name" class="form-control" placeholder="เช่น Valorant 1000 VP" required>
            </div>
            <div class="form-group">
                <label class="form-label">ราคา (coin) *</label>
                <input type="number" name="prod_price" class="form-control" step="0.01" min="0.01" placeholder="เช่น 149.00" required>
            </div>
            <div class="form-group">
                <label class="form-label">คำอธิบาย</label>
                <textarea name="prod_desc" class="form-control" placeholder="รายละเอียดสินค้า..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">เพิ่มสินค้า</button>
        </form>
    </div>
</div>

<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}
</script>

<?php include '../footer.php'; ?>
