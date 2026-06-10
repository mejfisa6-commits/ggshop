<?php
require_once 'config.php';
requireLogin();
$pageTitle = 'ตะกร้าสินค้า - ' . SITE_NAME;

$pdo = getDB();

// เพิ่มสินค้าลงตะกร้า
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity   = max(1, (int)($_POST['quantity'] ?? 1));

    $stmt = $pdo->prepare("SELECT p.*, (SELECT COUNT(*) FROM product_codes pc WHERE pc.product_id = p.id AND pc.status='available') AS avail FROM products p WHERE p.id = ?");
    $stmt->execute([$product_id]);
    $p = $stmt->fetch();

    if ($p && $p['avail'] > 0) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $qty_in_cart = $_SESSION['cart'][$product_id]['quantity'] ?? 0;
        $new_qty = min($qty_in_cart + $quantity, $p['avail']);
        $_SESSION['cart'][$product_id] = [
            'product_id' => $product_id,
            'name'       => $p['name'],
            'price'      => $p['price'],
            'image'      => $p['image'],
            'quantity'   => $new_qty,
            'avail'      => $p['avail'],
        ];
        flash('เพิ่ม "' . $p['name'] . '" ลงตะกร้าแล้ว', 'success');
    }
    $redirect = $_POST['redirect'] ?? 'cart.php';
    header('Location: ' . BASE_URL . '/' . $redirect);
    exit;
}

// อัปเดตจำนวน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $pid => $qty) {
        $pid = (int)$pid; $qty = (int)$qty;
        if ($qty <= 0) {
            unset($_SESSION['cart'][$pid]);
        } else {
            if (isset($_SESSION['cart'][$pid])) {
                $_SESSION['cart'][$pid]['quantity'] = min($qty, $_SESSION['cart'][$pid]['avail']);
            }
        }
    }
    flash('อัปเดตตะกร้าแล้ว', 'success');
    header('Location: ' . BASE_URL . '/cart.php'); exit;
}

// ลบรายการ
if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][(int)$_GET['remove']]);
    flash('ลบสินค้าออกจากตะกร้าแล้ว', 'success');
    header('Location: ' . BASE_URL . '/cart.php'); exit;
}

// ล้างตะกร้า
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    flash('ล้างตะกร้าแล้ว', 'success');
    header('Location: ' . BASE_URL . '/cart.php'); exit;
}

// ชำระเงิน (ซื้อทั้งหมดในตะกร้า)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $cart = $_SESSION['cart'] ?? [];
    if (empty($cart)) { flash('ตะกร้าว่างเปล่า', 'error'); header('Location: ' . BASE_URL . '/cart.php'); exit; }

    // คำนวณราคารวม
    $total = 0;
    foreach ($cart as $item) $total += $item['price'] * $item['quantity'];

    // ตรวจสอบ coin
    $u = $pdo->prepare("SELECT coin FROM users WHERE id = ?");
    $u->execute([$_SESSION['user_id']]);
    $user = $u->fetch();
    if ($user['coin'] < $total) {
        flash('coin ไม่เพียงพอ ต้องการ ' . formatCoin($total) . ' coin (มี ' . formatCoin($user['coin']) . ' coin)', 'error');
        header('Location: ' . BASE_URL . '/cart.php'); exit;
    }

    try {
        $pdo->beginTransaction();
        $allCodes = [];

        foreach ($cart as $pid => $item) {
            // ตรวจสต็อก
            $codes = $pdo->prepare("SELECT id, code FROM product_codes WHERE product_id = ? AND status='available' LIMIT ? FOR UPDATE");
            $codes->execute([$pid, $item['quantity']]);
            $codeRows = $codes->fetchAll();

            if (count($codeRows) < $item['quantity']) {
                $pdo->rollBack();
                flash('สินค้า "' . $item['name'] . '" มีไม่เพียงพอ', 'error');
                header('Location: ' . BASE_URL . '/cart.php'); exit;
            }

            foreach ($codeRows as $c) {
                $pdo->prepare("UPDATE product_codes SET status='sold' WHERE id=?")->execute([$c['id']]);
                $pdo->prepare("INSERT INTO orders (user_id, product_id, code, price) VALUES (?,?,?,?)")
                    ->execute([$_SESSION['user_id'], $pid, $c['code'], $item['price']]);
                $allCodes[] = ['name' => $item['name'], 'code' => $c['code'], 'price' => $item['price']];
            }
            $pdo->prepare("UPDATE products SET stock=(SELECT COUNT(*) FROM product_codes WHERE product_id=? AND status='available') WHERE id=?")
                ->execute([$pid, $pid]);
        }

        // หัก coin
        $pdo->prepare("UPDATE users SET coin = coin - ? WHERE id=?")->execute([$total, $_SESSION['user_id']]);
        $pdo->commit();

        $_SESSION['cart'] = [];
        $_SESSION['last_codes'] = ['codes_list' => $allCodes, 'total' => $total];
        flash('ชำระเงินสำเร็จ!', 'success');
        header('Location: ' . BASE_URL . '/checkout_success.php'); exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        flash('เกิดข้อผิดพลาด กรุณาลองใหม่', 'error');
        header('Location: ' . BASE_URL . '/cart.php'); exit;
    }
}

