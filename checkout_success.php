<?php
require_once 'config.php';
requireLogin();
$pageTitle = 'ชำระเงินสำเร็จ - ' . SITE_NAME;

if (!isset($_SESSION['last_codes'])) {
    header('Location: ' . BASE_URL . '/index.php'); exit;
}
$data = $_SESSION['last_codes'];
unset($_SESSION['last_codes']);

include 'header.php';
?>
<div style="max-width:600px; margin:2rem auto; text-align:center;">
    <div style="font-size:4rem; margin-bottom:1rem;">🎉</div>
    <h2 style="color:var(--success); margin-bottom:.5rem;">ชำระเงินสำเร็จ!</h2>
    <p style="color:var(--muted); margin-bottom:2rem;">รวม <span style="color:var(--gold);"><?= formatCoin($data['total']) ?> 🪙</span></p>

    <div class="card" style="text-align:left;">
        <div class="card-header">🎮 รหัสทั้งหมดของคุณ</div>
        <div style="display:flex; flex-direction:column; gap:.75rem;">
            <?php foreach ($data['codes_list'] as $i => $c): ?>
            <div style="background:var(--bg3); border-radius:8px; padding:.85rem 1rem;">
                <div style="font-size:.8rem; color:var(--muted); margin-bottom:.3rem;"><?= htmlspecialchars($c['name']) ?> — <?= formatCoin($c['price']) ?> 🪙</div>
                <div style="
                    background:var(--bg2); border:2px dashed var(--accent);
                    border-radius:6px; padding:.6rem 1rem;
                    font-family:monospace; font-size:1.05rem; font-weight:700;
                    color:var(--gold); letter-spacing:1px; cursor:pointer; user-select:all;
                " onclick="copyCode(this)" title="คลิกเพื่อคัดลอก">
                    <?= htmlspecialchars($c['code']) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <p style="color:var(--muted); font-size:.8rem; margin-top:1rem; text-align:center;">💡 คลิกที่รหัสเพื่อคัดลอก</p>
    </div>

    <div style="margin-top:1.5rem; display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
        <a href="index.php" class="btn btn-primary">🛒 ช้อปต่อ</a>
        <a href="history.php" class="btn btn-outline">📋 ดูประวัติ</a>
    </div>
</div>
<script>
function copyCode(el) {
    navigator.clipboard.writeText(el.textContent.trim()).then(() => {
        const orig = el.style.borderColor;
        el.style.borderColor = 'var(--success)';
        setTimeout(() => el.style.borderColor = orig, 1500);
    });
}
</script>
<?php include 'footer.php'; ?>
