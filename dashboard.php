<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = 'Vui lòng đăng nhập để tiếp tục';
    $_SESSION['flash_type'] = 'warning';
    redirect('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

// Lấy thông tin user
try {
    $user = getRow("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    
    if (!$user) {
        session_destroy();
        redirect('login.php');
    }
    
    // Cập nhật session balance
    $_SESSION['user_balance'] = $user['balance'];
    
} catch(Exception $e) {
    die("Lỗi hệ thống");
}

// Lấy thống kê
try {
    // Tổng số dịch vụ
    $total_services = getRow("SELECT COUNT(*) as total FROM services WHERE user_id = ?", [$_SESSION['user_id']])['total'];
    
    // Dịch vụ đang hoạt động
    $active_services = getRow("SELECT COUNT(*) as active FROM services WHERE user_id = ? AND status = 'active'", [$_SESSION['user_id']])['active'];
    
    // Đơn hàng chờ xử lý
    $pending_orders = getRow("SELECT COUNT(*) as pending FROM orders WHERE user_id = ? AND status = 'pending'", [$_SESSION['user_id']])['pending'];
    
    // Nạp tiền chờ duyệt
    $pending_deposits = getRow("SELECT COUNT(*) as pending FROM deposits WHERE user_id = ? AND status = 'pending'", [$_SESSION['user_id']])['pending'];
    
    // Lấy 5 dịch vụ gần nhất
    $recent_services = getRows("
        SELECT s.*, p.name as package_name 
        FROM services s 
        LEFT JOIN packages p ON s.package_id = p.id 
        WHERE s.user_id = ? 
        ORDER BY s.created_at DESC 
        LIMIT 5
    ", [$_SESSION['user_id']]);
    
} catch(Exception $e) {
    die("Lỗi khi lấy dữ liệu: " . $e->getMessage());
}

$page_title = "Dashboard - " . SITE_NAME;
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Welcome Section -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-lg shadow-lg p-6 mb-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2">Chào mừng, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
                    <p class="text-blue-100">Quản lý dịch vụ VPS của bạn một cách dễ dàng</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-blue-100 mb-1">Số dư tài khoản</p>
                    <p class="text-3xl font-bold"><?php echo formatMoney($user['balance']); ?></p>
                    <a href="deposit.php" class="inline-block mt-2 bg-white text-blue-600 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-50 transition">
                        Nạp tiền
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-full">
                        <i class="fas fa-server text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Tổng dịch vụ</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $total_services; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-full">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Đang hoạt động</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $active_services; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-full">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Đơn hàng chờ</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $pending_orders; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-full">
                        <i class="fas fa-wallet text-purple-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Nạp tiền chờ</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $pending_deposits; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Quick Actions -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Thao tác nhanh</h2>
                    <div class="space-y-3">
                        <a href="packages.php" class="block w-full bg-blue-600 text-white text-center py-3 px-4 rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-plus mr-2"></i>Mua VPS mới
                        </a>
                        <a href="services.php" class="block w-full bg-green-600 text-white text-center py-3 px-4 rounded-lg hover:bg-green-700 transition">
                            <i class="fas fa-list mr-2"></i>Quản lý dịch vụ
                        </a>
                        <a href="deposit.php" class="block w-full bg-purple-600 text-white text-center py-3 px-4 rounded-lg hover:bg-purple-700 transition">
                            <i class="fas fa-credit-card mr-2"></i>Nạp tiền
                        </a>
                        <a href="orders.php" class="block w-full bg-gray-600 text-white text-center py-3 px-4 rounded-lg hover:bg-gray-700 transition">
                            <i class="fas fa-shopping-cart mr-2"></i>Lịch sử đơn hàng
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Services -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-800">Dịch vụ gần đây</h2>
                        <a href="services.php" class="text-blue-600 hover:text-blue-700 text-sm">Xem tất cả</a>
                    </div>
                    
                    <?php if (empty($recent_services)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-server text-4xl mb-3"></i>
                            <p>Bạn chưa có dịch vụ nào</p>
                            <a href="packages.php" class="inline-block mt-3 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                                Mua VPS ngay
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left py-2 text-sm font-medium text-gray-700">Gói dịch vụ</th>
                                        <th class="text-left py-2 text-sm font-medium text-gray-700">Hệ điều hành</th>
                                        <th class="text-left py-2 text-sm font-medium text-gray-700">IP</th>
                                        <th class="text-left py-2 text-sm font-medium text-gray-700">Trạng thái</th>
                                        <th class="text-left py-2 text-sm font-medium text-gray-700">Hết hạn</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_services as $service): ?>
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="py-3">
                                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($service['package_name']); ?></div>
                                            </td>
                                            <td class="py-3 text-sm text-gray-600"><?php echo htmlspecialchars($service['os'] ?: 'N/A'); ?></td>
                                            <td class="py-3 text-sm text-gray-600">
                                                <?php echo $service['ip_address'] ?: 'Chưa cấp'; ?>
                                            </td>
                                            <td class="py-3">
                                                <?php
                                                $status_colors = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'active' => 'bg-green-100 text-green-800',
                                                    'suspended' => 'bg-red-100 text-red-800',
                                                    'terminated' => 'bg-gray-100 text-gray-800'
                                                ];
                                                $status_text = [
                                                    'pending' => 'Chờ xử lý',
                                                    'active' => 'Hoạt động',
                                                    'suspended' => 'Tạm dừng',
                                                    'terminated' => 'Đã终止'
                                                ];
                                                ?>
                                                <span class="px-2 py-1 text-xs rounded-full <?php echo $status_colors[$service['status']]; ?>">
                                                    <?php echo $status_text[$service['status']]; ?>
                                                </span>
                                            </td>
                                            <td class="py-3 text-sm text-gray-600">
                                                <?php echo $service['next_due_date'] ? formatDate($service['next_due_date']) : 'N/A'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>