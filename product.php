<?php
require_once 'config.php';
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . '/index.php'); exit; }

$pdo = getDB();
$stmt = $pdo->prepare("SELECT p.*,
    (SELECT COUNT(*) FROM product_codes pc WHERE pc.product_id = p.id AND pc.status = 'available') AS available_stock
    FROM products p WHERE p.id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { flash('ไม่พบสินค้า', 'error'); header('Location: ' . BASE_URL . '/index.php'); exit; }

$pageTitle = htmlspecialchars($p['name']) . ' - ' . SITE_NAME;
include 'header.php';

$icons = ['steam'=>'🎮','minecraft'=>'⛏️','valorant'=>'🔫','roblox'=>'🧱','pubg'=>'🪖','genshin'=>'⚔️'];
$icon = '🕹️';
foreach ($icons as $k => $v) { if (stripos($p['name'], $k) !== false) { $icon = $v; break; } }
?>

<div style="margin-bottom:1rem;">
    <a href="index.php" class="btn btn-outline btn-sm">← กลับหน้าหลัก</a>
</div>

<div style="display:grid; grid-template-columns: 1fr 1.3fr; gap:2rem; align-items:start;">

    <!-- รูปสินค้า -->
    <div class="card" style="text-align:center; padding:2rem; overflow:hidden;">
        <?php if ($p['image']): ?>
            <img src="<?= BASE_URL ?>/uploads/products/<?= htmlspecialchars($p['image']) ?>"
                 style="max-width:100%; max-height:250px; object-fit:contain; border-radius:10px;">
        <?php else: ?>
            <div style="font-size:6rem; margin-bottom:1rem;"><?= $icon ?></div>
        <?php endif; ?>
        <div style="font-size:0.82rem; color:var(--muted); margin-top:1rem;">
            <?= $p['available_stock'] > 0
                ? '<span style="color:var(--success);">✅ มีสินค้าพร้อมส่ง ' . $p['available_stock'] . ' ชิ้น</span>'
                : '<span style="color:var(--danger);">❌ สินค้าหมด</span>' ?>
        </div>
    </div>

    <!-- รายละเอียด -->
    <div>
        <div class="card">
            <h1 style="font-size:1.5rem; font-weight:800; margin-bottom:0.5rem;"><?= htmlspecialchars($p['name']) ?></h1>
            <div style="font-size:2rem; font-weight:700; color:var(--gold); margin-bottom:1rem;">
                <?= formatCoin($p['price']) ?> 🪙
            </div>

            <?php if ($p['description']): ?>
            <div style="background:var(--bg3); border-radius:8px; padding:1rem; margin-bottom:1.25rem; line-height:1.8; color:var(--muted); font-size:0.92rem;">
                <?= nl2br(htmlspecialchars($p['description'])) ?>
            </div>
            <?php endif; ?>

            <!-- ข้อมูลเพิ่มเติม -->
            <div style="display:flex; flex-direction:column; gap:0.5rem; margin-bottom:1.5rem; font-size:0.88rem;">
                <div style="display:flex; justify-content:space-between; padding:0.5rem 0; border-bottom:1px solid var(--border);">
                    <span style="color:var(--muted);">ราคาต่อชิ้น</span>
                    <span style="color:var(--gold); font-weight:600;"><?= formatCoin($p['price']) ?> coin</span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:0.5rem 0; border-bottom:1px solid var(--border);">
                    <span style="color:var(--muted);">สินค้าคงเหลือ</span>
                    <span style="color:<?= $p['available_stock'] > 0 ? 'var(--success)' : 'var(--danger)' ?>; font-weight:600;"><?= $p['available_stock'] ?> ชิ้น</span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:0.5rem 0; border-bottom:1px solid var(--border);">
                    <span style="color:var(--muted);">ส่งทันที</span>
                    <span style="color:var(--success);">✅ อัตโนมัติ</span>
                </div>
            </div>

            <!-- ฟอร์มซื้อ -->
            <?php if (isLoggedIn()): ?>
                <?php if ($p['available_stock'] > 0): ?>
                <div style="margin-bottom:1rem;">
                    <label class="form-label">จำนวนที่ต้องการ</label>
                    <div style="display:flex; align-items:center; gap:1rem;">
                        <div style="display:flex; align-items:center; background:var(--bg3); border:1px solid var(--border); border-radius:8px; overflow:hidden;">
                            <button type="button" onclick="changeQty(-1, <?= $p['available_stock'] ?>)"
                                style="background:none; border:none; color:var(--text); padding:0.5rem 1rem; cursor:pointer; font-size:1.2rem;">−</button>
                            <input type="number" id="qty" value="1" min="1" max="<?= $p['available_stock'] ?>"
                                style="width:50px; background:none; border:none; color:var(--gold); font-weight:700; text-align:center; font-size:1rem;"
                                oninput="updateTotal(<?= $p['price'] ?>)">
                            <button type="button" onclick="changeQty(1, <?= $p['available_stock'] ?>)"
                                style="background:none; border:none; color:var(--text); padding:0.5rem 1rem; cursor:pointer; font-size:1.2rem;">+</button>
                        </div>
                        <div>
                            <div style="font-size:0.82rem; color:var(--muted);">ราคารวม</div>
                            <div id="total-price" style="font-size:1.3rem; font-weight:700; color:var(--gold);"><?= formatCoin($p['price']) ?> 🪙</div>
                        </div>
                    </div>
                </div>
                <div style="display:flex; gap:.75rem;">
                    <form method="POST" action="cart.php" style="flex:1;">
                        <input type="hidden" name="add_to_cart" value="1">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="redirect" value="product.php?id=<?= $p['id'] ?>">
                        <input type="hidden" name="quantity" id="cart_qty" value="1">
                        <button type="submit" class="btn btn-outline" style="width:100%; padding:.75rem;" onclick="syncQty()">
                            🛒 ใส่ตะกร้า
                        </button>
                    </form>
                    <form method="POST" action="buy.php" style="flex:1;" onsubmit="return confirmBuy(this, '<?= htmlspecialchars(addslashes($p['name'])) ?>', <?= $p['price'] ?>)">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="quantity" id="buy_qty" value="1">
                        <button type="submit" class="btn btn-primary" style="width:100%; padding:.75rem; font-size:1rem;" onclick="syncQty()">
                            ⚡ ซื้อเลย
                        </button>
                    </form>
                </div>
                <?php else: ?>
                    <button class="btn btn-outline" style="width:100%; padding:.75rem; opacity:0.5;" disabled>❌ สินค้าหมด</button>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary" style="width:100%; padding:.75rem; font-size:1rem; text-align:center;">
                    🔐 เข้าสู่ระบบเพื่อซื้อ
                </a>
            <?php endif; ?>
        </div>

        <!-- การรับประกัน -->
        <div class="card" style="margin-top:1rem;">
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; text-align:center; font-size:0.82rem;">
                <div>
                    <div style="font-size:1.5rem; margin-bottom:.3rem;">⚡</div>
                    <div style="color:var(--text); font-weight:600;">ส่งทันที</div>
                    <div style="color:var(--muted);">ได้รับ Code อัตโนมัติ</div>
                </div>
                <div>
                    <div style="font-size:1.5rem; margin-bottom:.3rem;">🔒</div>
                    <div style="color:var(--text); font-weight:600;">ปลอดภัย</div>
                    <div style="color:var(--muted);">Code ของแท้ 100%</div>
                </div>
                <div>
                    <div style="font-size:1.5rem; margin-bottom:.3rem;">📋</div>
                    <div style="color:var(--text); font-weight:600;">ดูประวัติ</div>
                    <div style="color:var(--muted);">ตรวจสอบได้ตลอด</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function changeQty(delta, maxStock) {
    const input = document.getElementById('qty');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > maxStock) val = maxStock;
    input.value = val;
    updateTotal(<?= $p['price'] ?>);
}
function updateTotal(price) {
    const qty = parseInt(document.getElementById('qty').value) || 1;
    const total = (qty * price).toFixed(2);
    document.getElementById('total-price').textContent = parseFloat(total).toLocaleString('th-TH', {minimumFractionDigits:2}) + ' 🪙';
}
function syncQty() {
    const qty = document.getElementById('qty').value;
    const cq = document.getElementById('cart_qty');
    const bq = document.getElementById('buy_qty');
    if (cq) cq.value = qty;
    if (bq) bq.value = qty;
}
function confirmBuy(form, name, price) {
    const qty = parseInt(document.getElementById('buy_qty').value) || 1;
    const total = (qty * price).toFixed(2);
    return confirm(`ยืนยันซื้อ "${name}"\nจำนวน: ${qty} ชิ้น\nราคารวม: ${parseFloat(total).toLocaleString('th-TH', {minimumFractionDigits:2})} coin`);
}
    const total = (qty * price).toFixed(2);
    return confirm(`ยืนยันซื้อ "${name}"\nจำนวน: ${qty} ชิ้น\nราคารวม: ${parseFloat(total).toLocaleString('th-TH', {minimumFractionDigits:2})} coin`);
}
</script>

<?php include 'footer.php'; ?>
