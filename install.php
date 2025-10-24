<?php
// 安装向导
session_start();

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$step = max(1, min(4, $step));

$errors = [];
$success = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($step == 2) {
        // 检查数据库连接
        $db_host = $_POST['db_host'] ?? 'localhost';
        $db_name = $_POST['db_name'] ?? '';
        $db_user = $_POST['db_user'] ?? '';
        $db_pass = $_POST['db_pass'] ?? '';
        
        if (empty($db_name) || empty($db_user)) {
            $errors[] = '数据库名称和用户名不能为空';
        } else {
            try {
                $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // 创建数据库
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                // 连接到数据库
                $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // 读取并执行SQL文件
                $sql = file_get_contents('database.sql');
                $pdo->exec($sql);
                
                // 创建配置文件
                $config_content = "<?php\n";
                $config_content .= "// Cấu hình database\n";
                $config_content .= "define('DB_HOST', '$db_host');\n";
                $config_content .= "define('DB_NAME', '$db_name');\n";
                $config_content .= "define('DB_USER', '$db_user');\n";
                $config_content .= "define('DB_PASS', '$db_pass');\n\n";
                $config_content .= file_get_contents('config/config.example.php');
                $config_content = str_replace("<?php\n// Cấu hình database\ndefine('DB_HOST', 'localhost');\ndefine('DB_NAME', 'vps_hosting');\ndefine('DB_USER', 'root');\ndefine('DB_PASS', '');\n\n", "", $config_content);
                
                file_put_contents('config/config.php', $config_content);
                
                $success = '数据库安装成功！';
                header("Location: install.php?step=3&success=1");
                exit();
                
            } catch(PDOException $e) {
                $errors[] = '数据库连接失败: ' . $e->getMessage();
            }
        }
    } elseif ($step == 3) {
        // 创建管理员账户
        $admin_username = $_POST['admin_username'] ?? 'admin';
        $admin_email = $_POST['admin_email'] ?? '';
        $admin_password = $_POST['admin_password'] ?? '';
        
        if (empty($admin_email) || empty($admin_password)) {
            $errors[] = '邮箱和密码不能为空';
        } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = '邮箱格式不正确';
        } elseif (strlen($admin_password) < 6) {
            $errors[] = '密码长度至少6位';
        } else {
            try {
                require_once 'config/config.php';
                
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE role = 'admin'");
                $stmt->execute([
                    $admin_username,
                    $admin_email,
                    password_hash($admin_password, PASSWORD_DEFAULT)
                ]);
                
                $success = '管理员账户创建成功！';
                header("Location: install.php?step=4&success=1");
                exit();
                
            } catch(Exception $e) {
                $errors[] = '创建管理员账户失败: ' . $e->getMessage();
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HostGreen VPS - 安装向导</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100">
                    <i class="fas fa-server text-blue-600 text-xl"></i>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    HostGreen VPS 安装向导
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    步骤 <?php echo $step; ?> / 4
                </p>
            </div>

            <!-- 进度条 -->
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: <?php echo ($step / 4) * 100; ?>%"></div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-8">
                <?php if (!empty($errors)): ?>
                    <div class="mb-4">
                        <?php foreach ($errors as $error): ?>
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-2">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        操作成功完成！
                    </div>
                <?php endif; ?>

                <?php if ($step == 1): ?>
                    <h3 class="text-lg font-semibold mb-4">欢迎使用 HostGreen VPS</h3>
                    <p class="text-gray-600 mb-6">这个向导将帮助您快速安装和配置 VPS 销售管理系统。</p>
                    
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span>PHP 7.4+ 环境</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span>MySQL 数据库</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span>Web 服务器</span>
                        </div>
                    </div>
                    
                    <div class="mt-8">
                        <a href="install.php?step=2" class="w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            开始安装
                        </a>
                    </div>

                <?php elseif ($step == 2): ?>
                    <h3 class="text-lg font-semibold mb-4">数据库配置</h3>
                    <form method="POST">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">数据库主机</label>
                                <input type="text" name="db_host" value="localhost" required
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">数据库名称</label>
                                <input type="text" name="db_name" value="hostgreen" required
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">数据库用户名</label>
                                <input type="text" name="db_user" required
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">数据库密码</label>
                                <input type="password" name="db_pass"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                安装数据库
                            </button>
                        </div>
                    </form>

                <?php elseif ($step == 3): ?>
                    <h3 class="text-lg font-semibold mb-4">创建管理员账户</h3>
                    <form method="POST">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">用户名</label>
                                <input type="text" name="admin_username" value="admin" required
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">邮箱</label>
                                <input type="email" name="admin_email" required
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">密码</label>
                                <input type="password" name="admin_password" required minlength="6"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                创建管理员
                            </button>
                        </div>
                    </form>

                <?php elseif ($step == 4): ?>
                    <h3 class="text-lg font-semibold mb-4">安装完成！</h3>
                    <div class="text-center">
                        <i class="fas fa-check-circle text-green-500 text-6xl mb-4"></i>
                        <p class="text-gray-600 mb-6">恭喜！HostGreen VPS 系统已经成功安装。</p>
                        
                        <div class="space-y-2 text-left bg-gray-50 p-4 rounded">
                            <p><strong>管理员账户:</strong> admin</p>
                            <p><strong>登录地址:</strong> <a href="login.php" class="text-blue-600">login.php</a></p>
                            <p><strong>管理后台:</strong> <a href="admin/" class="text-blue-600">admin/</a></p>
                        </div>
                        
                        <div class="mt-6 space-y-2">
                            <a href="login.php" class="w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                前往登录
                            </a>
                            <a href="index.php" class="w-full flex justify-center py-2 px-4 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                返回首页
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>