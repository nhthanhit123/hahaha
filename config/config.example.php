<?php
// Cấu hình database
define('DB_HOST', 'localhost');
define('DB_NAME', 'vps_hosting');
define('DB_USER', 'root');
define('DB_PASS', '');

// Cấu hình Telegram
define('TELEGRAM_BOT_TOKEN', 'YOUR_BOT_TOKEN');
define('TELEGRAM_CHAT_ID', 'YOUR_CHAT_ID');

// Cấu hình hệ thống
define('SITE_NAME', 'VPS Hosting');
define('SITE_URL', 'http://localhost');
define('ADMIN_EMAIL', 'admin@example.com');

// Cấu hình thanh toán
define('BANK_INFO', [
    'vietcombank' => [
        'name' => 'Vietcombank',
        'account_number' => '1234567890',
        'account_name' => 'NGUYEN VAN A',
        'branch' => 'Chi nhánh Hà Nội'
    ],
    'techcombank' => [
        'name' => 'Techcombank',
        'account_number' => '0987654321',
        'account_name' => 'NGUYEN VAN A',
        'branch' => 'Chi nhánh Hà Nội'
    ],
    'acb' => [
        'name' => 'ACB',
        'account_number' => '1122334455',
        'account_name' => 'NGUYEN VAN A',
        'branch' => 'Chi nhánh Hà Nội'
    ]
]);

// Tỷ lệ cộng thêm giá VPS (%)
define('VPS_PRICE_MARGIN', 5);

// Session timeout (phút)
define('SESSION_TIMEOUT', 30);

// Múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Bật/tắt hiển thị lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kết nối database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}
?>