<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    redirect('login.php');
}

// Lấy thông tin dịch vụ
$service = null;
if (isset($_GET['service_id'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT vs.*, vp.name as package_name, vp.price_monthly,
                   os.name as os_name, os.version as os_version
            FROM vps_services vs
            LEFT JOIN vps_packages vp ON vs.package_id = vp.id
            LEFT JOIN operating_systems os ON vs.os_id = os.id
            WHERE vs.id = ? AND vs.user_id = ?
        ");
        $stmt->execute([$_GET['service_id'], $_SESSION['user_id']]);
        $service = $stmt->fetch();
    } catch(PDOException $e) {
        // Bỏ qua
    }
}

if (!$service) {
    $_SESSION['flash_message'] = 'Không tìm thấy dịch vụ';
    $_SESSION['flash_type'] = 'error';
    redirect('services.php');
}

// Lấy thông tin gia hạn gần nhất
$renewal = null;
try {
    $stmt = $pdo->prepare("
        SELECT * FROM renewals 
        WHERE service_id = ? AND user_id = ? AND status = 'paid'
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$_GET['service_id'], $_SESSION['user_id']]);
    $renewal = $stmt->fetch();
} catch(PDOException $e) {
    // Bỏ qua
}

$page_title = "Gia hạn thành công - " . SITE_NAME;
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
                <h1 class="text-3xl font-bold text-green-800 mb-2">Gia hạn thành công!</h1>
                <p class="text-green-700">Dịch vụ VPS của bạn đã được gia hạn thành công</p>
            </div>

            <!-- Renewal Details -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Chi tiết gia hạn</h2>
                
                <?php if ($renewal): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="mb-4">
                                <span class="text-sm text-gray-500">Thời gian gia hạn</span>
                                <div class="font-semibold text-lg"><?php echo $renewal['months']; ?> tháng</div>
                            </div>
                            
                            <div class="mb-4">
                                <span class="text-sm text-gray-500">Số tiền thanh toán</span>
                                <div class="font-semibold text-lg text-blue-600"><?php echo formatMoney($renewal['amount']); ?></div>
                            </div>
                            
                            <div class="mb-4">
                                <span class="text-sm text-gray-500">Phương thức thanh toán</span>
                                <div class="font-medium">Số dư tài khoản</div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="mb-4">
                                <span class="text-sm text-gray-500">Hết hạn cũ</span>
                                <div class="font-medium"><?php echo formatDate($renewal['old_expiry_date']); ?></div>
                            </div>
                            
                            <div class="mb-4">
                                <span class="text-sm text-gray-500">Hết hạn mới</span>
                                <div class="font-semibold text-lg text-green-600"><?php echo formatDate($renewal['new_expiry_date']); ?></div>
                            </div>
                            
                            <div class="mb-4">
                                <span class="text-sm text-gray-500">Thời gian gia hạn</span>
                                <div class="font-medium"><?php echo formatDate($renewal['created_at']); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Service Info -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Thông tin dịch vụ</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="mb-4">
                            <span class="text-sm text-gray-500">Gói dịch vụ</span>
                            <div class="font-semibold"><?php echo htmlspecialchars($service['package_name']); ?></div>
                        </div>
                        
                        <div class="mb-4">
                            <span class="text-sm text-gray-500">Hệ điều hành</span>
                            <div class="font-medium">
                                <?php 
                                $os_icon = $service['os_type'] == 'windows' ? 'fab fa-windows text-blue-600' : 'fab fa-linux text-orange-600';
                                ?>
                                <i class="<?php echo $os_icon; ?> mr-1"></i>
                                <?php echo htmlspecialchars($service['os_name']); ?>
                                <?php if ($service['os_version']): ?>
                                    <?php echo htmlspecialchars($service['os_version']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <span class="text-sm text-gray-500">Địa chỉ IP</span>
                            <div class="font-medium font-mono"><?php echo $service['ip_address'] ?: 'Chưa cấp phát'; ?></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="mb-4">
                            <span class="text-sm text-gray-500">Ngày hết hạn</span>
                            <div class="font-semibold text-green-600"><?php echo formatDate($service['expiry_date']); ?></div>
                            <?php 
                            $days_left = floor((strtotime($service['expiry_date']) - time()) / (60 * 60 * 24));
                            if ($days_left > 0) {
                                echo '<div class="text-green-600 text-sm">Còn ' . $days_left . ' ngày</div>';
                            }
                            ?>
                        </div>
                        
                        <div class="mb-4">
                            <span class="text-sm text-gray-500">Trạng thái</span>
                            <div>
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                    Hoạt động
                                </span>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <span class="text-sm text-gray-500">Giá tháng</span>
                            <div class="font-medium"><?php echo formatMoney($service['price_monthly']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="services.php" class="bg-blue-600 text-white text-center py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 transition">
                    <i class="fas fa-list mr-2"></i>Xem tất cả dịch vụ
                </a>
                <a href="dashboard.php" class="bg-gray-200 text-gray-700 text-center py-3 px-4 rounded-lg font-semibold hover:bg-gray-300 transition">
                    <i class="fas fa-home mr-2"></i>Về dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>