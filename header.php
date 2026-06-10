<?php
// header.php - ใช้ include ในทุกหน้า
$flash = getFlash();
$_shopName   = setting('shop_name', 'GameShop');
$_shopSlogan = setting('shop_slogan', 'รหัสของแท้ ส่งทันที รับรองไม่มีปัญหา');
$_shopColor  = setting('shop_color', '#6c63ff');
$_shopBg     = setting('shop_bg_color', '#0f1117');
$_shopBgImg  = setting('shop_bg', '');
$_shopLogo   = setting('shop_logo', '');

// คำนวณสี bg2, bg3 จาก bg หลัก (lighten นิดหน่อย)
function hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    return [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
}
function lighten($hex, $amount) {
    [$r,$g,$b] = hexToRgb($hex);
    return sprintf('#%02x%02x%02x', min(255,$r+$amount), min(255,$g+$amount), min(255,$b+$amount));
}
$_bg2 = lighten($_shopBg, 10);
$_bg3 = lighten($_shopBg, 18);
$_card = lighten($_shopBg, 13);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? $_shopName) ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --bg: <?= htmlspecialchars($_shopBg) ?>;
    --bg2: <?= htmlspecialchars($_bg2) ?>;
    --bg3: <?= htmlspecialchars($_bg3) ?>;
    --card: <?= htmlspecialchars($_card) ?>;
    --border: #2d3150;
    --accent: <?= htmlspecialchars($_shopColor) ?>;
    --accent2: #ff6b6b;
    --gold: #ffd700;
    --text: #e8e9f0;
    --muted: #8b8fa8;
    --success: #4ade80;
    --danger: #f87171;
    --warning: #facc15;
    --radius: 10px;
}
body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 15px; line-height: 1.6; min-height: 100vh; }
a { color: var(--accent); text-decoration: none; }
a:hover { opacity: 0.85; }
img { max-width: 100%; }

