<?php
require_once 'config.php';
requireLogin();
$pageTitle = 'ประวัติการสั่งซื้อ - ' . SITE_NAME;

$pdo = getDB();
$orders = $pdo->prepare("
    SELECT o.*, p.name AS product_name
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE o.user_id = ?
    ORDER BY o.date DESC
");
$orders->execute([$_SESSION['user_id']]);
$orderList = $orders->fetchAll();

include 'header.php';
?>
<div class="page-title">📋 ประวัติการสั่งซื้อ</div>

<?php if (empty($orderList)): ?>
    <div class="card" style="text-align:center; padding:3rem;">
        <p style="font-size:3rem; margin-bottom:1rem;">🛒</p>
        <p style="color:var(--muted);">ยังไม่มีประวัติการสั่งซื้อ</p>
        <a href="index.php" class="btn btn-primary" style="margin-top:1rem;">ไปช้อปเลย</a>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>สินค้า</th>
                        <th>รหัส (Code)</th>
                        <th>ราคา</th>
                        <th>วันที่</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderList as $i => $o): ?>
                    <tr>
                        <td style="color:var(--muted); font-size:0.85rem;"><?= $i + 1 ?></td>
                        <td style="font-weight:500;"><?= htmlspecialchars($o['product_name']) ?></td>
                        <td>
                            <div style="
                                background: var(--bg3);
                                border: 1px dashed var(--accent);
                                border-radius: 6px;
                                padding: 0.35rem 0.75rem;
                                font-family: monospace;
                                font-size: 0.9rem;
                                color: var(--gold);
                                cursor: pointer;
                                display: inline-block;
                                user-select: all;
                            " title="คลิกเพื่อคัดลอก" onclick="
                                navigator.clipboard.writeText(this.textContent.trim());
                                this.style.borderColor='var(--success)';
                                setTimeout(()=>this.style.borderColor='var(--accent)',1500);
                            ">
                                <?= htmlspecialchars($o['code']) ?>
                            </div>
                        </td>
                        <td style="color:var(--gold); font-weight:600;"><?= formatCoin($o['price']) ?> 🪙</td>
                        <td style="color:var(--muted); font-size:0.85rem;"><?= date('d/m/Y H:i', strtotime($o['date'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <p style="color:var(--muted); font-size:0.82rem; margin-top:0.5rem;">💡 คลิกที่รหัสเพื่อคัดลอก</p>
<?php endif; ?>

<?php include 'footer.php'; ?>
