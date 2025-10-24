<?php
require_once 'config.php';
require_once 'database.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Vui lòng nhập tên đăng nhập hoặc email';
    }
    
    if (empty($password)) {
        $errors[] = 'Vui lòng nhập mật khẩu';
    }
    
    if (empty($errors)) {
        $user = getUserByUsername($username);
        
        if (!$user) {
            $user = getUserByEmail($username);
        }
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] == 'inactive') {
                $errors[] = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['balance'] = $user['balance'];
                
                if ($remember) {
                    setcookie('remember_user', $username, time() + (30 * 24 * 60 * 60), '/');
                }
                
                if ($user['role'] == 'admin') {
                    redirect('admin/');
                } else {
                    redirect('index.php');
                }
            }
        } else {
            $errors[] = 'Tên đăng nhập hoặc mật khẩu không đúng';
        }
    }
}

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$page_title = 'Đăng nhập - ' . SITE_NAME;

ob_start();
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-gradient-to-r from-cyan-500 to-blue-500">
                <i class="fas fa-sign-in-alt text-white text-xl"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Đăng nhập tài khoản
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Hoặc 
                <a href="register.php" class="font-medium text-cyan-500 hover:text-cyan-600">
                    đăng ký tài khoản mới
                </a>
            </p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="bg-green-50 border border-green-200 rounded-md p-4">
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
        
        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">
                            Lỗi đăng nhập
                        </h3>
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
        
        <form class="mt-8 space-y-6" method="POST">
            <div class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">
                        Tên đăng nhập hoặc Email
                    </label>
                    <div class="mt-1">
                        <input id="username" name="username" type="text" required
                               value="<?= htmlspecialchars($_POST['username'] ?? $_COOKIE['remember_user'] ?? '') ?>"
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 focus:z-10 sm:text-sm"
                               placeholder="Nhập tên đăng nhập hoặc email">
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Mật khẩu
                    </label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" required
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 focus:z-10 sm:text-sm"
                               placeholder="Nhập mật khẩu">
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox" 
                               class="h-4 w-4 text-cyan-500 focus:ring-cyan-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-900">
                            Ghi nhớ đăng nhập
                        </label>
                    </div>
                    
                    <div class="text-sm">
                        <a href="#" class="font-medium text-cyan-500 hover:text-cyan-600">
                            Quên mật khẩu?
                        </a>
                    </div>
                </div>
            </div>
            
            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 transition">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-lock text-cyan-200 group-hover:text-cyan-100"></i>
                    </span>
                    Đăng nhập
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'includes/header.php';
?>