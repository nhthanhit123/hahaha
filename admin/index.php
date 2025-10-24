<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// Kiểm tra đăng nhập và quyền admin
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['flash_message'] = 'Bạn không có quyền truy cập trang này';
    $_SESSION['flash_type'] = 'error';
    redirect('../login.php');
}

// Lấy thống kê dashboard
try {
    // Tổng số user
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users");
    $stmt->execute();
    $total_users = $stmt->fetch()['total'];
    
    // Tổng số dịch vụ
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM vps_services");
    $stmt->execute();
    $total_services = $stmt->fetch()['total'];
    
    // Dịch vụ đang hoạt động
    $stmt = $pdo->prepare("SELECT COUNT(*) as active FROM vps_services WHERE status = 'active'");
    $stmt->execute();
    $active_services = $stmt->fetch()['active'];
    
    // Doanh thu tháng này
    $stmt = $pdo->prepare("
        SELECT SUM(total_amount) as revenue 
        FROM orders 
        WHERE status = 'paid' 
        AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute();
    $monthly_revenue = $stmt->fetch()['revenue'] ?: 0;
    
    // Đơn hàng chờ xử lý
    $stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM orders WHERE status = 'pending'");
    $stmt->execute();
    $pending_orders = $stmt->fetch()['pending'];
    
    // Nạp tiền chờ duyệt
    $stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM deposits WHERE status = 'pending'");
    $stmt->execute();
    $pending_deposits = $stmt->fetch()['pending'];
    
    // 5 đơn hàng gần nhất
    $stmt = $pdo->prepare("
        SELECT o.*, u.full_name, u.email, vp.name as package_name
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN vps_packages vp ON o.package_id = vp.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recent_orders = $stmt->fetchAll();
    
    // 5 yêu cầu nạp tiền gần nhất
    $stmt = $pdo->prepare("
        SELECT d.*, u.full_name, u.email
        FROM deposits d
        LEFT JOIN users u ON d.user_id = u.id
        WHERE d.status = 'pending'
        ORDER BY d.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recent_deposits = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Lỗi khi lấy dữ liệu thống kê");
}

$page_title = "Admin Dashboard - " . SITE_NAME;
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <!-- Admin Header -->
    <div class="bg-gray-800 text-white">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <i class="fas fa-cog text-2xl mr-3"></i>
                    <h1 class="text-xl font-bold">Admin Panel</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm">Xin chào, <?php echo $_SESSION['user_name']; ?></span>
                    <a href="../logout.php" class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded text-sm transition">
                        Đăng xuất
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Navigation -->
    <div class="bg-gray-700 text-white">
        <div class="container mx-auto px-4">
            <nav class="flex space-x-6">
                <a href="index.php" class="py-3 px-4 hover:bg-gray-600 transition border-b-2 border-blue-500">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <a href="users.php" class="py-3 px-4 hover:bg-gray-600 transition">
                    <i class="fas fa-users mr-2"></i>Người dùng
                </a>
                <a href="services.php" class="py-3 px-4 hover:bg-gray-600 transition">
                    <i class="fas fa-server mr-2"></i>Dịch vụ
                </a>
                <a href="orders.php" class="py-3 px-4 hover:bg-gray-600 transition">
                    <i class="fas fa-shopping-cart mr-2"></i>Đơn hàng
                </a>
                <a href="deposits.php" class="py-3 px-4 hover:bg-gray-600 transition">
                    <i class="fas fa-credit-card mr-2"></i>Nạp tiền
                </a>
                <a href="packages.php" class="py-3 px-4 hover:bg-gray-600 transition">
                    <i class="fas fa-box mr-2"></i>Gói dịch vụ
                </a>
                <a href="settings.php" class="py-3 px-4 hover:bg-gray-600 transition">
                    <i class="fas fa-cog mr-2"></i>Cài đặt
                </a>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-full">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Tổng người dùng</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $total_users; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-full">
                        <i class="fas fa-server text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Dịch vụ hoạt động</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $active_services; ?>/<?php echo $total_services; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-full">
                        <i class="fas fa-dollar-sign text-yellow-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Doanh thu tháng</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo formatMoney($monthly_revenue); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-red-100 rounded-full">
                        <i class="fas fa-clock text-red-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Chờ xử lý</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $pending_orders + $pending_deposits; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Orders -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-900">Đơn hàng gần đây</h2>
                        <a href="orders.php" class="text-blue-600 hover:text-blue-800 text-sm">Xem tất cả</a>
                    </div>
                </div>
                <div class="p-6">
                    <?php if (empty($recent_orders)): ?>
                        <div class="text-center py-4 text-gray-500">
                            <i class="fas fa-shopping-cart text-3xl mb-2"></i>
                            <p>Chưa có đơn hàng nào</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <div class="font-medium"><?php echo $order['order_code']; ?></div>
                                        <div class="text-sm text-gray-500"><?php echo $order['full_name']; ?></div>
                                        <div class="text-sm text-gray-500"><?php echo $order['package_name']; ?></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold"><?php echo formatMoney($order['total_amount']); ?></div>
                                        <div class="text-xs">
                                            <?php
                                            $status_colors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'paid' => 'bg-green-100 text-green-800',
                                                'processing' => 'bg-blue-100 text-blue-800',
                                                'active' => 'bg-green-100 text-green-800'
                                            ];
                                            $status_text = [
                                                'pending' => 'Chờ xử lý',
                                                'paid' => 'Đã thanh toán',
                                                'processing' => 'Đang xử lý',
                                                'active' => 'Đã kích hoạt'
                                            ];
                                            ?>
                                            <span class="px-2 py-1 text-xs rounded-full <?php echo $status_colors[$order['status']]; ?>">
                                                <?php echo $status_text[$order['status']]; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Deposits -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-900">Nạp tiền chờ duyệt</h2>
                        <a href="deposits.php" class="text-blue-600 hover:text-blue-800 text-sm">Xem tất cả</a>
                    </div>
                </div>
                <div class="p-6">
                    <?php if (empty($recent_deposits)): ?>
                        <div class="text-center py-4 text-gray-500">
                            <i class="fas fa-credit-card text-3xl mb-2"></i>
                            <p>Không có yêu cầu nạp tiền nào</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recent_deposits as $deposit): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <div class="font-medium"><?php echo $deposit['deposit_code']; ?></div>
                                        <div class="text-sm text-gray-500"><?php echo $deposit['full_name']; ?></div>
                                        <div class="text-sm text-gray-500"><?php echo formatDate($deposit['created_at']); ?></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold"><?php echo formatMoney($deposit['amount']); ?></div>
                                        <a href="deposits.php?action=approve&id=<?php echo $deposit['id']; ?>" 
                                           class="text-xs bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700 transition">
                                            Duyệt
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>