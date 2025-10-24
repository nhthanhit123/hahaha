<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    redirect('login.php');
}

// Lấy thông tin đơn hàng
$order = null;
if (isset($_GET['order_id'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, vp.name as package_name, os.name as os_name, os.version as os_version
            FROM orders o
            LEFT JOIN vps_packages vp ON o.package_id = vp.id
            LEFT JOIN operating_systems os ON o.os_id = os.id
            WHERE o.id = ? AND o.user_id = ?
        ");
        $stmt->execute([$_GET['order_id'], $_SESSION['user_id']]);
        $order = $stmt->fetch();
    } catch(PDOException $e) {
        // Bỏ qua
    }
}

if (!$order) {
    $_SESSION['flash_message'] = 'Không tìm thấy đơn hàng';
    $_SESSION['flash_type'] = 'error';
    redirect('orders.php');
}

$page_title = "Đặt hàng thành công - " . SITE_NAME;
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto">
            <!-- Success Header -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-8 text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check text-green-600 text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-green-800 mb-2">Đặt hàng thành công!</h1>
                <p class="text-green-700">Đơn hàng của bạn đã được tạo và đang được xử lý</p>
            </div>

            <!-- Order Details -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Thông tin đơn hàng</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="mb-4">
                            <span class="text-sm text-gray-500">Mã đơn hàng</span>
                            <div class="font-semibold text-lg"><?php echo $order['order_code']; ?></div>
                        </div>
                        
                        <div class="mb-4">
                            <span class="text-sm text-gray-500">Trạng thái</span>
                            <div>
                                <span class="px-3 py-1 text-sm rounded-full bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-clock mr-1"></i>Đang xử lý
                                </span>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <span class="text-sm text-gray-500">Ngày đặt hàng</span>
                            <div class="font-medium"><?php echo formatDate($order['created_at']); ?></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="mb-4">
                            <span class="text-sm text-gray-500">Gói dịch vụ</span>
                            <div class="font-medium"><?php echo htmlspecialchars($order['package_name']); ?></div>
                        </div>
                        
                        <div class="mb-4">
                            <span class="text-sm text-gray-500">Hệ điều hành</span>
                            <div class="font-medium">
                                <?php echo htmlspecialchars($order['os_name']); ?>
                                <?php if ($order['os_version']): ?>
                                    <?php echo htmlspecialchars($order['os_version']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <span class="text-sm text-gray-500">Chu kỳ thanh toán</span>
                            <div class="font-medium"><?php echo $order['billing_cycle']; ?> tháng</div>
                        </div>
                    </div>
                </div>
                
                <?php if ($order['notes']): ?>
                    <div class="mt-6 pt-6 border-t">
                        <span class="text-sm text-gray-500">Ghi chú</span>
                        <div class="mt-1 p-3 bg-gray-50 rounded text-gray-700">
                            <?php echo htmlspecialchars($order['notes']); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="mt-6 pt-6 border-t">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold">Tổng thanh toán</span>
                        <span class="text-2xl font-bold text-blue-600"><?php echo formatMoney($order['total_amount']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-blue-900 mb-4">
                    <i class="fas fa-info-circle mr-2"></i>Các bước tiếp theo
                </h3>
                <div class="space-y-3 text-blue-800">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center text-sm font-semibold mr-3">1</div>
                        <div>Admin sẽ xác nhận và xử lý đơn hàng của bạn trong vòng 5-30 phút</div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center text-sm font-semibold mr-3">2</div>
                        <div>VPS sẽ được thiết lập và cấp IP, thông tin đăng nhập</div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center text-sm font-semibold mr-3">3</div>
                        <div>Bạn sẽ nhận được thông báo khi VPS sẵn sàng sử dụng</div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="services.php" class="bg-blue-600 text-white text-center py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 transition">
                    <i class="fas fa-list mr-2"></i>Xem dịch vụ của tôi
                </a>
                <a href="packages.php" class="bg-gray-200 text-gray-700 text-center py-3 px-4 rounded-lg font-semibold hover:bg-gray-300 transition">
                    <i class="fas fa-plus mr-2"></i>Mua thêm VPS
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>