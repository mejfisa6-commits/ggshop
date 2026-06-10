<?php
require_once 'config.php';
requireLogin();
$pageTitle = 'เติมเงิน - ' . SITE_NAME;

$error = '';
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

// ดึงบัญชีธนาคารที่เปิดใช้งาน
$bankAccounts = $pdo->query("SELECT * FROM bank_accounts WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)($_POST['amount'] ?? 0);
    $slipFile = $_FILES['slip'] ?? null;

    if ($amount < 1) {
        $error = 'กรุณาระบุจำนวนเงินที่ถูกต้อง (ขั้นต่ำ 1 บาท)';
    } elseif (!$slipFile || $slipFile['error'] !== UPLOAD_ERR_OK) {
        $error = 'กรุณาอัปโหลดสลิปการโอนเงิน';
    } else {
        // ตรวจสอบไฟล์รูปภาพ
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $slipFile['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            $error = 'ไฟล์ต้องเป็นรูปภาพ (JPG, PNG, GIF, WEBP) เท่านั้น';
        } elseif ($slipFile['size'] > 5 * 1024 * 1024) {
            $error = 'ไฟล์ขนาดใหญ่เกินไป (สูงสุด 5MB)';
        } else {
            // บันทึกไฟล์
            $ext = pathinfo($slipFile['name'], PATHINFO_EXTENSION);
            $filename = uniqid('slip_', true) . '.' . $ext;
            $uploadPath = __DIR__ . '/slips/' . $filename;

            if (!is_dir(__DIR__ . '/slips/')) {
                mkdir(__DIR__ . '/slips/', 0755, true);
            }

            if (move_uploaded_file($slipFile['tmp_name'], $uploadPath)) {
                $stmt = $pdo->prepare("INSERT INTO topups (user_id, amount, slip) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $amount, $filename]);
                flash('แจ้งเติมเงินสำเร็จ! รอ Admin ตรวจสอบสลิปของคุณ (ปกติใช้เวลาไม่เกิน 30 นาที)', 'success');
                header('Location: ' . BASE_URL . '/topup.php');
                exit;
            } else {
                $error = 'ไม่สามารถบันทึกไฟล์ได้ กรุณาลองใหม่';
            }
        }
    }
}

// ดึงประวัติการเติมเงิน
$history = $pdo->prepare("SELECT * FROM topups WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$history->execute([$_SESSION['user_id']]);
$topupHistory = $history->fetchAll();

include 'header.php';
?>
<div class="page-title">💰 เติมเงิน (Wallet)</div>

<div class="grid-2">
    <!-- ฟอร์มเติมเงิน -->
    <div class="card">
        <div class="card-header">แจ้งเติมเงิน</div>
        <?php if ($error): ?>
            <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div style="margin-bottom:1.25rem;">
            <div style="font-weight:600; color:var(--text); margin-bottom:0.75rem; font-size:0.9rem;">📲 โอนเงินมาที่บัญชีนี้</div>
            <?php if (empty($bankAccounts)): ?>
                <div style="background:var(--bg3); border-radius:8px; padding:1rem; color:var(--muted); font-size:0.88rem;">
                    ยังไม่มีบัญชีธนาคาร กรุณาติดต่อ Admin
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:0.6rem;">
                <?php foreach ($bankAccounts as $b): ?>
                    <div style="background:var(--bg3); border:1px solid var(--border); border-radius:8px; padding:0.85rem 1rem; font-size:0.88rem;">
                        <div style="font-weight:600; color:var(--text); margin-bottom:0.25rem;">🏦 <?= htmlspecialchars($b['bank_name']) ?></div>
                        <div style="color:var(--gold); font-family:monospace; font-size:1.05rem; letter-spacing:1px; cursor:pointer;"
                             onclick="navigator.clipboard.writeText('<?= htmlspecialchars($b['account_number']) ?>');this.style.color='var(--success)';setTimeout(()=>this.style.color='var(--gold)',1500)"
                             title="คลิกเพื่อคัดลอก">
                            <?= htmlspecialchars($b['account_number']) ?> 📋
                        </div>
                        <div style="color:var(--muted);"><?= htmlspecialchars($b['account_name']) ?></div>
                    </div>
                <?php endforeach; ?>
                </div>
                <div style="margin-top:0.6rem; font-size:0.8rem; color:var(--accent);">💡 คลิกเลขบัญชีเพื่อคัดลอก | อัตรา: 1 บาท = 1 coin</div>
            <?php endif; ?>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">จำนวนเงิน (บาท)</label>
                <input type="number" name="amount" class="form-control" placeholder="เช่น 100" min="1" step="0.01" value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">อัปโหลดสลิป</label>
                <input type="file" name="slip" class="form-control" accept="image/*" required>
                <small style="color:var(--muted); font-size:0.8rem;">รองรับ JPG, PNG, GIF, WEBP ขนาดไม่เกิน 5MB</small>
            </div>
            <button type="submit" class="btn btn-gold" style="width:100%;">📤 ส่งสลิป</button>
        </form>
    </div>

    <!-- ประวัติการเติมเงิน -->
    <div class="card">
        <div class="card-header">ประวัติการเติมเงิน</div>
        <?php if (empty($topupHistory)): ?>
            <p style="color:var(--muted); text-align:center; padding:1.5rem 0;">ยังไม่มีประวัติการเติมเงิน</p>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:0.75rem;">
                <?php foreach ($topupHistory as $t): ?>
                    <div style="background:var(--bg3); border-radius:8px; padding:0.85rem 1rem; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <div style="font-weight:600; color:var(--gold);"><?= formatCoin($t['amount']) ?> coin</div>
                            <div style="font-size:0.78rem; color:var(--muted);"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></div>
                            <?php if ($t['note']): ?>
                                <div style="font-size:0.78rem; color:var(--muted);">💬 <?= htmlspecialchars($t['note']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php
                            $badges = [
                                'pending'  => '<span class="badge badge-warning">⏳ รอตรวจสอบ</span>',
                                'approved' => '<span class="badge badge-success">✅ อนุมัติแล้ว</span>',
                                'rejected' => '<span class="badge badge-danger">❌ ปฏิเสธ</span>',
                            ];
                            echo $badges[$t['status']] ?? '';
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
