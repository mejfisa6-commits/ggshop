<?php
require_once '../config.php';
requireLogin();
requireAdmin();

$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();

if ($p) {
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    flash('ลบสินค้า "' . $p['name'] . '" สำเร็จ', 'success');
} else {
    flash('ไม่พบสินค้า', 'error');
}

header('Location: ' . BASE_URL . '/admin/admin.php');
exit;
?>
