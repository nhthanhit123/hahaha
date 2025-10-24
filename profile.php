<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = 'Vui lòng đăng nhập để tiếp tục';
    $_SESSION['flash_type'] = 'warning';
    redirect('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

// Lấy thông tin user
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_destroy();
        redirect('login.php');
    }
    
} catch(PDOException $e) {
    die("Lỗi hệ thống");
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate thông tin cơ bản
    if (empty($full_name)) {
        $errors[] = 'Vui lòng nhập họ tên';
    }
    
    if (!empty($phone) && !isValidPhone($phone)) {
        $errors[] = 'Số điện thoại không hợp lệ';
    }
    
    // Nếu có đổi mật khẩu
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = 'Vui lòng nhập mật khẩu hiện tại';
        } elseif (!verifyPassword($current_password, $user['password'])) {
            $errors[] = 'Mật khẩu hiện tại không đúng';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'Mật khẩu xác nhận không khớp';
        }
    }
    
    if (empty($errors)) {
        try {
            // Cập nhật thông tin cơ bản
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$full_name, $phone, $address, $_SESSION['user_id']]);
            
            // Nếu có đổi mật khẩu
            if (!empty($new_password)) {
                $hashed_password = hashPassword($new_password);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
            }
            
            // Cập nhật session
            $_SESSION['user_name'] = $full_name;
            
            // Log hoạt động
            logActivity($_SESSION['user_id'], 'update_profile', "Cập nhật thông tin cá nhân");
            
            $_SESSION['flash_message'] = 'Cập nhật thông tin thành công!';
            $_SESSION['flash_type'] = 'success';
            redirect('profile.php');
            
        } catch(PDOException $e) {
            $errors[] = 'Lỗi khi cập nhật thông tin';
        }
    }
}

$page_title = "Hồ sơ cá nhân - " . SITE_NAME;
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Hồ sơ cá nhân</h1>
            
            <div class="bg-white rounded-lg shadow-lg p-6">
                <?php if (!empty($errors)): ?>
                    <div class="mb-4">
                        <?php foreach ($errors as $error): ?>
                            <?php echo showAlert($error, 'error'); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tên đăng nhập</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            <p class="text-xs text-gray-500 mt-1">Không thể thay đổi tên đăng nhập</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            <p class="text-xs text-gray-500 mt-1">Không thể thay đổi email</p>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Họ tên đầy đủ *</label>
                        <input type="text" name="full_name" required
                               value="<?php echo htmlspecialchars($_POST['full_name'] ?? $user['full_name']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Số điện thoại</label>
                        <input type="tel" name="phone"
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? $user['phone']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Nhập số điện thoại">
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Địa chỉ</label>
                        <textarea name="address" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Nhập địa chỉ của bạn"><?php echo htmlspecialchars($_POST['address'] ?? $user['address']); ?></textarea>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Số dư tài khoản</label>
                        <div class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-blue-50">
                            <span class="text-lg font-semibold text-blue-600"><?php echo formatMoney($user['balance']); ?></span>
                        </div>
                        <a href="deposit.php" class="text-sm text-blue-600 hover:text-blue-700 mt-1 inline-block">
                            <i class="fas fa-plus-circle mr-1"></i>Nạp tiền
                        </a>
                    </div>
                    
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Đổi mật khẩu</h3>
                        <p class="text-sm text-gray-600 mb-4">Để trống nếu không muốn đổi mật khẩu</p>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Mật khẩu hiện tại</label>
                            <input type="password" name="current_password"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Mật khẩu mới</label>
                                <input type="password" name="new_password"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Xác nhận mật khẩu mới</label>
                                <input type="password" name="confirm_password"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                            <i class="fas fa-save mr-2"></i>Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>