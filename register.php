<?php
require_once 'config.php';
$pageTitle = 'สมัครสมาชิก - ' . SITE_NAME;

if (isLoggedIn()) { header('Location: ' . BASE_URL); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (strlen($username) < 3 || strlen($username) > 30) {
        $error = 'ชื่อผู้ใช้ต้องมี 3-30 ตัวอักษร';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'ชื่อผู้ใช้ใช้ได้เฉพาะตัวอักษร ตัวเลข และ _';
    } elseif (strlen($password) < 6) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    } elseif ($password !== $confirm) {
        $error = 'รหัสผ่านไม่ตรงกัน';
    } else {
        $pdo = getDB();
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetch()) {
            $error = 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $ins->execute([$username, $hash]);
            flash('สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ', 'success');
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
    }
}

include 'header.php';
?>
<div style="max-width:420px; margin: 2rem auto;">
    <div class="card">
        <div class="card-header" style="text-align:center; font-size:1.2rem;">🎮 สมัครสมาชิก</div>
        <?php if ($error): ?>
            <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">ชื่อผู้ใช้</label>
                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="a-z, 0-9, _ เท่านั้น" required>
            </div>
            <div class="form-group">
                <label class="form-label">รหัสผ่าน</label>
                <input type="password" name="password" class="form-control" placeholder="อย่างน้อย 6 ตัวอักษร" required>
            </div>
            <div class="form-group">
                <label class="form-label">ยืนยันรหัสผ่าน</label>
                <input type="password" name="confirm" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:.5rem;">สมัครสมาชิก</button>
        </form>
        <p style="text-align:center; margin-top:1rem; color:var(--muted); font-size:.88rem;">
            มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a>
        </p>
    </div>
</div>
<?php include 'footer.php'; ?>
