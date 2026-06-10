<?php
require_once '../config.php';
requireLogin();
requireAdmin();
$pageTitle = 'จัดการบัญชีธนาคาร - ' . SITE_NAME;

$pdo = getDB();

// สร้างตารางถ้ายังไม่มี
$pdo->exec("CREATE TABLE IF NOT EXISTS bank_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(100) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

// เพิ่มบัญชี
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bank'])) {
    $bank_name      = trim($_POST['bank_name'] ?? '');
    $account_number = trim($_POST['account_number'] ?? '');
    $account_name   = trim($_POST['account_name'] ?? '');
    if ($bank_name && $account_number && $account_name) {
        $pdo->prepare("INSERT INTO bank_accounts (bank_name, account_number, account_name) VALUES (?, ?, ?)")
            ->execute([$bank_name, $account_number, $account_name]);
        flash('เพิ่มบัญชีธนาคารสำเร็จ', 'success');
    }
    header('Location: bank_accounts.php'); exit;
}

// แก้ไขบัญชี
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_bank'])) {
    $id             = (int)$_POST['id'];
    $bank_name      = trim($_POST['bank_name'] ?? '');
    $account_number = trim($_POST['account_number'] ?? '');
    $account_name   = trim($_POST['account_name'] ?? '');
    $is_active      = isset($_POST['is_active']) ? 1 : 0;
    $pdo->prepare("UPDATE bank_accounts SET bank_name=?, account_number=?, account_name=?, is_active=? WHERE id=?")
        ->execute([$bank_name, $account_number, $account_name, $is_active, $id]);
    flash('อัปเดตบัญชีธนาคารสำเร็จ', 'success');
    header('Location: bank_accounts.php'); exit;
}

// ลบบัญชี
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM bank_accounts WHERE id = ?")->execute([(int)$_GET['delete']]);
    flash('ลบบัญชีธนาคารแล้ว', 'success');
    header('Location: bank_accounts.php'); exit;
}

$banks = $pdo->query("SELECT * FROM bank_accounts ORDER BY sort_order ASC, id ASC")->fetchAll();
$editBank = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM bank_accounts WHERE id = ?");
    $s->execute([(int)$_GET['edit']]);
    $editBank = $s->fetch();
}

// รายชื่อธนาคารไทย
$bankList = [
    'กสิกรไทย (KBANK)', 'ไทยพาณิชย์ (SCB)', 'กรุงเทพ (BBL)',
    'กรุงไทย (KTB)', 'กรุงศรี (BAY)', 'ทหารไทยธนชาต (TTB)',
    'ออมสิน', 'ธ.ก.ส.', 'ซีไอเอ็มบี (CIMB)', 'UOB', 'พร้อมเพย์'
];

include '../header.php';
?>
<div style="margin-bottom:1rem;"><a href="admin.php" class="btn btn-outline btn-sm">← กลับ Admin</a></div>
<div class="page-title">🏦 จัดการบัญชีธนาคาร</div>

<div class="grid-2">
    <!-- ฟอร์มเพิ่ม/แก้ไข -->
    <div class="card">
        <div class="card-header"><?= $editBank ? '✏️ แก้ไขบัญชี' : '➕ เพิ่มบัญชีธนาคาร' ?></div>
        <form method="POST">
            <input type="hidden" name="<?= $editBank ? 'edit_bank' : 'add_bank' ?>" value="1">
            <?php if ($editBank): ?>
                <input type="hidden" name="id" value="<?= $editBank['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label">ธนาคาร *</label>
                <select name="bank_name" class="form-control" required>
                    <option value="">-- เลือกธนาคาร --</option>
                    <?php foreach ($bankList as $b): ?>
                        <option value="<?= $b ?>" <?= ($editBank['bank_name'] ?? '') === $b ? 'selected' : '' ?>><?= $b ?></option>
                    <?php endforeach; ?>
                    <option value="อื่นๆ">อื่นๆ (พิมพ์เอง)</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">เลขบัญชี *</label>
                <input type="text" name="account_number" class="form-control"
                    value="<?= htmlspecialchars($editBank['account_number'] ?? '') ?>"
                    placeholder="เช่น 000-0-00000-0" required>
            </div>
            <div class="form-group">
                <label class="form-label">ชื่อบัญชี *</label>
                <input type="text" name="account_name" class="form-control"
                    value="<?= htmlspecialchars($editBank['account_name'] ?? '') ?>"
                    placeholder="เช่น นาย ชื่อ นามสกุล" required>
            </div>
            <?php if ($editBank): ?>
            <div class="form-group">
                <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                    <input type="checkbox" name="is_active" value="1" <?= $editBank['is_active'] ? 'checked' : '' ?>>
                    <span class="form-label" style="margin:0;">เปิดใช้งาน</span>
                </label>
            </div>
            <?php endif; ?>
            <div style="display:flex; gap:.75rem;">
                <button type="submit" class="btn btn-primary">💾 บันทึก</button>
                <?php if ($editBank): ?>
                    <a href="bank_accounts.php" class="btn btn-outline">ยกเลิก</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- รายการบัญชี -->
    <div class="card">
        <div class="card-header">บัญชีธนาคารทั้งหมด</div>
        <?php if (empty($banks)): ?>
            <p style="color:var(--muted); text-align:center; padding:1.5rem 0;">ยังไม่มีบัญชีธนาคาร</p>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:0.75rem;">
                <?php foreach ($banks as $b): ?>
                <div style="background:var(--bg3); border-radius:8px; padding:1rem; border:1px solid <?= $b['is_active'] ? 'var(--border)' : 'var(--danger)' ?>;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div style="font-weight:600; color:var(--text);">🏦 <?= htmlspecialchars($b['bank_name']) ?></div>
                            <div style="color:var(--gold); font-family:monospace; font-size:1rem; margin:.25rem 0;"><?= htmlspecialchars($b['account_number']) ?></div>
                            <div style="color:var(--muted); font-size:.85rem;"><?= htmlspecialchars($b['account_name']) ?></div>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:.4rem; align-items:flex-end;">
                            <span class="badge <?= $b['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                <?= $b['is_active'] ? '✅ เปิด' : '❌ ปิด' ?>
                            </span>
                            <div style="display:flex; gap:.4rem;">
                                <a href="?edit=<?= $b['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                                <a href="?delete=<?= $b['id'] ?>" class="btn btn-danger btn-sm"
                                   onclick="return confirm('ลบบัญชีนี้?')">🗑️</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../footer.php'; ?>
