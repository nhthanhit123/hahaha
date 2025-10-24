<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <a href="index.php" class="text-2xl font-bold text-blue-600">
                        <i class="fas fa-server mr-2"></i><?php echo SITE_NAME; ?>
                    </a>
                </div>
                
                <div class="hidden md:flex items-center space-x-6">
                    <a href="index.php" class="text-gray-700 hover:text-blue-600 transition">Trang chủ</a>
                    <a href="packages.php" class="text-gray-700 hover:text-blue-600 transition">Gói dịch vụ</a>
                    <?php if (isLoggedIn()): ?>
                        <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 transition">Dashboard</a>
                        <a href="services.php" class="text-gray-700 hover:text-blue-600 transition">Dịch vụ của tôi</a>
                        <a href="deposit.php" class="text-gray-700 hover:text-blue-600 transition">
                            <i class="fas fa-wallet mr-1"></i>
                            Nạp tiền
                        </a>
                        <div class="relative group">
                            <button class="text-gray-700 hover:text-blue-600 transition flex items-center">
                                <i class="fas fa-user-circle mr-1"></i>
                                <?php echo $_SESSION['user_name']; ?>
                                <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
                                <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-t-lg">Hồ sơ</a>
                                <a href="orders.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Đơn hàng</a>
                                <?php if (isAdmin()): ?>
                                    <a href="admin/" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Admin Panel</a>
                                <?php endif; ?>
                                <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-b-lg">Đăng xuất</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-700 hover:text-blue-600 transition">Đăng nhập</a>
                        <a href="register.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Đăng ký</a>
                    <?php endif; ?>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button id="mobile-menu-button" class="text-gray-700 hover:text-blue-600 focus:outline-none">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t">
            <div class="px-4 py-2 space-y-2">
                <a href="index.php" class="block py-2 text-gray-700 hover:text-blue-600">Trang chủ</a>
                <a href="packages.php" class="block py-2 text-gray-700 hover:text-blue-600">Gói dịch vụ</a>
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php" class="block py-2 text-gray-700 hover:text-blue-600">Dashboard</a>
                    <a href="services.php" class="block py-2 text-gray-700 hover:text-blue-600">Dịch vụ của tôi</a>
                    <a href="deposit.php" class="block py-2 text-gray-700 hover:text-blue-600">Nạp tiền</a>
                    <a href="profile.php" class="block py-2 text-gray-700 hover:text-blue-600">Hồ sơ</a>
                    <a href="orders.php" class="block py-2 text-gray-700 hover:text-blue-600">Đơn hàng</a>
                    <?php if (isAdmin()): ?>
                        <a href="admin/" class="block py-2 text-gray-700 hover:text-blue-600">Admin Panel</a>
                    <?php endif; ?>
                    <a href="logout.php" class="block py-2 text-gray-700 hover:text-blue-600">Đăng xuất</a>
                <?php else: ?>
                    <a href="login.php" class="block py-2 text-gray-700 hover:text-blue-600">Đăng nhập</a>
                    <a href="register.php" class="block py-2 text-gray-700 hover:text-blue-600">Đăng ký</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="container mx-auto px-4 mt-4">
            <?php echo showAlert($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
    </script>
</body>
</html>