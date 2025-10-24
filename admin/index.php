<?php
require_once '../config.php';
require_once '../database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$page_title = 'Dashboard - Admin Panel';

$totalUsers = count(fetchAll("SELECT id FROM users"));
totalOrders = count(fetchAll("SELECT id FROM vps_orders"));
$totalDeposits = count(fetchAll("SELECT id FROM deposits"));
$pendingOrders = count(fetchAll("SELECT id FROM vps_orders WHERE status = 'pending'"));
$pendingDeposits = count(fetchAll("SELECT id FROM deposits WHERE status = 'pending'"));

$recentOrders = fetchAll("SELECT vo.*, u.username, u.full_name, vp.name as package_name 
                          FROM vps_orders vo 
                          LEFT JOIN users u ON vo.user_id = u.id 
                          LEFT JOIN vps_packages vp ON vo.package_id = vp.id 
                          ORDER BY vo.created_at DESC LIMIT 10");

$recentDeposits = fetchAll("SELECT d.*, u.username, u.full_name 
                            FROM deposits d 
                            LEFT JOIN users u ON d.user_id = u.id 
                            ORDER BY d.created_at DESC LIMIT 10");

ob_start();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .hover-glow:hover {
            box-shadow: 0 0 30px rgba(0, 255, 136, 0.5);
            transform: translateY(-2px);
            transition: all 0.3s ease;
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
                        <a href="index.php" class="flex items-center space-x-3 text-cyan-600 bg-cyan-50 p-3 rounded-lg">
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
                        <a href="packages.php" class="flex items-center space-x-3 text-gray-700 hover:bg-gray-100 p-3 rounded-lg transition">
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
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-lg p-6 hover-glow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Tổng người dùng</p>
                            <p class="text-3xl font-bold text-gray-900"><?= $totalUsers ?></p>
                        </div>
                        <div class="text-3xl text-blue-500">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-6 hover-glow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Tổng đơn hàng</p>
                            <p class="text-3xl font-bold text-gray-900"><?= $totalOrders ?></p>
                        </div>
                        <div class="text-3xl text-green-500">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-6 hover-glow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Tổng nạp tiền</p>
                            <p class="text-3xl font-bold text-gray-900"><?= $totalDeposits ?></p>
                        </div>
                        <div class="text-3xl text-yellow-500">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-6 hover-glow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Chờ xử lý</p>
                            <p class="text-3xl font-bold text-red-600"><?= $pendingOrders + $pendingDeposits ?></p>
                        </div>
                        <div class="text-3xl text-red-500">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders & Deposits -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Orders -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Đơn hàng gần đây</h2>
                        <a href="orders.php" class="text-cyan-500 hover:text-cyan-600 text-sm font-medium">
                            Xem tất cả <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        <?php if (empty($recentOrders)): ?>
                            <p class="text-gray-500 text-center py-4">Chưa có đơn hàng nào</p>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $order): ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <p class="font-medium text-gray-900">#<?= $order['id'] ?> - <?= htmlspecialchars($order['package_name']) ?></p>
                                            <p class="text-sm text-gray-500"><?= htmlspecialchars($order['full_name']) ?> (<?= htmlspecialchars($order['username']) ?>)</p>
                                        </div>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                                            <?php
                                            switch($order['status']) {
                                                case 'active':
                                                    echo 'bg-green-100 text-green-800';
                                                    break;
                                                case 'pending':
                                                    echo 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'expired':
                                                    echo 'bg-red-100 text-red-800';
                                                    break;
                                                default:
                                                    echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>">
                                            <?= $order['status'] ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-gray-600"><?= formatPrice($order['total_price']) ?></span>
                                        <span class="text-gray-400"><?= formatDate($order['created_at']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Deposits -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Nạp tiền gần đây</h2>
                        <a href="deposits.php" class="text-cyan-500 hover:text-cyan-600 text-sm font-medium">
                            Xem tất cả <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        <?php if (empty($recentDeposits)): ?>
                            <p class="text-gray-500 text-center py-4">Chưa có giao dịch nào</p>
                        <?php else: ?>
                            <?php foreach ($recentDeposits as $deposit): ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <p class="font-medium text-gray-900"><?= htmlspecialchars($deposit['full_name']) ?> (<?= htmlspecialchars($deposit['username']) ?>)</p>
                                            <p class="text-sm text-gray-500"><?= htmlspecialchars($deposit['bank_name']) ?></p>
                                        </div>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                                            <?php
                                            switch($deposit['status']) {
                                                case 'completed':
                                                    echo 'bg-green-100 text-green-800';
                                                    break;
                                                case 'pending':
                                                    echo 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'failed':
                                                    echo 'bg-red-100 text-red-800';
                                                    break;
                                                default:
                                                    echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>">
                                            <?= $deposit['status'] ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-gray-600"><?= formatPrice($deposit['amount']) ?></span>
                                        <span class="text-gray-400"><?= formatDate($deposit['created_at']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Thao tác nhanh</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="packages.php?action=update" class="bg-blue-500 hover:bg-blue-600 text-white p-4 rounded-lg text-center transition">
                        <i class="fas fa-sync text-2xl mb-2"></i>
                        <p class="font-medium">Cập nhật gói VPS</p>
                    </a>
                    <a href="orders.php?filter=pending" class="bg-yellow-500 hover:bg-yellow-600 text-white p-4 rounded-lg text-center transition">
                        <i class="fas fa-clock text-2xl mb-2"></i>
                        <p class="font-medium">Đơn hàng chờ</p>
                    </a>
                    <a href="deposits.php?filter=pending" class="bg-green-500 hover:bg-green-600 text-white p-4 rounded-lg text-center transition">
                        <i class="fas fa-money-check text-2xl mb-2"></i>
                        <p class="font-medium">Nạp tiền chờ</p>
                    </a>
                    <a href="settings.php" class="bg-purple-500 hover:bg-purple-600 text-white p-4 rounded-lg text-center transition">
                        <i class="fas fa-cog text-2xl mb-2"></i>
                        <p class="font-medium">Cài đặt</p>
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
        function formatPrice(price) {
            return new Intl.NumberFormat('vi-VN').format(price) + ' VNĐ';
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'});
        }
    </script>
</body>
</html>

<?php
$content = ob_get_clean();
echo $content;
?>