$cart  = $_SESSION['cart'] ?? [];
$total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart));

// ดึง coin ล่าสุด
$u = $pdo->prepare("SELECT coin FROM users WHERE id=?");
$u->execute([$_SESSION['user_id']]);
$userCoin = $u->fetch()['coin'];

include 'header.php';
?>
<div class="page-title">🛒 ตะกร้าสินค้า</div>

<?php if (empty($cart)): ?>
<div class="card" style="text-align:center; padding:3rem;">
    <div style="font-size:4rem; margin-bottom:1rem;">🛒</div>
    <p style="color:var(--muted); margin-bottom:1.5rem;">ตะกร้าว่างเปล่า</p>
    <a href="index.php" class="btn btn-primary">🎮 ไปเลือกสินค้า</a>
</div>
<?php else: ?>
<div class="grid-2" style="align-items:start;">
    <!-- รายการสินค้า -->
    <div>
        <form method="POST">
            <input type="hidden" name="update_cart" value="1">
            <div style="display:flex; flex-direction:column; gap:.75rem; margin-bottom:1rem;">
                <?php foreach ($cart as $pid => $item): ?>
                <div class="card" style="padding:1rem; display:flex; gap:1rem; align-items:center;">
                    <!-- รูปสินค้า -->
                    <div style="width:64px; height:64px; background:var(--bg3); border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; overflow:hidden;">
                        <?php if ($item['image']): ?>
                            <img src="<?= BASE_URL ?>/uploads/products/<?= htmlspecialchars($item['image']) ?>" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <span style="font-size:1.8rem;">🕹️</span>
                        <?php endif; ?>
                    </div>
                    <!-- ชื่อ + ราคา -->
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:600; font-size:.92rem; margin-bottom:.2rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($item['name']) ?></div>
                        <div style="color:var(--gold); font-size:.88rem;"><?= formatCoin($item['price']) ?> 🪙 / ชิ้น</div>
                    </div>
                    <!-- จำนวน -->
                    <div style="display:flex; align-items:center; background:var(--bg3); border:1px solid var(--border); border-radius:8px; overflow:hidden;">
                        <button type="button" onclick="adjQty(<?= $pid ?>, -1)" style="background:none;border:none;color:var(--text);padding:.3rem .65rem;cursor:pointer;font-size:1rem;">−</button>
                        <input type="number" name="quantities[<?= $pid ?>]" id="qty_<?= $pid ?>" value="<?= $item['quantity'] ?>" min="0" max="<?= $item['avail'] ?>"
                            style="width:38px;background:none;border:none;color:var(--gold);font-weight:700;text-align:center;font-size:.9rem;">
                        <button type="button" onclick="adjQty(<?= $pid ?>, 1, <?= $item['avail'] ?>)" style="background:none;border:none;color:var(--text);padding:.3rem .65rem;cursor:pointer;font-size:1rem;">+</button>
                    </div>
                    <!-- รวม -->
                    <div style="text-align:right; min-width:80px;">
                        <div id="sub_<?= $pid ?>" style="color:var(--gold); font-weight:700; font-size:.95rem;"><?= formatCoin($item['price'] * $item['quantity']) ?> 🪙</div>
                    </div>
                    <!-- ลบ -->
                    <a href="cart.php?remove=<?= $pid ?>" style="color:var(--danger); font-size:1.1rem; padding:.2rem .4rem;" title="ลบ" onclick="return confirm('ลบสินค้านี้ออกจากตะกร้า?')">✕</a>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="display:flex; gap:.5rem;">
                <button type="submit" class="btn btn-outline btn-sm">🔄 อัปเดตตะกร้า</button>
                <a href="cart.php?clear=1" class="btn btn-danger btn-sm" onclick="return confirm('ล้างตะกร้าทั้งหมด?')">🗑️ ล้างตะกร้า</a>
            </div>
        </form>
    </div>

    <!-- สรุปออเดอร์ -->
    <div class="card" style="position:sticky; top:70px;">
        <div class="card-header">💳 สรุปออเดอร์</div>
        <div style="display:flex; flex-direction:column; gap:.6rem; margin-bottom:1.25rem;">
            <?php foreach ($cart as $item): ?>
            <div style="display:flex; justify-content:space-between; font-size:.88rem; color:var(--muted);">
                <span><?= htmlspecialchars(mb_substr($item['name'],0,25)) ?> × <?= $item['quantity'] ?></span>
                <span style="color:var(--text);"><?= formatCoin($item['price'] * $item['quantity']) ?> 🪙</span>
            </div>
            <?php endforeach; ?>
            <div style="border-top:1px solid var(--border); padding-top:.6rem; display:flex; justify-content:space-between; font-weight:700; font-size:1rem;">
                <span>รวมทั้งหมด</span>
                <span style="color:var(--gold);"><?= formatCoin($total) ?> 🪙</span>
            </div>
        </div>

        <div style="background:var(--bg3); border-radius:8px; padding:.75rem 1rem; margin-bottom:1rem; font-size:.88rem;">
            <div style="display:flex; justify-content:space-between; margin-bottom:.3rem;">
                <span style="color:var(--muted);">Coin ของฉัน</span>
                <span style="color:var(--gold);"><?= formatCoin($userCoin) ?> 🪙</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);">หลังชำระเงิน</span>
                <span style="color:<?= $userCoin >= $total ? 'var(--success)' : 'var(--danger)' ?>; font-weight:600;">
                    <?= formatCoin($userCoin - $total) ?> 🪙
                </span>
            </div>
        </div>

        <?php if ($userCoin < $total): ?>
            <div class="flash flash-error" style="margin-bottom:.75rem; font-size:.85rem;">
                ⚠️ Coin ไม่เพียงพอ ขาดอีก <?= formatCoin($total - $userCoin) ?> coin
            </div>
            <a href="topup.php" class="btn btn-gold" style="width:100%; text-align:center; margin-bottom:.5rem;">💰 เติม Coin</a>
        <?php else: ?>
            <form method="POST" onsubmit="return confirm('ยืนยันชำระเงิน <?= formatCoin($total) ?> coin?')">
                <input type="hidden" name="checkout" value="1">
                <button type="submit" class="btn btn-success" style="width:100%; padding:.75rem; font-size:1rem;">✅ ชำระเงิน <?= formatCoin($total) ?> 🪙</button>
            </form>
        <?php endif; ?>
        <a href="index.php" class="btn btn-outline" style="width:100%; text-align:center; margin-top:.5rem;">← ช้อปต่อ</a>
    </div>
</div>
<?php endif; ?>

<script>
const prices = <?= json_encode(array_map(fn($i) => (float)$i['price'], $cart)) ?>;
const pids   = <?= json_encode(array_keys($cart)) ?>;

function adjQty(pid, delta, max = 9999) {
    const el = document.getElementById('qty_' + pid);
    let v = parseInt(el.value) + delta;
    if (v < 0) v = 0;
    if (v > max) v = max;
    el.value = v;
    updateSub(pid);
}

function updateSub(pid) {
    const qty = parseInt(document.getElementById('qty_' + pid).value) || 0;
    const price = <?= json_encode(array_column($cart, 'price', null)) ?>;
    // find price for this pid
    const cartData = <?= json_encode($cart) ?>;
    if (cartData[pid]) {
        const sub = (qty * cartData[pid].price).toFixed(2);
        const el = document.getElementById('sub_' + pid);
        if (el) el.textContent = parseFloat(sub).toLocaleString('th-TH', {minimumFractionDigits:2}) + ' 🪙';
    }
}
</script>
<?php include 'footer.php'; ?>
