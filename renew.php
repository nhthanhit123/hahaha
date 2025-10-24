<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = 'Vui lòng đăng nhập để tiếp tục';
    $_SESSION['flash_type'] = 'warning';
    redirect('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

$service_id = intval($_GET['service_id'] ?? 0);
$errors = [];
$success = '';

// Lấy thông tin dịch vụ
$service = null;
if ($service_id > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT vs.*, vp.name as package_name, vp.price_monthly,
                   os.name as os_name, os.version as os_version
            FROM vps_services vs
            LEFT JOIN vps_packages vp ON vs.package_id = vp.id
            LEFT JOIN operating_systems os ON vs.os_id = os.id
            WHERE vs.id = ? AND vs.user_id = ?
        ");
        $stmt->execute([$service_id, $_SESSION['user_id']]);
        $service = $stmt->fetch();
        
        if (!$service) {
            $_SESSION['flash_message'] = 'Không tìm thấy dịch vụ';
            $_SESSION['flash_type'] = 'error';
            redirect('services.php');
        }
        
        // Kiểm tra xem có thể gia hạn không
        if ($service['status'] != 'active') {
            $_SESSION['flash_message'] = 'Chỉ có thể gia hạn dịch vụ đang hoạt động';
            $_SESSION['flash_type'] = 'error';
            redirect('services.php');
        }
        
    } catch(PDOException $e) {
        $_SESSION['flash_message'] = 'Lỗi hệ thống';
        $_SESSION['flash_type'] = 'error';
        redirect('services.php');
    }
} else {
    redirect('services.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $months = intval($_POST['months'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'balance';
    
    // Validate
    if (!in_array($months, [1, 6, 12, 24])) {
        $errors[] = 'Thời gian gia hạn không hợp lệ';
    }
    
    if (!in_array($payment_method, ['balance'])) {
        $errors[] = 'Phương thức thanh toán không hợp lệ';
    }
    
    // Tính số tiền
    $amount = $service['price_monthly'] * $months;
    
    // Kiểm tra số dư
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user_balance = $stmt->fetch()['balance'];
            
            if ($user_balance < $amount) {
                $errors[] = 'Số dư không đủ. Vui lòng nạp thêm tiền.';
            }
            
        } catch(PDOException $e) {
            $errors[] = 'Lỗi hệ thống, vui lòng thử lại';
        }
    }
    
    // Nếu không có lỗi thì tạo yêu cầu gia hạn
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Tính ngày hết hạn mới
            $current_expiry = new DateTime($service['expiry_date']);
            $new_expiry = clone $current_expiry;
            $new_expiry->add(new DateInterval("P{$months}M"));
            
            // Trừ tiền
            $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$amount, $_SESSION['user_id']]);
            
            // Tạo bản ghi gia hạn
            $stmt = $pdo->prepare("
                INSERT INTO renewals (service_id, user_id, months, amount, status, payment_method, old_expiry_date, new_expiry_date) 
                VALUES (?, ?, ?, ?, 'paid', ?, ?, ?)
            ");
            $stmt->execute([
                $service_id,
                $_SESSION['user_id'],
                $months,
                $amount,
                $payment_method,
                $service['expiry_date'],
                $new_expiry->format('Y-m-d')
            ]);
            
            // Cập nhật ngày hết hạn của dịch vụ
            $stmt = $pdo->prepare("UPDATE vps_services SET expiry_date = ?, last_renewal_date = CURDATE() WHERE id = ?");
            $stmt->execute([$new_expiry->format('Y-m-d'), $service_id]);
            
            $pdo->commit();
            
            // Cập nhật session balance
            $_SESSION['user_balance'] = $user_balance - $amount;
            
            // Log hoạt động
            logActivity($_SESSION['user_id'], 'renew_service', "Gia hạn dịch vụ ID: $service_id - $months tháng");
            
            // Gửi thông báo Telegram
            $telegram_message = "
🔄 GIA HẠN DỊCH VỤ

📋 Mã dịch vụ: #{$service['id']}
👤 Khách hàng: {$_SESSION['user_name']} ({$_SESSION['user_email']})
💰 Số tiền: " . formatMoney($amount) . "
📦 Gói dịch vụ: {$service['package_name']}
💻 Hệ điều hành: {$service['os_name']} {$service['os_version']}
⏰ Gia hạn thêm: $months tháng
📅 Hết hạn cũ: " . formatDate($service['expiry_date']) . "
📅 Hết hạn mới: " . formatDate($new_expiry->format('Y-m-d')) . "
📅 Ngày gia hạn: " . date('d/m/Y H:i') . "

Gia hạn thành công!
            ";
            
            sendTelegram($telegram_message);
            
            $_SESSION['flash_message'] = 'Gia hạn dịch vụ thành công!';
            $_SESSION['flash_type'] = 'success';
            
            redirect("renew-success.php?service_id=$service_id");
            
        } catch(PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Lỗi khi gia hạn dịch vụ: ' . $e->getMessage();
        }
    }
}

