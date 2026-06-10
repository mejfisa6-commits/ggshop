<?php
require_once 'config.php';
$pageTitle = 'เข้าสู่ระบบ - ' . SITE_NAME;

if (isLoggedIn()) { header('Location: ' . BASE_URL); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            flash('ยินดีต้อนรับ ' . $user['username'] . '!', 'success');
            $redirect = $user['role'] === 'admin' ? BASE_URL . '/admin/admin.php' : BASE_URL . '/index.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    } else {
        $error = 'กรุณากรอกข้อมูลให้ครบ';
    }
}

include 'header.php';
?>
<div style="max-width:400px; margin: 2rem auto;">
    <div class="card">
        <div class="card-header" style="text-align:center; font-size:1.2rem;">🔐 เข้าสู่ระบบ</div>
        <?php if ($error): ?>
            <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">ชื่อผู้ใช้</label>
                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autofocus required>
            </div>
            <div class="form-group">
                <label class="form-label">รหัสผ่าน</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:.5rem;">เข้าสู่ระบบ</button>
        </form>
        <p style="text-align:center; margin-top:1rem; color:var(--muted); font-size:.88rem;">
            ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a>
        </p>
    </div>
</div>
<?php include 'footer.php'; ?>
