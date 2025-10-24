<?php
require_once '../config.php';
require_once '../database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $telegram_bot_token = cleanInput($_POST['telegram_bot_token']);
    $telegram_chat_id = cleanInput($_POST['telegram_chat_id']);
    
    $config_file = '../config.php';
    $config_content = file_get_contents($config_file);
    
    // Update Telegram bot token
    $config_content = preg_replace(
        '/\$telegram_bot_token = [^;]+;/',
        "\$telegram_bot_token = '$telegram_bot_token';",
        $config_content
    );
    
    // Update Telegram chat ID
    $config_content = preg_replace(
        '/\$telegram_chat_id = [^;]+;/',
        "\$telegram_chat_id = '$telegram_chat_id';",
        $config_content
    );
    
    if (file_put_contents($config_file, $config_content)) {
        $_SESSION['success_message'] = 'Cập nhật cài đặt Telegram thành công!';
        
        // Test Telegram connection
        $test_message = "🔧 <b>Test Connection</b>\n\nTelegram API đã được cấu hình thành công!\n\nThời gian: " . date('d/m/Y H:i:s');
        $result = sendTelegramNotification($test_message);
        
        if ($result) {
            $_SESSION['success_message'] .= ' Tin nhắn test đã được gửi!';
        } else {
            $_SESSION['error_message'] = ' Cấu hình thành công nhưng không thể gửi tin nhắn test. Vui lòng kiểm tra lại token và chat ID.';
        }
    } else {
        $_SESSION['error_message'] = 'Cập nhật thất bại. Vui lòng kiểm tra quyền file.';
    }
    
    redirect('settings.php');
}

// Get current settings
$telegram_bot_token = getTelegramBotToken();
$telegram_chat_id = getTelegramChatId();

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

