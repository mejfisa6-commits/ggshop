<?php
require_once 'config.php';
requireLogin();
$pageTitle = 'ซื้อสำเร็จ - ' . SITE_NAME;

if (!isset($_SESSION['last_codes'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
$data = $_SESSION['last_codes'];
unset($_SESSION['last_codes']);

include 'header.php';
?>
<div style="max-width:560px; margin: 2rem auto; text-align:center;">
    <div style="font-size:4rem; margin-bottom:1rem;">🎉</div>
    <h2 style="color:var(--success); margin-bottom:0.5rem;">ซื้อสำเร็จแล้ว!</h2>
    <p style="color:var(--muted); margin-bottom:0.25rem;"><?= htmlspecialchars($data['name']) ?></p>
    <p style="color:var(--muted); font-size:0.88rem; margin-bottom:2rem;">
        จำนวน <?= $data['quantity'] ?> ชิ้น | รวม <span style="color:var(--gold);"><?= formatCoin($data['total']) ?> 🪙</span>
    </p>

    <div class="card" style="background: var(--bg3); text-align:left;">
        <div style="font-size:0.85rem; color:var(--muted); margin-bottom:0.75rem; text-align:center;">รหัสของคุณ (<?= count($data['codes']) ?> รายการ)</div>
        <div style="display:flex; flex-direction:column; gap:0.5rem;">
            <?php foreach ($data['codes'] as $i => $code): ?>
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <span style="color:var(--muted); font-size:0.8rem; min-width:20px;"><?= $i+1 ?>.</span>
                <div style="
                    flex:1;
                    background: var(--bg2);
                    border: 2px dashed var(--accent);
                    border-radius: 8px;
                    padding: 0.75rem 1rem;
                    font-size: 1.1rem;
                    font-weight: 700;
                    font-family: monospace;
                    letter-spacing: 2px;
                    color: var(--gold);
                    word-break: break-all;
                    cursor: pointer;
                    user-select: all;
                " title="คลิกเพื่อคัดลอก" onclick="copyCode(this)">
                    <?= htmlspecialchars($code) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <p style="color:var(--muted); font-size:0.8rem; margin-top:1rem; text-align:center;" id="copy-msg">คลิกที่รหัสเพื่อคัดลอก</p>
    </div>

    <div style="margin-top:1.5rem; display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
        <a href="index.php" class="btn btn-primary">🛒 ซื้อต่อ</a>
        <a href="history.php" class="btn btn-outline">📋 ดูประวัติ</a>
    </div>

    <p style="margin-top:1.5rem; color:var(--muted); font-size:0.8rem;">
        ⚠️ กรุณาบันทึกรหัสเหล่านี้ไว้ ดูย้อนหลังได้ที่หน้าประวัติ
    </p>
</div>
<script>
function copyCode(el) {
    navigator.clipboard.writeText(el.textContent.trim()).then(() => {
        document.getElementById('copy-msg').textContent = '✅ คัดลอกแล้ว!';
        el.style.borderColor = 'var(--success)';
        setTimeout(() => {
            document.getElementById('copy-msg').textContent = 'คลิกที่รหัสเพื่อคัดลอก';
            el.style.borderColor = 'var(--accent)';
        }, 2000);
    });
}
</script>
<?php include 'footer.php'; ?>
