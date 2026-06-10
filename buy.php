<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$product_id = (int)($_POST['product_id'] ?? 0);
$quantity   = max(1, (int)($_POST['quantity'] ?? 1));
if (!$product_id) { header('Location: ' . BASE_URL . '/index.php'); exit; }

$pdo = getDB();

// ดึงข้อมูลสินค้า
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();
if (!$product) { flash('ไม่พบสินค้า', 'error'); header('Location: ' . BASE_URL . '/index.php'); exit; }

// ดึงข้อมูล user
$stmt = $pdo->prepare("SELECT coin FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$totalPrice = $product['price'] * $quantity;

// ตรวจสอบ coin
if ($user['coin'] < $totalPrice) {
    flash('coin ไม่เพียงพอ ต้องการ ' . formatCoin($totalPrice) . ' coin (มี ' . formatCoin($user['coin']) . ' coin)', 'error');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // หา codes ที่ available
    $stmt = $pdo->prepare("SELECT id, code FROM product_codes WHERE product_id = ? AND status = 'available' LIMIT ? FOR UPDATE");
    $stmt->execute([$product_id, $quantity]);
    $codes = $stmt->fetchAll();

    if (count($codes) < $quantity) {
        $pdo->rollBack();
        flash('สินค้าไม่เพียงพอ มีเหลือแค่ ' . count($codes) . ' ชิ้น', 'error');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }

    $boughtCodes = [];
    foreach ($codes as $codeRow) {
        // อัปเดตสถานะ code
        $pdo->prepare("UPDATE product_codes SET status = 'sold' WHERE id = ?")->execute([$codeRow['id']]);
        // บันทึก order
        $pdo->prepare("INSERT INTO orders (user_id, product_id, code, price) VALUES (?, ?, ?, ?)")
            ->execute([$_SESSION['user_id'], $product_id, $codeRow['code'], $product['price']]);
        $boughtCodes[] = $codeRow['code'];
    }

    // หัก coin
    $pdo->prepare("UPDATE users SET coin = coin - ? WHERE id = ?")->execute([$totalPrice, $_SESSION['user_id']]);

    // อัปเดต stock
    $pdo->prepare("UPDATE products SET stock = (SELECT COUNT(*) FROM product_codes WHERE product_id = ? AND status = 'available') WHERE id = ?")
        ->execute([$product_id, $product_id]);

    $pdo->commit();

    $_SESSION['last_codes'] = [
        'name'     => $product['name'],
        'codes'    => $boughtCodes,
        'price'    => $product['price'],
        'quantity' => $quantity,
        'total'    => $totalPrice,
    ];

    flash('ซื้อสำเร็จ!', 'success');
    header('Location: ' . BASE_URL . '/buy_success.php');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    flash('เกิดข้อผิดพลาด กรุณาลองใหม่', 'error');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
?>
