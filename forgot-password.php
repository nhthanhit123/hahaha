<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// Nếu đã đăng nhập thì chuyển về dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $errors[] = 'Vui lòng nhập email';
    } elseif (!isValidEmail($email)) {
        $errors[] = 'Email không hợp lệ';
    }
    
    if (empty($errors)) {
        try {
            // Kiểm tra email có tồn tại không
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Tạo token reset password
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Lưu token vào database (cần thêm cột reset_token và reset_token_expiry)
                // Hiện tại chưa có cột này, nên chỉ hiển thị thông báo
                
                // Gửi email reset password (cần cấu hình mail server)
                // Hiện tại chỉ log lại
                
                logActivity($user['id'], 'forgot_password', "Yêu cầu reset mật khẩu");
                
                $_SESSION['flash_message'] = 'Nếu email tồn tại trong hệ thống, bạn sẽ nhận được hướng dẫn đặt lại mật khẩu.';
                $_SESSION['flash_type'] = 'success';
                redirect('login.php');
                
            } else {
                // Vẫn hiển thị thông báo thành công để bảo mật
                $_SESSION['flash_message'] = 'Nếu email tồn tại trong hệ thống, bạn sẽ nhận được hướng dẫn đặt lại mật khẩu.';
                $_SESSION['flash_type'] = 'success';
                redirect('login.php');
            }
            
        } catch(PDOException $e) {
            $errors[] = 'Lỗi hệ thống, vui lòng thử lại';
        }
    }
}

$page_title = "Quên mật khẩu - " . SITE_NAME;
include 'includes/header.php';
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100">
                <i class="fas fa-key text-blue-600 text-xl"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Quên mật khẩu
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Nhập email của bạn để nhận hướng dẫn đặt lại mật khẩu
            </p>
        </div>
        
        <div class="bg-white rounded-lg shadow-lg p-8">
            <?php if (!empty($errors)): ?>
                <div class="mb-4">
                    <?php foreach ($errors as $error): ?>
                        <?php echo showAlert($error, 'error'); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form class="space-y-6" method="POST">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <div class="mt-1">
                        <input id="email" name="email" type="email" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-paper-plane text-blue-500 group-hover:text-blue-400"></i>
                        </span>
                        Gửi hướng dẫn
                    </button>
                </div>
            </form>
            
            <div class="mt-6 text-center">
                <a href="login.php" class="text-blue-600 hover:text-blue-500 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i>Quay lại đăng nhập
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>