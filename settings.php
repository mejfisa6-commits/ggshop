<?php
require_once '../config.php';
requireLogin();
requireAdmin();
$pageTitle = 'ตั้งค่าเว็บไซต์ - ' . SITE_NAME;

$pdo = getDB();

// สร้างตาราง settings ถ้ายังไม่มี
$pdo->exec("CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(100) PRIMARY KEY,
    `value` TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB");

// ฟังก์ชันดึงค่า setting
function getSetting($pdo, $key, $default = '') {
    $s = $pdo->prepare("SELECT `value` FROM settings WHERE `key` = ?");
    $s->execute([$key]);
    $r = $s->fetch();
    return $r ? $r['value'] : $default;
}

// ฟังก์ชันบันทึก setting
function setSetting($pdo, $key, $value) {
    $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?")
        ->execute([$key, $value, $value]);
}

// ฟังก์ชันอัปโหลดรูป
function uploadImage($file, $folder, $prefix) {
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed)) return ['error' => 'ไฟล์ต้องเป็นรูปภาพเท่านั้น'];
    if ($file['size'] > 5 * 1024 * 1024) return ['error' => 'ไฟล์ใหญ่เกิน 5MB'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . '_' . time() . '.' . $ext;
    $dir = __DIR__ . '/../uploads/' . $folder . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        return ['success' => true, 'filename' => $filename];
    }
    return ['error' => 'อัปโหลดไม่สำเร็จ'];
}

// บันทึกการตั้งค่า
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? '';

    // ลบโลโก้
    if ($section === 'delete_logo') {
        $old = getSetting($pdo, 'shop_logo', '');
        if ($old && file_exists(__DIR__ . '/../uploads/shop/' . $old)) {
            unlink(__DIR__ . '/../uploads/shop/' . $old);
        }
        setSetting($pdo, 'shop_logo', '');
        flash('ลบโลโก้แล้ว', 'success');
        header('Location: settings.php'); exit;
    }

    // ลบพื้นหลัง
    if ($section === 'delete_bg') {
        $old = getSetting($pdo, 'shop_bg', '');
        if ($old && file_exists(__DIR__ . '/../uploads/shop/' . $old)) {
            unlink(__DIR__ . '/../uploads/shop/' . $old);
        }
        setSetting($pdo, 'shop_bg', '');
        flash('ลบรูปพื้นหลังแล้ว', 'success');
        header('Location: settings.php'); exit;
    }

    if ($section === 'shop') {
        setSetting($pdo, 'shop_name', trim($_POST['shop_name'] ?? ''));
        setSetting($pdo, 'shop_slogan', trim($_POST['shop_slogan'] ?? ''));
        setSetting($pdo, 'shop_color', $_POST['shop_color'] ?? '#6c63ff');
        setSetting($pdo, 'shop_bg_color', $_POST['shop_bg_color'] ?? '#0f1117');

        // อัปโหลดโลโก้
        if (!empty($_FILES['shop_logo']['name'])) {
            $r = uploadImage($_FILES['shop_logo'], 'shop', 'logo');
            if (isset($r['success'])) setSetting($pdo, 'shop_logo', $r['filename']);
            else flash($r['error'], 'error');
        }
        // อัปโหลดพื้นหลัง
        if (!empty($_FILES['shop_bg']['name'])) {
            $r = uploadImage($_FILES['shop_bg'], 'shop', 'bg');
            if (isset($r['success'])) setSetting($pdo, 'shop_bg', $r['filename']);
            else flash($r['error'], 'error');
        }
        flash('บันทึกการตั้งค่าร้านสำเร็จ', 'success');
    }

    header('Location: settings.php'); exit;
}

// ดึงค่าปัจจุบัน
$shopName   = getSetting($pdo, 'shop_name', 'GameShop');
$shopSlogan = getSetting($pdo, 'shop_slogan', 'รหัสของแท้ ส่งทันที รับรองไม่มีปัญหา');
$shopColor  = getSetting($pdo, 'shop_color', '#6c63ff');
$shopBgColor= getSetting($pdo, 'shop_bg_color', '#0f1117');
$shopLogo   = getSetting($pdo, 'shop_logo', '');
$shopBg     = getSetting($pdo, 'shop_bg', '');

include '../header.php';
?>
<div style="margin-bottom:1rem;"><a href="admin.php" class="btn btn-outline btn-sm">← กลับ Admin</a></div>
<div class="page-title">⚙️ ตั้งค่าเว็บไซต์</div>