/* NAV */
.navbar {
    background: var(--bg2);
    border-bottom: 1px solid var(--border);
    padding: 0 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    height: 58px;
    position: sticky;
    top: 0;
    z-index: 100;
}
.navbar .logo { font-size: 1.3rem; font-weight: 700; color: var(--gold); letter-spacing: 1px; }
.navbar .logo span { color: var(--accent); }
.nav-links { display: flex; gap: 0.5rem; flex: 1; }
.nav-links a { padding: 0.4rem 0.9rem; border-radius: 6px; color: var(--muted); font-size: 0.9rem; transition: all .2s; }
.nav-links a:hover, .nav-links a.active { background: var(--bg3); color: var(--text); }
.nav-right { display: flex; align-items: center; gap: 1rem; margin-left: auto; }
.coin-badge { background: var(--bg3); border: 1px solid var(--border); padding: 0.35rem 0.9rem; border-radius: 20px; font-size: 0.85rem; }
.coin-badge .coin-val { color: var(--gold); font-weight: 600; }
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 0.45rem 1.1rem; border-radius: var(--radius); border: none; cursor: pointer; font-size: 0.9rem; font-weight: 500; transition: all .2s; text-decoration: none; }
.btn-primary { background: var(--accent); color: #fff; }
.btn-primary:hover { background: #5550e8; opacity: 1; }
.btn-danger { background: var(--danger); color: #fff; }
.btn-danger:hover { background: #e85555; opacity: 1; }
.btn-success { background: var(--success); color: #000; }
.btn-success:hover { background: #3dba6e; opacity: 1; }
.btn-outline { background: transparent; color: var(--text); border: 1px solid var(--border); }
.btn-outline:hover { background: var(--bg3); opacity: 1; }
.btn-gold { background: var(--gold); color: #000; }
.btn-gold:hover { background: #e6c200; opacity: 1; }
.btn-sm { padding: 0.3rem 0.75rem; font-size: 0.82rem; }

/* MAIN LAYOUT */
.container { max-width: 1100px; margin: 0 auto; padding: 1.5rem 1rem; }
.page-title { font-size: 1.6rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--text); }

/* CARDS */
.card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; margin-bottom: 1rem; }
.card-header { font-weight: 600; font-size: 1rem; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border); color: var(--text); }

/* FLASH MESSAGES */
.flash { padding: 0.85rem 1.2rem; border-radius: var(--radius); margin-bottom: 1rem; font-size: 0.9rem; }
.flash-success { background: rgba(74,222,128,0.12); border: 1px solid rgba(74,222,128,0.3); color: var(--success); }
.flash-error { background: rgba(248,113,113,0.12); border: 1px solid rgba(248,113,113,0.3); color: var(--danger); }
.flash-warning { background: rgba(250,204,21,0.12); border: 1px solid rgba(250,204,21,0.3); color: var(--warning); }

/* FORMS */
.form-group { margin-bottom: 1rem; }
.form-label { display: block; font-size: 0.88rem; color: var(--muted); margin-bottom: 0.4rem; font-weight: 500; }
.form-control {
    width: 100%; padding: 0.6rem 0.9rem;
    background: var(--bg2); border: 1px solid var(--border);
    border-radius: 8px; color: var(--text); font-size: 0.92rem;
    transition: border-color .2s;
}
.form-control:focus { outline: none; border-color: var(--accent); }
textarea.form-control { resize: vertical; min-height: 90px; }

/* TABLES */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
th { background: var(--bg3); padding: 0.7rem 1rem; text-align: left; color: var(--muted); font-weight: 600; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.5px; }
td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: rgba(108,99,255,0.04); }

/* BADGES */
.badge { display: inline-flex; align-items: center; padding: 0.2rem 0.65rem; border-radius: 20px; font-size: 0.78rem; font-weight: 600; }
.badge-success { background: rgba(74,222,128,0.15); color: var(--success); }
.badge-danger { background: rgba(248,113,113,0.15); color: var(--danger); }
.badge-warning { background: rgba(250,204,21,0.15); color: var(--warning); }
.badge-info { background: rgba(108,99,255,0.15); color: var(--accent); }

/* GRID */
.grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
.grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
.grid-4 { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 1.2rem; }
@media (max-width: 640px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<nav class="navbar">
    <a class="logo" href="<?= BASE_URL ?>">
        <?php if ($_shopLogo): ?>
            <img src="<?= BASE_URL ?>/uploads/shop/<?= htmlspecialchars($_shopLogo) ?>" style="max-height:36px; max-width:140px; object-fit:contain;">
        <?php else: ?>
            <span>🎮</span><?= htmlspecialchars($_shopName) ?>
        <?php endif; ?>
    </a>
    <div class="nav-links">
        <a href="<?= BASE_URL ?>/index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">🏠 หน้าหลัก</a>
        <?php if (isLoggedIn()): ?>
            <a href="<?= BASE_URL ?>/topup.php" class="<?= basename($_SERVER['PHP_SELF']) == 'topup.php' ? 'active' : '' ?>">💰 เติมเงิน</a>
            <a href="<?= BASE_URL ?>/history.php" class="<?= basename($_SERVER['PHP_SELF']) == 'history.php' ? 'active' : '' ?>">📋 ประวัติ</a>
            <?php if (isAdmin()): ?>
                <a href="<?= BASE_URL ?>/admin/admin.php" style="color: var(--gold);">👑 Admin</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="nav-right">
        <!-- ช่องค้นหา -->
        <form method="GET" action="<?= BASE_URL ?>/index.php" style="display:flex; align-items:center; gap:.4rem;">
            <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                placeholder="🔍 ค้นหาสินค้า..."
                style="background:var(--bg3); border:1px solid var(--border); border-radius:20px; padding:.35rem .9rem; color:var(--text); font-size:.85rem; width:180px; outline:none; transition:.2s;"
                onfocus="this.style.borderColor='var(--accent)'; this.style.width='220px'"
                onblur="this.style.borderColor='var(--border)'; this.style.width='180px'">
        </form>
        <?php if (isLoggedIn()):
            $pdo = getDB();
            $u = $pdo->prepare("SELECT coin FROM users WHERE id = ?");
            $u->execute([$_SESSION['user_id']]);
            $userData = $u->fetch();
            $cartCount = array_sum(array_column($_SESSION['cart'] ?? [], 'quantity'));
        ?>
            <!-- ไอคอนตะกร้า -->
            <a href="<?= BASE_URL ?>/cart.php" style="position:relative; text-decoration:none; font-size:1.3rem; padding:.2rem .3rem;" title="ตะกร้าสินค้า">
                🛒
                <?php if ($cartCount > 0): ?>
                <span style="
                    position:absolute; top:-4px; right:-4px;
                    background:var(--accent2); color:#fff;
                    border-radius:50%; width:18px; height:18px;
                    font-size:.65rem; font-weight:700;
                    display:flex; align-items:center; justify-content:center;
                    line-height:1;
                "><?= $cartCount > 99 ? '99+' : $cartCount ?></span>
                <?php endif; ?>
            </a>
            <div class="coin-badge">🪙 <span class="coin-val"><?= formatCoin($userData['coin'] ?? 0) ?></span> coin</div>
            <span style="color: var(--muted); font-size:.88rem;">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline btn-sm">ออกจากระบบ</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline btn-sm">เข้าสู่ระบบ</a>
            <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-sm">สมัครสมาชิก</a>
        <?php endif; ?>
    </div>
</nav>
<div class="container">
<?php if ($flash): ?>
    <div class="flash flash-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>
