<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Nếu đã đăng nhập thì chuyển về dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate
    if (empty($username)) {
        $errors[] = 'Vui lòng nhập tên đăng nhập hoặc email';
    }
    
    if (empty($password)) {
        $errors[] = 'Vui lòng nhập mật khẩu';
    }
    
    if (empty($errors)) {
        try {
            // Kiểm tra user
            $user = getRow("SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'", [$username, $username]);
            
            if ($user && verifyPassword($password, $user['password'])) {
                // Đăng nhập thành công
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_balance'] = $user['balance'];
                
                // Remember me
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                    
                    updateData('users', [
                        'remember_token' => $token,
                        'token_expiry' => $expiry
                    ], 'id = ?', [$user['id']]);
                    
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
                }
                
                // Log hoạt động
                logActivity($user['id'], 'login', "Đăng nhập thành công");
                
                $_SESSION['flash_message'] = 'Đăng nhập thành công!';
                $_SESSION['flash_type'] = 'success';
                
                // Chuyển hướng
                $redirect = $_GET['redirect'] ?? 'dashboard.php';
                redirect($redirect);
                
            } else {
                $errors[] = 'Tên đăng nhập hoặc mật khẩu không đúng';
            }
            
        } catch(Exception $e) {
            $errors[] = 'Lỗi hệ thống, vui lòng thử lại';
        }
    }
}

// Kiểm tra remember token
if (!isLoggedIn() && isset($_COOKIE['remember_token'])) {
    try {
        $user = getRow("SELECT * FROM users WHERE remember_token = ? AND token_expiry > NOW() AND status = 'active'", [$_COOKIE['remember_token']]);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_balance'] = $user['balance'];
            
            logActivity($user['id'], 'login', "Đăng nhập tự động (remember me)");
            
            redirect('dashboard.php');
        }
    } catch(Exception $e) {
        // Xóa cookie nếu có lỗi
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

$page_title = "Đăng nhập - " . SITE_NAME;
include 'includes/header.php';
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100">
                <i class="fas fa-sign-in-alt text-blue-600 text-xl"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Đăng nhập tài khoản
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Chưa có tài khoản? <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500">Đăng ký ngay</a>
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
                    <label for="username" class="block text-sm font-medium text-gray-700">Tên đăng nhập hoặc Email</label>
                    <div class="mt-1">
                        <input id="username" name="username" type="text" required
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Mật khẩu</label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" required
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox" 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-900">
                            Ghi nhớ đăng nhập
                        </label>
                    </div>
                    
                    <div class="text-sm">
                        <a href="forgot-password.php" class="font-medium text-blue-600 hover:text-blue-500">
                            Quên mật khẩu?
                        </a>
                    </div>
                </div>
                
                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-lock text-blue-500 group-hover:text-blue-400"></i>
                        </span>
                        Đăng nhập
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>