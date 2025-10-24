<?php
require_once 'config.php';
require_once 'database.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = cleanInput($_POST['username']);
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = cleanInput($_POST['full_name']);
    $phone = cleanInput($_POST['phone']);
    
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Vui lòng nhập tên đăng nhập';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Tên đăng nhập phải có ít nhất 3 ký tự';
    } elseif (getUserByUsername($username)) {
        $errors[] = 'Tên đăng nhập đã tồn tại';
    }
    
    if (empty($email)) {
        $errors[] = 'Vui lòng nhập email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ';
    } elseif (getUserByEmail($email)) {
        $errors[] = 'Email đã tồn tại';
    }
    
    if (empty($password)) {
        $errors[] = 'Vui lòng nhập mật khẩu';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Mật khẩu xác nhận không khớp';
    }
    
    if (empty($full_name)) {
        $errors[] = 'Vui lòng nhập họ tên';
    }
    
    if (empty($errors)) {
        $userData = [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'full_name' => $full_name,
            'phone' => $phone
        ];
        
        if (createUser($userData)) {
            $_SESSION['success_message'] = 'Đăng ký thành công! Vui lòng đăng nhập.';
            redirect('login.php');
        } else {
            $errors[] = 'Đăng ký thất bại. Vui lòng thử lại.';
        }
    }
}

$page_title = 'Đăng ký - ' . SITE_NAME;

ob_start();
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-gradient-to-r from-cyan-500 to-blue-500">
                <i class="fas fa-user-plus text-white text-xl"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Đăng ký tài khoản
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Hoặc 
                <a href="login.php" class="font-medium text-cyan-500 hover:text-cyan-600">
                    đăng nhập nếu đã có tài khoản
                </a>
            </p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">
                            Lỗi đăng ký
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
                        Tên đăng nhập *
                    </label>
                    <div class="mt-1">
                        <input id="username" name="username" type="text" required
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 focus:z-10 sm:text-sm"
                               placeholder="Nhập tên đăng nhập">
                    </div>
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email *
                    </label>
                    <div class="mt-1">
                        <input id="email" name="email" type="email" required
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 focus:z-10 sm:text-sm"
                               placeholder="nhapemail@example.com">
                    </div>
                </div>
                
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700">
                        Họ tên *
                    </label>
                    <div class="mt-1">
                        <input id="full_name" name="full_name" type="text" required
                               value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 focus:z-10 sm:text-sm"
                               placeholder="Nhập họ tên đầy đủ">
                    </div>
                </div>
                
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">
                        Số điện thoại
                    </label>
                    <div class="mt-1">
                        <input id="phone" name="phone" type="tel"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 focus:z-10 sm:text-sm"
                               placeholder="Nhập số điện thoại">
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Mật khẩu *
                    </label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" required
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 focus:z-10 sm:text-sm"
                               placeholder="Nhập mật khẩu">
                    </div>
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                        Xác nhận mật khẩu *
                    </label>
                    <div class="mt-1">
                        <input id="confirm_password" name="confirm_password" type="password" required
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 focus:z-10 sm:text-sm"
                               placeholder="Nhập lại mật khẩu">
                    </div>
                </div>
            </div>
            
            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 transition">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-user-plus text-cyan-200 group-hover:text-cyan-100"></i>
                    </span>
                    Đăng ký
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Mật khẩu xác nhận không khớp');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php
$content = ob_get_clean();
include 'includes/header.php';
?>