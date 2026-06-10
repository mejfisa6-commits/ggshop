<?php
// ตั้งค่าฐานข้อมูล - แก้ตามเครื่องของคุณ
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gameshop');

// ตั้งค่าเว็บไซต์
define('SITE_NAME', 'GameShop');
define('BASE_URL', 'http://localhost/gameshop');
define('UPLOAD_DIR', __DIR__ . '/slips/');

// เชื่อมต่อฐานข้อมูล
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:2rem;color:red;">
                <h2>❌ ไม่สามารถเชื่อมต่อฐานข้อมูลได้</h2>
                <p>กรุณาตรวจสอบการตั้งค่าใน config.php และรันไฟล์ database.sql ก่อน</p>
                <code>' . htmlspecialchars($e->getMessage()) . '</code>
            </div>');
        }
    }
    return $pdo;
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function flash($msg, $type = 'success') {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function formatCoin($amount) {
    return number_format($amount, 2);
}

// ดึงการตั้งค่าเว็บไซต์จาก DB
function getSettings() {
    static $settings = null;
    if ($settings !== null) return $settings;
    try {
        $pdo = getDB();
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            `key` VARCHAR(100) PRIMARY KEY,
            `value` TEXT DEFAULT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        $rows = $pdo->query("SELECT `key`, `value` FROM settings")->fetchAll();
        $settings = [];
        foreach ($rows as $r) $settings[$r['key']] = $r['value'];
    } catch (Exception $e) {
        $settings = [];
    }
    return $settings;
}

function setting($key, $default = '') {
    $s = getSettings();
    return $s[$key] ?? $default;
}

session_start();
?>
