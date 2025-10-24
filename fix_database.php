<?php
// 数据库修复脚本
require_once 'includes/db.php';

echo "<h1>数据库修复脚本</h1>";

try {
    // 检查并创建所有必需的表
    $tables = [
        'users' => "
            CREATE TABLE IF NOT EXISTS users (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'packages' => "
            CREATE TABLE IF NOT EXISTS packages (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'orders' => "
            CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                package_id INT NOT NULL,
                billing_cycle ENUM('monthly', 'yearly') NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'paid', 'cancelled', 'expired') DEFAULT 'pending',
                payment_method VARCHAR(50),
                payment_info TEXT,
                order_code VARCHAR(50) UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'services' => "
            CREATE TABLE IF NOT EXISTS services (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                package_id INT NOT NULL,
                order_id INT NOT NULL,
                domain VARCHAR(255),
                os VARCHAR(100),
                ip_address VARCHAR(45),
                username VARCHAR(100),
                password VARCHAR(255),
                status ENUM('pending', 'active', 'suspended', 'terminated') DEFAULT 'pending',
                billing_cycle ENUM('monthly', 'yearly') NOT NULL,
                next_due_date DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'deposits' => "
            CREATE TABLE IF NOT EXISTS deposits (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'settings' => "
            CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'sessions' => "
            CREATE TABLE IF NOT EXISTS sessions (
                id VARCHAR(128) PRIMARY KEY,
                user_id INT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                payload TEXT NOT NULL,
                last_activity INT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'activity_logs' => "
            CREATE TABLE IF NOT EXISTS activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                action VARCHAR(100) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        "
    ];
    
    // 创建表
    foreach ($tables as $tableName => $sql) {
        try {
            executeQuery($sql);
            echo "<p>✅ 表 '$tableName' 创建成功或已存在</p>";
        } catch (Exception $e) {
            echo "<p>❌ 创建表 '$tableName' 失败: " . $e->getMessage() . "</p>";
        }
    }
    
    // 检查并插入默认数据
    echo "<h2>检查默认数据</h2>";
    
    // 检查管理员用户
    $adminCount = getRow("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")['count'];
    if ($adminCount == 0) {
        insertData('users', [
            'username' => 'admin',
            'email' => 'admin@hostgreen.vn',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Administrator',
            'role' => 'admin',
            'status' => 'active'
        ]);
        echo "<p>✅ 创建默认管理员账户: admin / password</p>";
    } else {
        echo "<p>✅ 管理员账户已存在</p>";
    }
    
    // 检查VPS套餐
    $packageCount = getRow("SELECT COUNT(*) as count FROM packages")['count'];
    if ($packageCount == 0) {
        $packages = [
            [
                'name' => 'VPS Starter',
                'description' => 'VPS khởi đầu phù hợp cho website cá nhân',
                'cpu_cores' => 1,
                'ram_gb' => 2,
                'storage_gb' => 50,
                'bandwidth_gb' => 1000,
                'price_monthly' => 120000.00,
                'price_yearly' => 1200000.00,
                'is_popular' => FALSE,
                'is_active' => TRUE
            ],
            [
                'name' => 'VPS Business',
                'description' => 'VPS kinh doanh hiệu suất cao',
                'cpu_cores' => 2,
                'ram_gb' => 4,
                'storage_gb' => 100,
                'bandwidth_gb' => 2000,
                'price_monthly' => 250000.00,
                'price_yearly' => 2500000.00,
                'is_popular' => TRUE,
                'is_active' => TRUE
            ],
            [
                'name' => 'VPS Enterprise',
                'description' => 'VPS doanh nghiệp mạnh mẽ',
                'cpu_cores' => 4,
                'ram_gb' => 8,
                'storage_gb' => 200,
                'bandwidth_gb' => 5000,
                'price_monthly' => 450000.00,
                'price_yearly' => 4500000.00,
                'is_popular' => FALSE,
                'is_active' => TRUE
            ]
        ];
        
        foreach ($packages as $package) {
            insertData('packages', $package);
        }
        echo "<p>✅ 创建默认VPS套餐</p>";
    } else {
        echo "<p>✅ VPS套餐已存在</p>";
    }
    
    // 检查系统设置
    $settingCount = getRow("SELECT COUNT(*) as count FROM settings")['count'];
    if ($settingCount == 0) {
        $settings = [
            ['setting_key' => 'site_name', 'setting_value' => 'HostGreen', 'description' => 'Tên website'],
            ['setting_key' => 'site_url', 'setting_value' => 'https://hostgreen.vn', 'description' => 'Địa chỉ website'],
            ['setting_key' => 'site_email', 'setting_value' => 'support@hostgreen.vn', 'description' => 'Email hỗ trợ'],
            ['setting_key' => 'site_phone', 'setting_value' => '1900-1234', 'description' => 'Số điện thoại'],
            ['setting_key' => 'bank_name', 'setting_value' => 'Vietcombank', 'description' => 'Tên ngân hàng'],
            ['setting_key' => 'bank_account', 'setting_value' => '1234567890', 'description' => 'Số tài khoản'],
            ['setting_key' => 'bank_account_name', 'setting_value' => 'NGUYEN VAN A', 'description' => 'Chủ tài khoản'],
            ['setting_key' => 'vps_setup_time', 'setting_value' => '24', 'description' => 'Thời gian thiết lập VPS (giờ)'],
            ['setting_key' => 'min_deposit', 'setting_value' => '50000', 'description' => 'Số tiền nạp tối thiểu'],
            ['setting_key' => 'currency', 'setting_value' => 'VND', 'description' => 'Đơn vị tiền tệ']
        ];
        
        foreach ($settings as $setting) {
            insertData('settings', $setting);
        }
        echo "<p>✅ 创建默认系统设置</p>";
    } else {
        echo "<p>✅ 系统设置已存在</p>";
    }
    
    echo "<h2>数据库修复完成！</h2>";
    echo "<p><a href='test_db.php'>测试数据库连接</a></p>";
    echo "<p><a href='index.php'>返回首页</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ 数据库修复失败: " . $e->getMessage() . "</p>";
}
?>