<?php
require_once '../config.php';
require_once '../database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

if (isset($_GET['action']) && $_GET['action'] == 'update') {
    // This will trigger the VPS package update from index.php
    header('Location: ../index.php?update=1');
    exit();
}

$packages = fetchVpsPackages();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['sync_packages'])) {
    // Sync packages from source
    header('Location: ../index.php?update=1');
    exit();
}

ob_start();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý gói VPS - Admin Panel</title>
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
                        <a href="packages.php" class="flex items-center space-x-3 text-cyan-600 bg-cyan-50 p-3 rounded-lg">
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
                        <a href="settings.php" class="flex items-center space-x-3 text-gray-700 hover:bg-gray-100 p-3 rounded-lg transition">
                            <i class="fas fa-cog"></i>
                            <span>Cài đặt</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Quản lý gói VPS</h1>
                    <p class="text-gray-600">Quản lý các gói VPS được lấy từ thuevpsgiare.com.vn</p>
                </div>
                <form method="POST" class="inline">
                    <button type="submit" name="sync_packages" 
                            class="bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 text-white py-2 px-6 rounded-lg font-semibold transition">
                        <i class="fas fa-sync mr-2"></i>Đồng bộ gói VPS
                    </button>
                </form>
            </div>

            <!-- Sync Info -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-blue-800 mb-2">Thông tin đồng bộ</h3>
                        <div class="text-blue-700">
                            <p class="mb-2"><strong>Nguồn:</strong> <a href="https://thuevpsgiare.com.vn/vps/packages?category=vps-cheap-ip-nat" target="_blank" class="underline">thuevpsgiare.com.vn</a></p>
                            <p class="mb-2"><strong>Tự động tăng giá:</strong> +5% trên giá gốc</p>
                            <p class="mb-2"><strong>Số gói hiện tại:</strong> <?= count($packages) ?> gói</p>
                            <p><strong>Lần cập nhật cuối:</strong> <?= date('d/m/Y H:i:s') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Packages Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (empty($packages)): ?>
                    <div class="col-span-full text-center py-12">
                        <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">Chưa có gói VPS nào</h3>
                        <p class="text-gray-500 mb-6">Vui lòng nhấn nút "Đồng bộ gói VPS" để lấy dữ liệu</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($packages as $package): ?>
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <h3 class="text-lg font-bold text-gray-900">
                                        <?= htmlspecialchars($package['name']) ?>
                                    </h3>
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                                        <?= $package['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $package['status'] == 'active' ? 'Hoạt động' : 'Đã ẩn' ?>
                                    </span>
                                </div>
                                
                                <div class="space-y-2 mb-4">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">CPU:</span>
                                        <span class="font-medium"><?= htmlspecialchars($package['cpu']) ?></span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">RAM:</span>
                                        <span class="font-medium"><?= htmlspecialchars($package['ram']) ?></span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Ổ cứng:</span>
                                        <span class="font-medium"><?= htmlspecialchars($package['storage']) ?></span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Vị trí:</span>
                                        <span class="font-medium"><?= htmlspecialchars($package['location']) ?></span>
                                    </div>
                                </div>
                                
                                <div class="border-t pt-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-gray-500 text-sm line-through">
                                            <?= formatPrice($package['original_price']) ?>
                                        </span>
                                        <span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">
                                            +5%
                                        </span>
                                    </div>
                                    <div class="text-xl font-bold text-cyan-600">
                                        <?= formatPrice($package['selling_price']) ?>
                                        <span class="text-sm text-gray-500">/tháng</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Statistics -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Tổng gói VPS</p>
                            <p class="text-3xl font-bold text-gray-900"><?= count($packages) ?></p>
                        </div>
                        <div class="text-3xl text-blue-500">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Giá trung bình</p>
                            <p class="text-3xl font-bold text-gray-900">
                                <?php
                                $avg_price = count($packages) > 0 ? array_sum(array_column($packages, 'selling_price')) / count($packages) : 0;
                                echo formatPrice($avg_price);
                                ?>
                            </p>
                        </div>
                        <div class="text-3xl text-green-500">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Gói đang hoạt động</p>
                            <p class="text-3xl font-bold text-gray-900">
                                <?php
                                $active_count = count(array_filter($packages, function($p) { return $p['status'] == 'active'; }));
                                echo $active_count;
                                ?>
                            </p>
                        </div>
                        <div class="text-3xl text-green-500">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function formatPrice(price) {
            return new Intl.NumberFormat('vi-VN').format(price) + ' VNĐ';
        }

        // Auto-sync every 30 minutes
        setInterval(function() {
            console.log('Auto-sync would run here');
        }, 30 * 60 * 1000);
    </script>
</body>
</html>

<?php
$content = ob_get_clean();
echo $content;
?>