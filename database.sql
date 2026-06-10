-- ร้านขายรหัสเกม Database Schema
-- นำเข้าใน phpMyAdmin หรือ MySQL CLI

CREATE DATABASE IF NOT EXISTS gameshop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gameshop;

-- ตาราง users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    coin DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ตาราง products
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ตาราง product_codes
CREATE TABLE IF NOT EXISTS product_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    code VARCHAR(255) NOT NULL,
    status ENUM('available', 'sold') NOT NULL DEFAULT 'available',
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ตาราง orders
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    code VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- ตาราง topups
CREATE TABLE IF NOT EXISTS topups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    slip VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- สร้าง admin เริ่มต้น (password: admin1234)
INSERT INTO users (username, password, coin, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'admin');

-- ตัวอย่างสินค้า
INSERT INTO products (name, price, stock, description) VALUES
('Valorant Points 1000 VP', 149.00, 0, 'Valorant Points สำหรับซื้อไอเทมในเกม'),
('Steam Wallet 100 THB', 100.00, 0, 'บัตรเติมเงิน Steam มูลค่า 100 บาท'),
('Minecraft Java Edition', 699.00, 0, 'เกม Minecraft Java Edition ของแท้');

-- ตาราง bank_accounts (บัญชีธนาคารสำหรับรับเงิน)
CREATE TABLE IF NOT EXISTS bank_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(100) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ตัวอย่างบัญชีธนาคาร
INSERT INTO bank_accounts (bank_name, account_number, account_name) VALUES
('กสิกรไทย (KBANK)', 'XXX-X-XXXXX-X', 'GameShop Store');

-- ตาราง categories (หมวดหมู่สินค้า)
ALTER TABLE products ADD COLUMN IF NOT EXISTS category_id INT DEFAULT NULL;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(10) DEFAULT '🎮',
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO categories (name, icon, sort_order) VALUES
('เกม PC', '💻', 1),
('เกม Mobile', '📱', 2),
('Point / เครดิต', '💎', 3),
('Gift Card', '🎁', 4),
('Subscription', '⭐', 5);
