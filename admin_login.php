<?php
require_once '../config.php';
$pageTitle = 'Admin Login - ' . SITE_NAME;

if (isAdmin()) { header('Location: ' . BASE_URL . '/admin/admin.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? AND role = 'admin'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            flash('ยินดีต้อนรับ Admin!', 'success');
            header('Location: ' . BASE_URL . '/admin/admin.php');
            exit;
        } else {
            $error = 'ข้อมูลไม่ถูกต้องหรือไม่มีสิทธิ์ Admin';
        }
    }
}

include '../header.php';
?>
<div style="max-width:380px; margin:2rem auto;">
    <div class="card">
        <div class="card-header" style="text-align:center; font-size:1.2rem; color:var(--gold);">👑 Admin Login</div>
        <?php if ($error): ?>
            <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">ชื่อผู้ใช้ Admin</label>
                <input type="text" name="username" class="form-control" autofocus required>
            </div>
            <div class="form-group">
                <label class="form-label">รหัสผ่าน</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-gold" style="width:100%;">เข้าสู่ระบบ Admin</button>
        </form>
        <p style="text-align:center; margin-top:1rem; font-size:0.82rem; color:var(--muted);">
            <a href="<?= BASE_URL ?>/login.php">← กลับหน้า Login ปกติ</a>
        </p>
    </div>
</div>
<?php include '../footer.php'; ?>
