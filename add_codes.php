<?php
require_once '../config.php';
requireLogin();
requireAdmin();

$pdo = getDB();
$product_id = (int)($_GET['product_id'] ?? 0);

$product = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$product->execute([$product_id]);
$p = $product->fetch();
if (!$p) { flash('ไม่พบสินค้า', 'error'); header('Location: admin.php'); exit; }

$pageTitle = 'เพิ่ม Code - ' . $p['name'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawCodes = trim($_POST['codes'] ?? '');
    $lines = array_filter(array_map('trim', explode("\n", $rawCodes)));

    if (empty($lines)) {
        $error = 'กรุณากรอก Code อย่างน้อย 1 รายการ';
    } else {
        $inserted = 0;
        $skipped  = 0;
        $stmt = $pdo->prepare("INSERT IGNORE INTO product_codes (product_id, code) VALUES (?, ?)");
        $updStock = $pdo->prepare("UPDATE products SET stock = (SELECT COUNT(*) FROM product_codes WHERE product_id = ? AND status='available') WHERE id = ?");

        foreach ($lines as $code) {
            if ($code === '') continue;
            // ตรวจสอบ duplicate
            $dup = $pdo->prepare("SELECT id FROM product_codes WHERE product_id = ? AND code = ?");
            $dup->execute([$product_id, $code]);
            if ($dup->fetch()) { $skipped++; continue; }
            $stmt->execute([$product_id, $code]);
            $inserted++;
        }

        // อัปเดต stock
        $pdo->prepare("UPDATE products SET stock = (SELECT COUNT(*) FROM product_codes WHERE product_id = ? AND status = 'available') WHERE id = ?")
            ->execute([$product_id, $product_id]);

        $success = "เพิ่ม Code สำเร็จ {$inserted} รายการ" . ($skipped > 0 ? " (ข้าม duplicate {$skipped} รายการ)" : '');
    }
}

// ดึง codes ปัจจุบัน
$codes = $pdo->prepare("SELECT * FROM product_codes WHERE product_id = ? ORDER BY id DESC LIMIT 50");
$codes->execute([$product_id]);
$codeList = $codes->fetchAll();

include '../header.php';
?>
<div style="margin-bottom:1rem;">
    <a href="admin.php" class="btn btn-outline btn-sm">← กลับ Admin</a>
</div>
<div class="page-title">➕ เพิ่ม Code: <?= htmlspecialchars($p['name']) ?></div>
<div class="card" style="color:var(--muted); font-size:.88rem; margin-bottom:1rem;">
    ราคา: <strong style="color:var(--gold);"><?= formatCoin($p['price']) ?> coin</strong>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">กรอก Code (1 รายการต่อบรรทัด)</div>
        <?php if ($error): ?><div class="flash flash-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="flash flash-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <textarea name="codes" class="form-control" rows="12"
                    placeholder="CODE-XXXX-YYYY&#10;CODE-AAAA-BBBB&#10;CODE-1111-2222&#10;..."></textarea>
                <small style="color:var(--muted);">วาง Code ได้ครั้งละหลายรายการ (แต่ละบรรทัดคือ 1 Code)</small>
            </div>
            <button type="submit" class="btn btn-primary">💾 บันทึก Code</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header">Code ทั้งหมด (แสดง 50 ล่าสุด)</div>
        <?php if (empty($codeList)): ?>
            <p style="color:var(--muted); padding:1rem 0;">ยังไม่มี Code</p>
        <?php else: ?>
            <div style="max-height:400px; overflow-y:auto;">
                <?php foreach ($codeList as $c): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:.4rem 0; border-bottom:1px solid var(--border); font-size:.88rem;">
                    <code style="color: <?= $c['status'] === 'available' ? 'var(--success)' : 'var(--muted)' ?>; word-break:break-all;">
                        <?= htmlspecialchars($c['code']) ?>
                    </code>
                    <span class="badge <?= $c['status'] === 'available' ? 'badge-success' : 'badge-danger' ?>" style="white-space:nowrap; margin-left:8px;">
                        <?= $c['status'] === 'available' ? 'ว่าง' : 'ขายแล้ว' ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../footer.php'; ?>
