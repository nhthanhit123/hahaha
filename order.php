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

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $package_id = intval($_POST['package_id'] ?? 0);
    $os_id = intval($_POST['os_id'] ?? 0);
    $billing_cycle = $_POST['billing_cycle'] ?? '';
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate
    if ($package_id <= 0) {
        $errors[] = 'Vui lòng chọn gói dịch vụ';
    }
    
    if ($os_id <= 0) {
        $errors[] = 'Vui lòng chọn hệ điều hành';
    }
    
    if (!in_array($billing_cycle, ['1', '6', '12', '24'])) {
        $errors[] = 'Chu kỳ thanh toán không hợp lệ';
    }
    
    if ($total_amount <= 0) {
        $errors[] = 'Số tiền không hợp lệ';
    }
    
    // Kiểm tra package và OS có tồn tại không
    if (empty($errors)) {
        try {
            // Kiểm tra package
            $stmt = $pdo->prepare("SELECT * FROM vps_packages WHERE id = ? AND is_active = 1");
            $stmt->execute([$package_id]);
            $package = $stmt->fetch();
            
            if (!$package) {
                $errors[] = 'Gói dịch vụ không tồn tại';
            }
            
            // Kiểm tra OS
            $stmt = $pdo->prepare("SELECT * FROM operating_systems WHERE id = ? AND is_active = 1");
            $stmt->execute([$os_id]);
            $os = $stmt->fetch();
            
            if (!$os) {
                $errors[] = 'Hệ điều hành không tồn tại';
            }
            
            // Kiểm tra số dư
            $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user_balance = $stmt->fetch()['balance'];
            
            if ($user_balance < $total_amount) {
                $errors[] = 'Số dư không đủ. Vui lòng nạp thêm tiền.';
            }
            
        } catch(PDOException $e) {
            $errors[] = 'Lỗi hệ thống, vui lòng thử lại';
        }
    }
    
    // Nếu không có lỗi thì tạo đơn hàng
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Tạo mã đơn hàng
            $order_code = generateOrderCode();
            
            // Trừ tiền từ tài khoản
            $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$total_amount, $_SESSION['user_id']]);
            
            // Tạo đơn hàng
            $stmt = $pdo->prepare("
                INSERT INTO orders (order_code, user_id, package_id, os_id, billing_cycle, total_amount, status, payment_method, notes) 
                VALUES (?, ?, ?, ?, ?, ?, 'paid', 'balance', ?)
            ");
            $stmt->execute([$order_code, $_SESSION['user_id'], $package_id, $os_id, $billing_cycle, $total_amount, $notes]);
            
            $order_id = $pdo->lastInsertId();
            
            // Tạo dịch vụ VPS
            $expiry_date = date('Y-m-d', strtotime("+$billing_cycle months"));
            
            $stmt = $pdo->prepare("
                INSERT INTO vps_services (order_id, user_id, package_id, os_id, status, expiry_date) 
                VALUES (?, ?, ?, ?, 'pending', ?)
            ");
            $stmt->execute([$order_id, $_SESSION['user_id'], $package_id, $os_id, $expiry_date]);
            
            $pdo->commit();
            
            // Cập nhật session balance
            $_SESSION['user_balance'] = $user_balance - $total_amount;
            
            // Log hoạt động
            logActivity($_SESSION['user_id'], 'create_order', "Tạo đơn hàng $order_code - Gói: {$package['name']}");
            
            // Gửi thông báo Telegram
            $telegram_message = "
🆕 ĐƠN HÀNG MỚI

📋 Mã đơn: $order_code
👤 Khách hàng: {$_SESSION['user_name']} ({$_SESSION['user_email']})
💰 Số tiền: " . formatMoney($total_amount) . "
📦 Gói dịch vụ: {$package['name']}
💻 Hệ điều hành: {$os['name']} {$os['version']}
⏰ Chu kỳ: $billing_cycle tháng
📅 Ngày đặt: " . date('d/m/Y H:i') . "
📝 Ghi chú: " . ($notes ?: 'Không có') . "

Vui lòng xử lý đơn hàng này!
            ";
            
            sendTelegram($telegram_message);
            
            $_SESSION['flash_message'] = 'Đặt hàng thành công! Đơn hàng của bạn đang được xử lý.';
            $_SESSION['flash_type'] = 'success';
            
            redirect("order-success.php?order_id=$order_id");
            
        } catch(PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Lỗi khi tạo đơn hàng: ' . $e->getMessage();
        }
    }
    
    // Nếu có lỗi, lưu thông tin để hiển thị lại
    if (!empty($errors)) {
        $_SESSION['order_errors'] = $errors;
        $_SESSION['order_data'] = $_POST;
        redirect('packages.php');
    }
} else {
    // Nếu không phải POST, chuyển về trang packages
    redirect('packages.php');
}
?>