<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// Kiểm tra đăng nhập và quyền admin
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['flash_message'] = 'Bạn không có quyền truy cập trang này';
    $_SESSION['flash_type'] = 'error';
    redirect('../login.php');
}

// Xử lý cập nhật cài đặt
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $setting_key = substr($key, 8); // Bỏ "setting_"
                
                // Kiểm tra setting đã tồn tại chưa
                $stmt = $pdo->prepare("SELECT id FROM settings WHERE setting_key = ?");
                $stmt->execute([$setting_key]);
                $exists = $stmt->fetch();
                
                if ($exists) {
                    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                    $stmt->execute([$value, $setting_key]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                    $stmt->execute([$setting_key, $value]);
                }
            }
        }
        
        $_SESSION['flash_message'] = 'Đã cập nhật cài đặt thành công';
        $_SESSION['flash_type'] = 'success';
        
    } catch(PDOException $e) {
        $_SESSION['flash_message'] = 'Lỗi khi cập nhật cài đặt: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'error';
    }
    
    redirect('settings.php');
}

// Lấy cài đặt hiện tại
$settings = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM settings");
    $stmt->execute();
    $settings_data = $stmt->fetchAll();
    
    foreach ($settings_data as $setting) {
        $settings[$setting['setting_key']] = $setting['setting_value'];
    }
    
} catch(PDOException $e) {
    // Bỏ qua lỗi
}

// Default values
$default_settings = [
    'site_name' => SITE_NAME,
    'site_email' => ADMIN_EMAIL,
    'telegram_bot_token' => '',
    'telegram_chat_id' => '',
    'vps_price_margin' => VPS_PRICE_MARGIN,
    'auto_renewal_enabled' => '1',
    'maintenance_mode' => '0',
    'registration_enabled' => '1',
    'deposit_min_amount' => '10000',
    'deposit_max_amount' => '50000000'
];