<div class="grid-2">
    <!-- ตั้งค่าร้าน -->
    <div class="card">
        <div class="card-header">🏪 ตั้งค่าร้านค้า</div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="section" value="shop">

            <div class="form-group">
                <label class="form-label">ชื่อร้าน</label>
                <input type="text" name="shop_name" class="form-control" value="<?= htmlspecialchars($shopName) ?>" placeholder="GameShop">
            </div>

            <div class="form-group">
                <label class="form-label">สโลแกน / คำโฆษณา</label>
                <input type="text" name="shop_slogan" class="form-control" value="<?= htmlspecialchars($shopSlogan) ?>" placeholder="รหัสของแท้ ส่งทันที">
            </div>

            <div class="grid-2" style="gap:.75rem;">
                <div class="form-group">
                    <label class="form-label">สีหลัก</label>
                    <div style="display:flex; gap:.5rem; align-items:center;">
                        <input type="color" name="shop_color" value="<?= htmlspecialchars($shopColor) ?>"
                            style="width:50px; height:38px; border:none; border-radius:6px; cursor:pointer; background:none;">
                        <input type="text" id="color_text" value="<?= htmlspecialchars($shopColor) ?>"
                            class="form-control" style="flex:1;" oninput="document.querySelector('[name=shop_color]').value=this.value">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">สีพื้นหลัง</label>
                    <div style="display:flex; gap:.5rem; align-items:center;">
                        <input type="color" name="shop_bg_color" value="<?= htmlspecialchars($shopBgColor) ?>"
                            style="width:50px; height:38px; border:none; border-radius:6px; cursor:pointer; background:none;">
                        <input type="text" id="bg_text" value="<?= htmlspecialchars($shopBgColor) ?>"
                            class="form-control" style="flex:1;" oninput="document.querySelector('[name=shop_bg_color]').value=this.value">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">โลโก้ / รูปชื่อร้าน</label>
                <?php if ($shopLogo): ?>
                    <div style="display:flex; align-items:center; gap:.75rem; margin-bottom:.75rem; background:var(--bg3); padding:.75rem; border-radius:8px;">
                        <img src="<?= BASE_URL ?>/uploads/shop/<?= htmlspecialchars($shopLogo) ?>" style="max-height:50px; max-width:120px; object-fit:contain; border-radius:6px;">
                        <div style="flex:1; font-size:.82rem; color:var(--muted);">โลโก้ปัจจุบัน</div>
                        <form method="POST" onsubmit="return confirm('ลบโลโก้ออก?')">
                            <input type="hidden" name="section" value="delete_logo">
                            <button type="submit" class="btn btn-danger btn-sm">🗑️ ลบโลโก้</button>
                        </form>
                    </div>
                <?php endif; ?>
                <input type="file" name="shop_logo" class="form-control" accept="image/*">
                <small style="color:var(--muted); font-size:.78rem;">PNG แบบพื้นหลังโปร่งใสดูดีที่สุด</small>
            </div>

            <div class="form-group">
                <label class="form-label">รูปพื้นหลัง</label>
                <?php if ($shopBg): ?>
                    <div style="margin-bottom:.75rem; background:var(--bg3); padding:.75rem; border-radius:8px;">
                        <img src="<?= BASE_URL ?>/uploads/shop/<?= htmlspecialchars($shopBg) ?>" style="max-height:80px; border-radius:6px; width:100%; object-fit:cover; margin-bottom:.5rem;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="color:var(--muted); font-size:.82rem;">พื้นหลังปัจจุบัน</span>
                            <form method="POST" onsubmit="return confirm('ลบรูปพื้นหลังออก?')">
                                <input type="hidden" name="section" value="delete_bg">
                                <button type="submit" class="btn btn-danger btn-sm">🗑️ ลบพื้นหลัง</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                <input type="file" name="shop_bg" class="form-control" accept="image/*">
                <small style="color:var(--muted); font-size:.78rem;">แนะนำขนาด 1920x400px หรือกว้างกว่า</small>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;">💾 บันทึกการตั้งค่า</button>
        </form>
    </div>

    <!-- Preview -->
    <div>
        <div class="card">
            <div class="card-header">👁️ ตัวอย่างหน้าร้าน</div>
            <div id="preview-banner" style="
                border-radius:10px; padding:2rem 1rem; text-align:center;
                background: <?= $shopBg ? 'url(' . BASE_URL . '/uploads/shop/' . $shopBg . ') center/cover' : 'linear-gradient(135deg, #1a1d27, #0f1117)' ?>;
                border: 1px solid var(--border); margin-bottom:1rem;
            ">
                <?php if ($shopLogo): ?>
                    <img src="<?= BASE_URL ?>/uploads/shop/<?= htmlspecialchars($shopLogo) ?>" style="max-height:60px; margin-bottom:.75rem;">
                <?php else: ?>
                    <div id="preview-name" style="font-size:1.8rem; font-weight:800; color:<?= htmlspecialchars($shopColor) ?>;">🎮 <?= htmlspecialchars($shopName) ?></div>
                <?php endif; ?>
                <div id="preview-slogan" style="color:#8b8fa8; margin-top:.4rem; font-size:.9rem;"><?= htmlspecialchars($shopSlogan) ?></div>
            </div>
            <div style="font-size:.82rem; color:var(--muted); text-align:center;">Preview อัปเดตหลังบันทึก</div>
        </div>

        <div class="card" style="margin-top:1rem;">
            <div class="card-header">🎨 สีที่แนะนำ</div>
            <div style="display:grid; grid-template-columns:repeat(5,1fr); gap:.5rem;">
                <?php
                $presetColors = [
                    ['#6c63ff','m#1a1d27','💜 Default'],
                    ['#ff6b6b','#1a0f0f','❤️ Red'],
                    ['#4ade80','#0f1a12','💚 Green'],
                    ['#ffd700','#1a1600','💛 Gold'],
                    ['#38bdf8','#0f1520','💙 Blue'],
                ];
                foreach ($presetColors as $c): ?>
                <button type="button" onclick="applyColor('<?= $c[0] ?>','<?= substr($c[1],1) ?>')"
                    style="background:<?= $c[0] ?>; border:none; border-radius:8px; height:36px; cursor:pointer; transition:.2s;"
                    title="<?= $c[2] ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('[name=shop_color]').addEventListener('input', function() {
    document.getElementById('color_text').value = this.value;
});
document.querySelector('[name=shop_bg_color]').addEventListener('input', function() {
    document.getElementById('bg_text').value = this.value;
});
function applyColor(accent, bg) {
    document.querySelector('[name=shop_color]').value = accent;
    document.getElementById('color_text').value = accent;
    document.querySelector('[name=shop_bg_color]').value = '#' + bg;
    document.getElementById('bg_text').value = '#' + bg;
}
</script>

<?php include '../footer.php'; ?>
