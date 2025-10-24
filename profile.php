<?php
require_once 'config.php';
require_once 'database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php?redirect=profile.php');
}

$user = getUser($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = cleanInput($_POST['full_name']);
    $phone = cleanInput($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = 'Vui lòng nhập họ tên';
    }
    
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = 'Vui lòng nhập mật khẩu hiện tại để đổi mật khẩu';
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Mật khẩu hiện tại không đúng';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'Mật khẩu xác nhận không khớp';
        }
    }
    
    if (empty($errors)) {
        $updateData = [
            'full_name' => $full_name,
            'phone' => $phone
        ];
        
        if (!empty($new_password)) {
            $updateData['password'] = $new_password;
        }
        
        if (updateUser($user['id'], $updateData)) {
            $_SESSION['full_name'] = $full_name;
            $_SESSION['success_message'] = 'Cập nhật thông tin thành công!';
            
            if (!empty($new_password)) {
                $_SESSION['success_message'] .= ' Mật khẩu đã được đổi.';
            }
            
            redirect('profile.php');
        } else {
            $errors[] = 'Cập nhật thất bại. Vui lòng thử lại.';
        }
    }
}

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$page_title = 'Hồ sơ cá nhân - ' . SITE_NAME;

ob_start();
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Hồ sơ cá nhân</h1>
                <p class="text-gray-600">Quản lý thông tin tài khoản của bạn</p>
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
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Profile Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Thông tin cá nhân</h2>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-circle text-red-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800">Lỗi cập nhật</h3>
                                        <div class="mt-2 text-sm text-red-700">
                                            <ul class="list-disc pl-5 space-y-1">
                                                <?php foreach ($errors as $error): ?>
                                                    <li><?= htmlspecialchars($error) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                                        Tên đăng nhập
                                    </label>
                                    <input type="text" id="username" value="<?= htmlspecialchars($user['username']) ?>" 
                                           disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-500">
                                    <p class="mt-1 text-sm text-gray-500">Không thể thay đổi tên đăng nhập</p>
                                </div>
                                
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                        Email
                                    </label>
                                    <input type="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" 
                                           disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-500">
                                    <p class="mt-1 text-sm text-gray-500">Không thể thay đổi email</p>
                                </div>
                                
                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Họ tên *
                                    </label>
                                    <input type="text" name="full_name" id="full_name" required
                                           value="<?= htmlspecialchars($user['full_name']) ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500">
                                </div>
                                
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        Số điện thoại
                                    </label>
                                    <input type="tel" name="phone" id="phone"
                                           value="<?= htmlspecialchars($user['phone']) ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500">
                                </div>
                            </div>
                            
                            <div class="mt-8 pt-8 border-t border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Đổi mật khẩu</h3>
                                <p class="text-sm text-gray-600 mb-4">Để trống nếu không muốn đổi mật khẩu</p>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            Mật khẩu hiện tại
                                        </label>
                                        <input type="password" name="current_password" id="current_password"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500">
                                    </div>
                                    
                                    <div>
                                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            Mật khẩu mới
                                        </label>
                                        <input type="password" name="new_password" id="new_password"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500">
                                    </div>
                                    
                                    <div>
                                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            Xác nhận mật khẩu mới
                                        </label>
                                        <input type="password" name="confirm_password" id="confirm_password"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-8">
                                <button type="submit" 
                                        class="bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 text-white py-2 px-6 rounded-lg font-semibold transition">
                                    <i class="fas fa-save mr-2"></i>Cập nhật thông tin
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Account Info -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Thông tin tài khoản</h3>
                        
                        <div class="space-y-4">
                            <div class="text-center">
                                <div class="w-20 h-20 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-full flex items-center justify-center text-white text-2xl font-bold mx-auto mb-3">
                                    <?= strtoupper(substr($user['username'], 0, 2)) ?>
                                </div>
                                <h4 class="font-semibold text-gray-900"><?= htmlspecialchars($user['full_name']) ?></h4>
                                <p class="text-sm text-gray-500">@<?= htmlspecialchars($user['username']) ?></p>
                            </div>
                            
                            <div class="border-t pt-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-600">Số dư:</span>
                                    <span class="font-bold text-cyan-600"><?= formatPrice($user['balance']) ?></span>
                                </div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-600">Vai trò:</span>
                                    <span class="font-medium">
                                        <?= $user['role'] == 'admin' ? 'Quản trị viên' : 'Thành viên' ?>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Trạng thái:</span>
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                                        <?= $user['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $user['status'] == 'active' ? 'Hoạt động' : 'Đã khóa' ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="border-t pt-4">
                                <div class="text-sm text-gray-500">
                                    <div class="mb-1">
                                        <i class="fas fa-calendar mr-2"></i>
                                        Ngày tham gia: <?= formatDate($user['created_at']) ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-clock mr-2"></i>
                                        Cập nhật: <?= formatDate($user['updated_at']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Thao tác nhanh</h3>
                        
                        <div class="space-y-3">
                            <a href="deposit.php" 
                               class="w-full bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg font-semibold transition text-center block">
                                <i class="fas fa-plus-circle mr-2"></i>Nạp tiền
                            </a>
                            
                            <a href="services.php" 
                               class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg font-semibold transition text-center block">
                                <i class="fas fa-server mr-2"></i>Quản lý VPS
                            </a>
                            
                            <a href="packages.php" 
                               class="w-full border border-cyan-500 hover:bg-cyan-50 text-cyan-500 py-2 px-4 rounded-lg font-semibold transition text-center block">
                                <i class="fas fa-shopping-cart mr-2"></i>Mua VPS mới
                            </a>
                        </div>
                    </div>
                    
                    <!-- Stats -->
                    <div class="bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg shadow-lg p-6 text-white">
                        <h3 class="text-lg font-bold mb-4">Thống kê</h3>
                        
                        <?php
                        $orderCount = count(getUserOrders($user['id']));
                        $depositCount = count(getUserDeposits($user['id']));
                        ?>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span>VPS đã mua:</span>
                                <span class="font-bold"><?= $orderCount ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Lần nạp tiền:</span>
                                <span class="font-bold"><?= $depositCount ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Tổng đã nạp:</span>
                                <span class="font-bold">
                                    <?php
                                    $totalDeposited = array_sum(array_column(
                                        array_filter(getUserDeposits($user['id']), function($d) { return $d['status'] == 'completed'; }),
                                        'amount'
                                    ));
                                    echo formatPrice($totalDeposited);
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword && confirmPassword !== newPassword) {
        this.setCustomValidity('Mật khẩu xác nhận không khớp');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('new_password').addEventListener('input', function() {
    const currentPassword = document.getElementById('current_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (this.value) {
        currentPassword.required = true;
        confirmPassword.required = true;
    } else {
        currentPassword.required = false;
        confirmPassword.required = false;
    }
});
</script>

<?php
$content = ob_get_clean();
include 'includes/header.php';
?>