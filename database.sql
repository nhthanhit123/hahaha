-- HostGreen VPS管理系统数据库结构
-- 创建数据库
CREATE DATABASE IF NOT EXISTS hostgreen DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hostgreen;

-- 用户表
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    balance DECIMAL(10,2) DEFAULT 0.00,
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    remember_token VARCHAR(255),
    token_expiry DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- VPS套餐表
CREATE TABLE packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    cpu_cores INT NOT NULL,
    ram_gb INT NOT NULL,
    storage_gb INT NOT NULL,
    bandwidth_gb INT NOT NULL,
    price_monthly DECIMAL(10,2) NOT NULL,
    price_yearly DECIMAL(10,2),
    is_popular BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 订单表
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    billing_cycle ENUM('monthly', 'yearly') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'cancelled', 'expired') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_info TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
);

-- VPS服务表
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    order_id INT NOT NULL,
    domain VARCHAR(255),
    os VARCHAR(100),
    ip_address VARCHAR(45),
    status ENUM('pending', 'active', 'suspended', 'terminated') DEFAULT 'pending',
    billing_cycle ENUM('monthly', 'yearly') NOT NULL,
    next_due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- 充值记录表
CREATE TABLE deposits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    payment_info TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 系统设置表
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 会话表
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT NOT NULL,
    last_activity INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 活动日志表
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 插入默认管理员账户
INSERT INTO users (username, email, password, full_name, role, status) VALUES 
('admin', 'admin@hostgreen.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 'active');

-- 插入默认VPS套餐
INSERT INTO packages (name, description, cpu_cores, ram_gb, storage_gb, bandwidth_gb, price_monthly, price_yearly, is_popular, is_active) VALUES 
('VPS Starter', 'VPS khởi đầu phù hợp cho website cá nhân', 1, 2, 50, 1000, 120000.00, 1200000.00, FALSE, TRUE),
('VPS Business', 'VPS kinh doanh hiệu suất cao', 2, 4, 100, 2000, 250000.00, 2500000.00, TRUE, TRUE),
('VPS Enterprise', 'VPS doanh nghiệp mạnh mẽ', 4, 8, 200, 5000, 450000.00, 4500000.00, FALSE, TRUE);

-- 插入系统设置
INSERT INTO settings (setting_key, setting_value, description) VALUES 
('site_name', 'HostGreen', 'Tên website'),
('site_url', 'https://hostgreen.vn', 'Địa chỉ website'),
('site_email', 'support@hostgreen.vn', 'Email hỗ trợ'),
('site_phone', '1900-1234', 'Số điện thoại'),
('bank_name', 'Vietcombank', 'Tên ngân hàng'),
('bank_account', '1234567890', 'Số tài khoản'),
('bank_account_name', 'NGUYEN VAN A', 'Chủ tài khoản'),
('vps_setup_time', '24', 'Thời gian thiết lập VPS (giờ)'),
('min_deposit', '50000', 'Số tiền nạp tối thiểu'),
('currency', 'VND', 'Đơn vị tiền tệ');

-- 创建索引以提高查询性能
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_services_user_id ON services(user_id);
CREATE INDEX idx_services_status ON services(status);
CREATE INDEX idx_deposits_user_id ON deposits(user_id);
CREATE INDEX idx_deposits_status ON deposits(status);
CREATE INDEX idx_sessions_user_id ON sessions(user_id);
CREATE INDEX idx_sessions_last_activity ON sessions(last_activity);