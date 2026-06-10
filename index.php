<?php
require_once 'config.php';
$pageTitle = SITE_NAME . ' - ร้านขายรหัสเกมออนไลน์';

$pdo = getDB();

// สร้างตารางถ้ายังไม่มี
$pdo->exec("CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(10) DEFAULT '🎮',
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");
try { $pdo->exec("ALTER TABLE products ADD COLUMN category_id INT DEFAULT NULL"); } catch(Exception $e) {}

// ดึงหมวดหมู่
$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, id ASC")->fetchAll();

// กรองสินค้า
$search  = trim($_GET['q'] ?? '');
$cat_id  = (int)($_GET['cat'] ?? 0);

$sql = "SELECT p.*,
        (SELECT COUNT(*) FROM product_codes pc WHERE pc.product_id = p.id AND pc.status = 'available') AS available_stock
        FROM products p WHERE 1=1";
$params = [];
if ($search) {
    $sql .= " AND p.name LIKE ?";
    $params[] = "%$search%";
}
if ($cat_id) {
    $sql .= " AND p.category_id = ?";
    $params[] = $cat_id;
}
$sql .= " ORDER BY p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

include 'header.php';
?>

<?php
$_bgStyle = '';
if (setting('shop_bg')) {
    $_bgStyle = 'background: url(' . BASE_URL . '/uploads/shop/' . htmlspecialchars(setting('shop_bg')) . ') center/cover no-repeat; position:relative;';
} else {
    $_bgStyle = 'background: linear-gradient(135deg, var(--bg2) 0%, var(--bg) 100%);';
}
?>
<div style="text-align:center; padding: 2.5rem 1rem 2rem; <?= $_bgStyle ?> border-radius: 16px; margin-bottom: 1.5rem; border: 1px solid var(--border);">
    <?php if (setting('shop_bg')): ?><div style="position:absolute;inset:0;background:rgba(0,0,0,0.5);border-radius:16px;"></div><?php endif; ?>
    <div style="position:relative; z-index:1;">
        <?php if (setting('shop_logo')): ?>
            <img src="<?= BASE_URL ?>/uploads/shop/<?= htmlspecialchars(setting('shop_logo')) ?>" style="max-height:70px; margin-bottom:.75rem;">
        <?php else: ?>
            <h1 style="font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--accent);">🎮 <?= htmlspecialchars(setting('shop_name','GameShop')) ?></h1>
        <?php endif; ?>
        <p style="color: #ccc;"><?= htmlspecialchars(setting('shop_slogan','รหัสของแท้ ส่งทันที รับรองไม่มีปัญหา')) ?></p>
    </div>
</div>

<?php if (!empty($categories)): ?>
<!-- แท็บหมวดหมู่ -->
<div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:1.5rem;">
    <a href="index.php<?= $search ? '?q='.urlencode($search) : '' ?>"
       class="btn <?= !$cat_id ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        🏠 ทั้งหมด
    </a>
    <?php foreach ($categories as $cat): ?>
        <a href="index.php?cat=<?= $cat['id'] ?><?= $search ? '&q='.urlencode($search) : '' ?>"
           class="btn <?= $cat_id == $cat['id'] ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            <?= htmlspecialchars($cat['icon'] . ' ' . $cat['name']) ?>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php if (empty($products)): ?>
    <div class="card" style="text-align:center; padding:3rem;">
        <p style="font-size:3rem; margin-bottom:1rem;">🕹️</p>
        <p style="color:var(--muted);">ไม่พบสินค้า<?= $search ? ' สำหรับ "' . htmlspecialchars($search) . '"' : '' ?></p>
    </div>
<?php else: ?>
    <div class="grid-4">
        <?php foreach ($products as $p): ?>
        <div class="card" style="padding:0; overflow:hidden; display:flex; flex-direction:column; transition: transform .2s, box-shadow .2s;" onmouseenter="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 24px rgba(108,99,255,0.15)'" onmouseleave="this.style.transform='';this.style.boxShadow=''">
            <div style="background: var(--bg3); height: 130px; display:flex; align-items:center; justify-content:center; font-size: 3rem; border-bottom: 1px solid var(--border); overflow:hidden;">
                <?php if ($p['image']): ?>
                    <img src="<?= BASE_URL ?>/uploads/products/<?= htmlspecialchars($p['image']) ?>"
                         style="width:100%; height:100%; object-fit:cover;">
                <?php else: ?>
                    <?php
                    $icons = ['steam'=>'🎮','minecraft'=>'⛏️','valorant'=>'🔫','roblox'=>'🧱','pubg'=>'🪖','genshin'=>'⚔️'];
                    $icon = '🕹️';
                    foreach ($icons as $k => $v) { if (stripos($p['name'], $k) !== false) { $icon = $v; break; } }
                    echo $icon;
                    ?>
                <?php endif; ?>
            </div>
            <div style="padding: 1rem; flex:1; display:flex; flex-direction:column; gap:0.5rem;">
                <a href="product.php?id=<?= $p['id'] ?>" style="font-weight:600; font-size:0.95rem; color:var(--text); text-decoration:none;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text)'"><?= htmlspecialchars($p['name']) ?></a>
                <?php if ($p['description']): ?>
                    <div style="font-size:0.82rem; color:var(--muted); flex:1;"><?= htmlspecialchars(mb_substr($p['description'],0,60)) ?>...</div>
                <?php endif; ?>
                <a href="product.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm" style="text-align:center; margin-bottom:0.25rem;">📄 ดูรายละเอียด</a>
                <div style="display:flex; align-items:center; justify-content:space-between; margin-top:0.5rem;">
                    <span style="font-size:1.1rem; font-weight:700; color:var(--gold);"><?= formatCoin($p['price']) ?> 🪙</span>
                    <span style="font-size:0.8rem; color: <?= $p['available_stock'] > 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                        <?= $p['available_stock'] > 0 ? '✅ มี ' . $p['available_stock'] . ' ชิ้น' : '❌ สินค้าหมด' ?>
                    </span>
                </div>
                <?php if (isLoggedIn()): ?>
                    <?php if ($p['available_stock'] > 0): ?>
                        <!-- ปุ่มเพิ่มตะกร้า + ซื้อเลย -->
                        <div style="display:flex; gap:.4rem; margin-top:.25rem;">
                            <form method="POST" action="cart.php" style="flex:1;">
                                <input type="hidden" name="add_to_cart" value="1">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="redirect" value="index.php">
                                <button type="submit" class="btn btn-outline" style="width:100%; font-size:.85rem;">🛒 ใส่ตะกร้า</button>
                            </form>
                            <form method="POST" action="buy.php" style="flex:1;" onsubmit="return confirm('ซื้อ <?= htmlspecialchars(addslashes($p['name'])) ?> ราคา <?= formatCoin($p['price']) ?> coin?')">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn btn-primary" style="width:100%; font-size:.85rem;">⚡ ซื้อเลย</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <button class="btn btn-outline" style="width:100%; opacity:0.5;" disabled>สินค้าหมด</button>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline" style="width:100%; text-align:center;">เข้าสู่ระบบเพื่อซื้อ</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function changeQty(btn, delta, maxStock) {
    const input = btn.parentElement.querySelector('input[name="quantity"]');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > maxStock) val = maxStock;
    input.value = val;
    const price = parseFloat(btn.closest('form').dataset.price || 0);
    updateTotalFromInput(input, price);
}

function updateTotal(input, price) {
    updateTotalFromInput(input, price);
}

function updateTotalFromInput(input, price) {
    const qty = parseInt(input.value) || 1;
    const total = (qty * price).toFixed(2);
    const totalEl = input.closest('form').querySelector('.total-price');
    if (totalEl) totalEl.textContent = parseFloat(total).toLocaleString('th-TH', {minimumFractionDigits:2}) + ' 🪙';
}

function confirmBuy(form, name, price) {
    const qty = parseInt(form.querySelector('input[name="quantity"]').value) || 1;
    const total = (qty * price).toFixed(2);
    return confirm(`ยืนยันซื้อ "${name}"\nจำนวน: ${qty} ชิ้น\nราคารวม: ${parseFloat(total).toLocaleString('th-TH', {minimumFractionDigits:2})} coin`);
}
</script>
<?php include 'footer.php'; ?>