ob_start();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold">
                        <i class="fas fa-cog mr-2"></i>Admin Panel
                    </h1>
                </div>
                
                <div class="flex items-center space-x-6">
                    <a href="../index.php" class="hover:text-cyan-300 transition">
                        <i class="fas fa-home mr-1"></i>Trang chủ
                    </a>
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-user-circle text-xl"></i>
                        <span><?= $_SESSION['username'] ?></span>
                    </div>
                    <a href="../logout.php" class="hover:text-cyan-300 transition">
                        <i class="fas fa-sign-out-alt mr-1"></i>Đăng xuất
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg min-h-screen">
            <nav class="p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="index.php" class="flex items-center space-x-3 text-gray-700 hover:bg-gray-100 p-3 rounded-lg transition">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="users.php" class="flex items-center space-x-3 text-gray-700 hover:bg-gray-100 p-3 rounded-lg transition">
                            <i class="fas fa-users"></i>
                            <span>Quản lý người dùng</span>
                        </a>
                    </li>
                    <li>
                        <a href="orders.php" class="flex items-center space-x-3 text-gray-700 hover:bg-gray-100 p-3 rounded-lg transition">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Quản lý đơn hàng</span>
                        </a>
                    </li>
                    <li>
                        <a href="deposits.php" class="flex items-center space-x-3 text-gray-700 hover:bg-gray-100 p-3 rounded-lg transition">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Quản lý nạp tiền</span>
                        </a>
                    </li>
                    <li>
                        <a href="packages.php" class="flex items-center space-x-3 text-gray-700 hover:bg-gray-100 p-3 rounded-lg transition">
                            <i class="fas fa-box"></i>
                            <span>Quản lý gói VPS</span>
                        </a>
                    </li>
                    <li>
                        <a href="renewals.php" class="flex items-center space-x-3 text-gray-700 hover:bg-gray-100 p-3 rounded-lg transition">
                            <i class="fas fa-redo"></i>
                            <span>Lịch sử gia hạn</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php" class="flex items-center space-x-3 text-cyan-600 bg-cyan-50 p-3 rounded-lg">
                            <i class="fas fa-cog"></i>
                            <span>Cài đặt</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Cài đặt hệ thống</h1>
                <p class="text-gray-600">Quản lý cài đặt và cấu hình hệ thống</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                <?= htmlspecialchars($success_message) ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">
                                <?= htmlspecialchars($error_message) ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Telegram Settings -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">
                        <i class="fab fa-telegram-plane mr-2 text-blue-500"></i>Cài đặt Telegram
                    </h2>
                    
                    <form method="POST">
                        <div class="space-y-4">
                            <div>
                                <label for="telegram_bot_token" class="block text-sm font-medium text-gray-700 mb-2">
                                    Bot Token *
                                </label>
                                <input type="text" name="telegram_bot_token" id="telegram_bot_token" required
                                       value="<?= htmlspecialchars($telegram_bot_token) ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500"
                                       placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz">
                                <p class="mt-1 text-sm text-gray-500">
                                    Nhập token của Telegram Bot
                                </p>
                            </div>
                            
                            <div>
                                <label for="telegram_chat_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Chat ID *
                                </label>
                                <input type="text" name="telegram_chat_id" id="telegram_chat_id" required
                                       value="<?= htmlspecialchars($telegram_chat_id) ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500"
                                       placeholder="-123456789">
                                <p class="mt-1 text-sm text-gray-500">
                                    Chat ID để nhận thông báo
                                </p>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" 
                                    class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg font-semibold transition">
                                <i class="fas fa-save mr-2"></i>Lưu cài đặt Telegram
                            </button>
                        </div>
                    </form>
                </div>

                <!-- System Info -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">
                        <i class="fas fa-info-circle mr-2 text-cyan-500"></i>Thông tin hệ thống
                    </h2>
                    
                    <div class="space-y-4">
                        <div class="border-b pb-3">
                            <h3 class="font-semibold text-gray-800 mb-2">Hướng dẫn setup Telegram Bot</h3>
                            <ol class="text-sm text-gray-600 space-y-2 list-decimal list-inside">
                                <li>Mở Telegram và tìm @BotFather</li>
                                <li>Gửi lệnh <code class="bg-gray-100 px-1 rounded">/newbot</code></li>
                                <li>Tên bot: VPS Store Bot</li>
                                <li>Username bot: vpsstore_bot (phải kết thúc bằng _bot)</li>
                                <li>Sao chép Bot Token được cung cấp</li>
                                <li>Thêm bot vào nhóm và lấy Chat ID (bắt đầu bằng -100)</li>
                            </ol>
                        </div>
                        
                        <div class="border-b pb-3">
                            <h3 class="font-semibold text-gray-800 mb-2">Cách lấy Chat ID</h3>
                            <ol class="text-sm text-gray-600 space-y-2 list-decimal list-inside">
                                <li>Thêm bot vào nhóm nhận thông báo</li>
                                <li>Gửi một tin nhắn bất kỳ vào nhóm</li>
                                <li>Truy cập: <code class="bg-gray-100 px-1 rounded">https://api.telegram.org/bot[TOKEN]/getUpdates</code></li>
                                <li>Tìm <code class="bg-gray-100 px-1 rounded">chat.id</code> trong kết quả</li>
                            </ol>
                        </div>
                        
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-2">Các thông báo sẽ gửi</h3>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li><i class="fas fa-check text-green-500 mr-2"></i>Đơn hàng VPS mới</li>
                                <li><i class="fas fa-check text-green-500 mr-2"></i>Yêu cầu gia hạn VPS</li>
                                <li><i class="fas fa-check text-green-500 mr-2"></i>Yêu cầu nạp tiền</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test Connection -->
            <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-plug mr-2 text-green-500"></i>Kiểm tra kết nối
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-database text-green-600 text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-1">Database</h3>
                        <p class="text-sm text-gray-600">
                            <?php
                            $db_test = fetchOne("SELECT 1 as test");
                            echo $db_test ? 'Kết nối thành công' : 'Lỗi kết nối';
                            ?>
                        </p>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fab fa-telegram text-blue-600 text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-1">Telegram API</h3>
                        <p class="text-sm text-gray-600">
                            <?php
                            if ($telegram_bot_token && $telegram_chat_id && 
                                $telegram_bot_token != 'YOUR_TELEGRAM_BOT_TOKEN' && 
                                $telegram_chat_id != 'YOUR_TELEGRAM_CHAT_ID') {
                                echo 'Đã cấu hình';
                            } else {
                                echo 'Chưa cấu hình';
                            }
                            ?>
                        </p>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-server text-purple-600 text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-1">Server Info</h3>
                        <p class="text-sm text-gray-600">
                            PHP: <?= PHP_VERSION ?><br>
                            Server: <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?>
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Auto-save functionality could be added here
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Settings page loaded');
        });
    </script>
</body>
</html>

<?php
$content = ob_get_clean();
echo $content;
?>