$page_title = "Gia hạn VPS - " . SITE_NAME;
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <ol class="flex items-center space-x-2 text-sm text-gray-500">
                    <li><a href="services.php" class="hover:text-gray-700">Dịch vụ của tôi</a></li>
                    <li><i class="fas fa-chevron-right text-xs"></i></li>
                    <li><span class="text-gray-900">Gia hạn VPS</span></li>
                </ol>
            </nav>

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
                            <span class="text-sm text-gray-500">Ngày hết hạn hiện tại</span>
                            <div class="font-semibold"><?php echo formatDate($service['expiry_date']); ?></div>
                            <?php 
                            $days_left = floor((strtotime($service['expiry_date']) - time()) / (60 * 60 * 24));
                            if ($days_left <= 7 && $days_left > 0) {
                                echo '<div class="text-yellow-600 text-sm">Còn ' . $days_left . ' ngày</div>';
                            }
                            ?>
                        </div>
                        
                        <div class="mb-4">
                            <span class="text-sm text-gray-500">Giá tháng</span>
                            <div class="font-semibold text-blue-600"><?php echo formatMoney($service['price_monthly']); ?></div>
                        </div>
                        
                        <div class="mb-4">
                            <span class="text-sm text-gray-500">Trạng thái</span>
                            <div>
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                    Hoạt động
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Renewal Form -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Gia hạn dịch vụ</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="mb-4">
                        <?php foreach ($errors as $error): ?>
                            <?php echo showAlert($error, 'error'); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-4">Chọn thời gian gia hạn</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <label class="cursor-pointer">
                                <input type="radio" name="months" value="1" class="hidden peer" required>
                                <div class="border-2 border-gray-200 rounded-lg p-4 text-center hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition">
                                    <div class="font-semibold">1 tháng</div>
                                    <div class="text-sm text-gray-500"><?php echo formatMoney($service['price_monthly']); ?></div>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="months" value="6" class="hidden peer">
                                <div class="border-2 border-gray-200 rounded-lg p-4 text-center hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition">
                                    <div class="font-semibold">6 tháng</div>
                                    <div class="text-sm text-gray-500"><?php echo formatMoney($service['price_monthly'] * 6); ?></div>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="months" value="12" class="hidden peer">
                                <div class="border-2 border-gray-200 rounded-lg p-4 text-center hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition">
                                    <div class="font-semibold">12 tháng</div>
                                    <div class="text-sm text-gray-500"><?php echo formatMoney($service['price_monthly'] * 12); ?></div>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="months" value="24" class="hidden peer">
                                <div class="border-2 border-gray-200 rounded-lg p-4 text-center hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition">
                                    <div class="font-semibold">24 tháng</div>
                                    <div class="text-sm text-gray-500"><?php echo formatMoney($service['price_monthly'] * 24); ?></div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phương thức thanh toán</label>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <input type="radio" name="payment_method" value="balance" checked class="mr-3">
                                <div>
                                    <div class="font-medium">Số dư tài khoản</div>
                                    <div class="text-sm text-gray-500">Số dư hiện tại: <?php echo formatMoney($_SESSION['user_balance']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Amount Display -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold">Tổng thanh toán:</span>
                            <span class="text-2xl font-bold text-blue-600" id="totalAmount">0đ</span>
                        </div>
                    </div>
                    
                    <div class="flex space-x-4">
                        <a href="services.php" class="flex-1 bg-gray-200 text-gray-700 text-center py-3 px-4 rounded-lg font-semibold hover:bg-gray-300 transition">
                            <i class="fas fa-arrow-left mr-2"></i>Quay lại
                        </a>
                        <button type="submit" class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 transition">
                            <i class="fas fa-sync mr-2"></i>Gia hạn ngay
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthInputs = document.querySelectorAll('input[name="months"]');
    const totalAmountElement = document.getElementById('totalAmount');
    const monthlyPrice = <?php echo $service['price_monthly']; ?>;
    
    function updateTotalAmount() {
        const selectedMonths = document.querySelector('input[name="months"]:checked');
        if (selectedMonths) {
            const months = parseInt(selectedMonths.value);
            const total = monthlyPrice * months;
            totalAmountElement.textContent = formatMoney(total);
        } else {
            totalAmountElement.textContent = '0đ';
        }
    }
    
    monthInputs.forEach(input => {
        input.addEventListener('change', updateTotalAmount);
    });
    
    function formatMoney(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
    }
});
</script>

<?php include 'includes/footer.php'; ?>