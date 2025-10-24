<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? SITE_NAME ?></title>
    <meta name="description" content="<?= $page_description ?? SITE_DESCRIPTION ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .cyber-border {
            border: 2px solid #00ff88;
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.3);
        }
        
        .cyber-text {
            color: #00ff88;
            text-shadow: 0 0 10px rgba(0, 255, 136, 0.5);
        }
        
        .hover-glow:hover {
            box-shadow: 0 0 30px rgba(0, 255, 136, 0.5);
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }
        
        .dark-mode {
            background: #0a0a0a;
            color: #ffffff;
        }
        
        .dark-mode .bg-white {
            background: #1a1a1a !important;
        }
        
        .dark-mode .text-gray-600 {
            color: #cccccc !important;
        }
        
        .dark-mode .text-gray-800 {
            color: #ffffff !important;
        }
        
        .dark-mode .border-gray-200 {
            border-color: #333333 !important;
        }
        
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #00ff88;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <nav class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-2xl font-bold cyber-text">
                        <i class="fas fa-server mr-2"></i><?= SITE_NAME ?>
                    </a>
                </div>
                
                <div class="hidden md:flex items-center space-x-6">
                    <a href="index.php" class="hover:text-cyan-300 transition">Trang chủ</a>
                    <a href="packages.php" class="hover:text-cyan-300 transition">Gói VPS</a>
                    <a href="services.php" class="hover:text-cyan-300 transition">Dịch vụ của tôi</a>
                    <a href="deposit.php" class="hover:text-cyan-300 transition">Nạp tiền</a>
                    <?php if (isLoggedIn()): ?>
                        <a href="profile.php" class="hover:text-cyan-300 transition">
                            <i class="fas fa-user mr-1"></i><?= $_SESSION['username'] ?>
                        </a>
                        <?php if (isAdmin()): ?>
                            <a href="admin/" class="hover:text-cyan-300 transition">
                                <i class="fas fa-cog mr-1"></i>Admin
                            </a>
                        <?php endif; ?>
                        <a href="logout.php" class="hover:text-cyan-300 transition">
                            <i class="fas fa-sign-out-alt mr-1"></i>Đăng xuất
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="hover:text-cyan-300 transition">Đăng nhập</a>
                        <a href="register.php" class="hover:text-cyan-300 transition">Đăng ký</a>
                    <?php endif; ?>
                </div>
                
                <button class="md:hidden" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
            
            <!-- Mobile Menu -->
            <div id="mobileMenu" class="hidden md:hidden mt-4">
                <div class="flex flex-col space-y-2">
                    <a href="index.php" class="hover:text-cyan-300 transition py-2">Trang chủ</a>
                    <a href="packages.php" class="hover:text-cyan-300 transition py-2">Gói VPS</a>
                    <a href="services.php" class="hover:text-cyan-300 transition py-2">Dịch vụ của tôi</a>
                    <a href="deposit.php" class="hover:text-cyan-300 transition py-2">Nạp tiền</a>
                    <?php if (isLoggedIn()): ?>
                        <a href="profile.php" class="hover:text-cyan-300 transition py-2">
                            <i class="fas fa-user mr-1"></i><?= $_SESSION['username'] ?>
                        </a>
                        <?php if (isAdmin()): ?>
                            <a href="admin/" class="hover:text-cyan-300 transition py-2">
                                <i class="fas fa-cog mr-1"></i>Admin
                            </a>
                        <?php endif; ?>
                        <a href="logout.php" class="hover:text-cyan-300 transition py-2">
                            <i class="fas fa-sign-out-alt mr-1"></i>Đăng xuất
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="hover:text-cyan-300 transition py-2">Đăng nhập</a>
                        <a href="register.php" class="hover:text-cyan-300 transition py-2">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <?= $content ?? '' ?>
    </main>

    <!-- Footer -->
    <footer class="gradient-bg text-white mt-12">
        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4 cyber-text"><?= SITE_NAME ?></h3>
                    <p class="text-gray-300">Dịch vụ VPS chất lượng cao với giá cả phải chăng</p>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-3">Dịch vụ</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="packages.php" class="hover:text-cyan-300 transition">VPS Giá Rẻ</a></li>
                        <li><a href="packages.php" class="hover:text-cyan-300 transition">VPS Cao Cấp</a></li>
                        <li><a href="deposit.php" class="hover:text-cyan-300 transition">Nạp Tiền</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-3">Hỗ trợ</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="#" class="hover:text-cyan-300 transition">Hướng dẫn</a></li>
                        <li><a href="#" class="hover:text-cyan-300 transition">FAQ</a></li>
                        <li><a href="#" class="hover:text-cyan-300 transition">Liên hệ</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-3">Liên hệ</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li><i class="fas fa-envelope mr-2"></i>support@vpsstore.com</li>
                        <li><i class="fas fa-phone mr-2"></i>1900 1234</li>
                        <li><i class="fas fa-map-marker-alt mr-2"></i>Hà Nội, Việt Nam</li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-600 mt-8 pt-6 text-center text-gray-300">
                <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }
        
        function showLoading() {
            const loader = document.createElement('div');
            loader.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            loader.innerHTML = '<div class="loading-spinner"></div>';
            loader.id = 'loadingOverlay';
            document.body.appendChild(loader);
        }
        
        function hideLoading() {
            const loader = document.getElementById('loadingOverlay');
            if (loader) {
                loader.remove();
            }
        }
        
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
            toast.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>