foreach ($default_settings as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

$page_title = "Cài đặt hệ thống - Admin";
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <!-- Admin Header -->
    <div class="bg-gray-800 text-white">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <i class="fas fa-cog text-2xl mr-3"></i>
                    <h1 class="text-xl font-bold">Admin Panel</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm">Xin chào, <?php echo $_SESSION['user_name']; ?></span>
                    <a href="../logout.php" class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded text-sm transition">
                        Đăng xuất
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Navigation -->
    <div class="bg-gray-700 text-white">
        <div class="container mx-auto px-4">
            <nav class="flex space-x-6">
                <a href="index.php" class="py-3 px-4 hover:bg-gray-600 transition">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <a href="users.php" class="py-3 px-4 hover:bg-gray-600 transition">
                    <i class="fas fa-users mr-2"></i>Người dùng
                </a>
                <a href="services.php" class="py-3 px-4 hover:bg-gray-600 transition">
                    <i class="fas fa-server mr-2"></i>Dịch vụ
                </a>
                <a href="orders.php" class="py-3 px-4 hover:bg-gray-600 transition">
                    <i class="fas fa-shopping-cart mr-2"></i>Đơn hàng
                </a>
                <a href="deposits.php" class="py-3 px-4 hover:bg-gray-600 transition">
                    <i class="fas fa-credit-card mr-2"></i>Nạp tiền
                </a>
                <a href="packages.php" class="py-3 px-4 hover:bg-gray-600 transition">
                    <i class="fas fa-box mr-2"></i>Gói dịch vụ
                </a>
                <a href="settings.php" class="py-3 px-4 hover:bg-gray-600 transition border-b-2 border-blue-500">
                    <i class="fas fa-cog mr-2"></i>Cài đặt
                </a>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-8">Cài đặt hệ thống</h1>
        
        <form method="POST" class="space-y-8">
            <!-- General Settings -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Cài đặt chung</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tên website</label>
                            <input type="text" name="setting_site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email liên hệ</label>
                            <input type="email" name="setting_site_email" value="<?php echo htmlspecialchars($settings['site_email']); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tỷ lệ cộng thêm giá VPS (%)</label>
                            <input type="number" name="setting_vps_price_margin" value="<?php echo htmlspecialchars($settings['vps_price_margin']); ?>" 
                                   min="0" max="100" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Số tiền nạp tối thiểu (VNĐ)</label>
                            <input type="number" name="setting_deposit_min_amount" value="<?php echo htmlspecialchars($settings['deposit_min_amount']); ?>" 
                                   min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Số tiền nạp tối đa (VNĐ)</label>
                            <input type="number" name="setting_deposit_max_amount" value="<?php echo htmlspecialchars($settings['deposit_max_amount']); ?>" 
                                   min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Telegram Settings -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Cài đặt Telegram</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Bot Token</label>
                            <input type="text" name="setting_telegram_bot_token" value="<?php echo htmlspecialchars($settings['telegram_bot_token']); ?>" 
                                   placeholder="Nhập Telegram Bot Token" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <p class="text-xs text-gray-500 mt-1">Lấy token từ @BotFather</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Chat ID</label>
                            <input type="text" name="setting_telegram_chat_id" value="<?php echo htmlspecialchars($settings['telegram_chat_id']); ?>" 
                                   placeholder="Nhập Chat ID" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <p class="text-xs text-gray-500 mt-1">Chat ID của nhóm hoặc channel nhận thông báo</p>
                        </div>
                    </div>
                    
                    <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                        <p class="text-sm text-blue-800">
                            <strong>Hướng dẫn:</strong><br>
                            1. Tạo bot qua @BotFather trên Telegram<br>
                            2. Lấy Bot Token và nhập vào ô trên<br>
                            3. Thêm bot vào nhóm/channel và lấy Chat ID<br>
                            4. Nhập Chat ID vào ô trên để nhận thông báo
                        </p>
                    </div>
                </div>
            </div>

            <!-- System Settings -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Cài đặt hệ thống</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="setting_auto_renewal_enabled" value="1" 
                                   <?php echo $settings['auto_renewal_enabled'] ? 'checked' : ''; ?> 
                                   class="mr-3 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="setting_auto_renewal_enabled" class="text-sm font-medium text-gray-700">
                                Bật tính năng tự động gia hạn
                            </label>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" name="setting_maintenance_mode" value="1" 
                                   <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?> 
                                   class="mr-3 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="setting_maintenance_mode" class="text-sm font-medium text-gray-700">
                                Chế độ bảo trì (Website sẽ tạm thời không truy cập được)
                            </label>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" name="setting_registration_enabled" value="1" 
                                   <?php echo $settings['registration_enabled'] ? 'checked' : ''; ?> 
                                   class="mr-3 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="setting_registration_enabled" class="text-sm font-medium text-gray-700">
                                Cho phép đăng ký tài khoản mới
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bank Settings -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Cài đặt ngân hàng</h2>
                    <p class="text-sm text-gray-600 mt-1">Cài đặt này được lưu trong file config/config.php</p>
                </div>
                <div class="p-6">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-sm text-yellow-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            Để cập nhật thông tin ngân hàng, vui lòng chỉnh sửa trực tiếp file <code>config/config.php</code>
                            trong mảng <code>BANK_INFO</code>.
                        </p>
                    </div>
                    
                    <div class="mt-4 space-y-2">
                        <?php foreach (BANK_INFO as $code => $bank): ?>
                            <div class="p-3 bg-gray-50 rounded">
                                <div class="font-medium"><?php echo $bank['name']; ?></div>
                                <div class="text-sm text-gray-600">
                                    STK: <?php echo $bank['account_number']; ?> - 
                                    Chủ TK: <?php echo $bank['account_name']; ?> - 
                                    Chi nhánh: <?php echo $bank['branch']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                    <i class="fas fa-save mr-2"></i>Lưu cài đặt
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>