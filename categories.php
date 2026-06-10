<?php
require_once '../config.php';
requireLogin();
requireAdmin();
$pageTitle = 'จัดการหมวดหมู่ - ' . SITE_NAME;

$pdo = getDB();

// สร้างตารางถ้ายังไม่มี
$pdo->exec("CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(10) DEFAULT '🎮',
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

// เพิ่ม column ถ้ายังไม่มี
try { $pdo->exec("ALTER TABLE products ADD COLUMN category_id INT DEFAULT NULL"); } catch(Exception $e) {}

// เพิ่มหมวดหมู่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cat'])) {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '🎮');
    $sort = (int)($_POST['sort_order'] ?? 0);
    if ($name) {
        $pdo->prepare("INSERT INTO categories (name, icon, sort_order) VALUES (?, ?, ?)")->execute([$name, $icon, $sort]);
        flash('เพิ่มหมวดหมู่สำเร็จ', 'success');
    }
    header('Location: categories.php'); exit;
}

// แก้ไข
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_cat'])) {
    $id   = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '🎮');
    $sort = (int)($_POST['sort_order'] ?? 0);
    $pdo->prepare("UPDATE categories SET name=?, icon=?, sort_order=? WHERE id=?")->execute([$name, $icon, $sort, $id]);
    flash('อัปเดตหมวดหมู่สำเร็จ', 'success');
    header('Location: categories.php'); exit;
}

// ลบ
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("UPDATE products SET category_id = NULL WHERE category_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
    flash('ลบหมวดหมู่แล้ว', 'success');
    header('Location: categories.php'); exit;
}

$cats = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count FROM categories c ORDER BY sort_order ASC, id ASC")->fetchAll();
$editCat = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $s->execute([(int)$_GET['edit']]);
    $editCat = $s->fetch();
}

include '../header.php';
?>
<div style="margin-bottom:1rem;"><a href="admin.php" class="btn btn-outline btn-sm">← กลับ Admin</a></div>
<div class="page-title">🗂️ จัดการหมวดหมู่</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><?= $editCat ? '✏️ แก้ไขหมวดหมู่' : '➕ เพิ่มหมวดหมู่' ?></div>
        <form method="POST">
            <input type="hidden" name="<?= $editCat ? 'edit_cat' : 'add_cat' ?>" value="1">
            <?php if ($editCat): ?><input type="hidden" name="id" value="<?= $editCat['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label class="form-label">ไอคอน (Emoji)</label>
                <input type="text" name="icon" class="form-control" value="<?= htmlspecialchars($editCat['icon'] ?? '🎮') ?>" placeholder="เช่น 🎮 💻 📱" maxlength="5">
            </div>
            <div class="form-group">
                <label class="form-label">ชื่อหมวดหมู่ *</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editCat['name'] ?? '') ?>" placeholder="เช่น เกม PC, Gift Card" required>
            </div>
            <div class="form-group">
                <label class="form-label">ลำดับการแสดง</label>
                <input type="number" name="sort_order" class="form-control" value="<?= $editCat['sort_order'] ?? 0 ?>" min="0">
            </div>
            <div style="display:flex; gap:.75rem;">
                <button type="submit" class="btn btn-primary">💾 บันทึก</button>
                <?php if ($editCat): ?><a href="categories.php" class="btn btn-outline">ยกเลิก</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header">หมวดหมู่ทั้งหมด</div>
        <?php if (empty($cats)): ?>
            <p style="color:var(--muted); text-align:center; padding:1.5rem 0;">ยังไม่มีหมวดหมู่</p>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:.6rem;">
                <?php foreach ($cats as $c): ?>
                <div style="background:var(--bg3); border-radius:8px; padding:.85rem 1rem; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <span style="font-size:1.3rem;"><?= htmlspecialchars($c['icon']) ?></span>
                        <strong style="margin-left:.5rem;"><?= htmlspecialchars($c['name']) ?></strong>
                        <span style="color:var(--muted); font-size:.82rem; margin-left:.5rem;">(<?= $c['product_count'] ?> สินค้า)</span>
                    </div>
                    <div style="display:flex; gap:.4rem;">
                        <a href="?edit=<?= $c['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                        <a href="?delete=<?= $c['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('ลบหมวดหมู่นี้? สินค้าในหมวดจะถูกย้ายไปไม่มีหมวด')">🗑️</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include '../footer.php'; ?>
