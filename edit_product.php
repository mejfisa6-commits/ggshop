<?php
require_once '../config.php';
requireLogin();
requireAdmin();

$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { flash('ไม่พบสินค้า', 'error'); header('Location: admin.php'); exit; }

$pageTitle = 'แก้ไขสินค้า - ' . SITE_NAME;
$error = '';
$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, id ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name'] ?? '');
    $price  = (float)($_POST['price'] ?? 0);
    $desc   = trim($_POST['description'] ?? '');
    $cat_id = $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
    $image  = $p['image'];

    // อัปโหลดรูปสินค้า
    if (!empty($_FILES['product_image']['name'])) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['product_image']['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowed)) {
            $error = 'ไฟล์ต้องเป็นรูปภาพเท่านั้น';
        } elseif ($_FILES['product_image']['size'] > 5 * 1024 * 1024) {
            $error = 'ไฟล์ใหญ่เกิน 5MB';
        } else {
            $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $filename = 'product_' . $id . '_' . time() . '.' . $ext;
            $dir = __DIR__ . '/../uploads/products/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $dir . $filename)) {
                $image = $filename;
            }
        }
    }

    if (!$error && $name && $price > 0) {
        $pdo->prepare("UPDATE products SET name=?, price=?, description=?, category_id=?, image=? WHERE id=?")
            ->execute([$name, $price, $desc, $cat_id, $image, $id]);
        flash('อัปเดตสินค้าสำเร็จ', 'success');
        header('Location: admin.php'); exit;
    } elseif (!$error) {
        $error = 'กรุณากรอกข้อมูลให้ครบ';
    }
}

include '../header.php';
?>
<div style="margin-bottom:1rem;"><a href="admin.php" class="btn btn-outline btn-sm">← กลับ</a></div>
<div class="page-title">✏️ แก้ไขสินค้า</div>
<div class="card" style="max-width:560px;">
    <?php if ($error): ?><div class="flash flash-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label class="form-label">ชื่อสินค้า *</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($p['name']) ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">ราคา (coin) *</label>
            <input type="number" name="price" class="form-control" step="0.01" value="<?= $p['price'] ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">หมวดหมู่</label>
            <select name="category_id" class="form-control">
                <option value="">-- ไม่ระบุ --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $p['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['icon'] . ' ' . $cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">คำอธิบาย</label>
            <textarea name="description" class="form-control"><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">รูปสินค้า</label>
            <?php if ($p['image']): ?>
                <div style="margin-bottom:.75rem;">
                    <img src="<?= BASE_URL ?>/uploads/products/<?= htmlspecialchars($p['image']) ?>"
                         style="max-height:100px; border-radius:8px; border:1px solid var(--border);">
                    <div style="color:var(--muted); font-size:.8rem; margin-top:.25rem;">รูปปัจจุบัน</div>
                </div>
            <?php endif; ?>
            <input type="file" name="product_image" class="form-control" accept="image/*">
            <small style="color:var(--muted); font-size:.78rem;">JPG, PNG, WEBP ขนาดไม่เกิน 5MB | แนะนำ 400x400px</small>
        </div>
        <div style="display:flex; gap:.75rem;">
            <button type="submit" class="btn btn-primary">💾 บันทึก</button>
            <a href="admin.php" class="btn btn-outline">ยกเลิก</a>
        </div>
    </form>
</div>
<?php include '../footer.php'; ?>
