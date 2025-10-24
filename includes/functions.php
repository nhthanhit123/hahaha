<?php
require_once 'config.php';
require_once 'database.php';

function fetchVpsPackages() {
    $sql = "SELECT * FROM vps_packages WHERE status = 'active' ORDER BY selling_price ASC";
    return fetchAll($sql);
}

function fetchOperatingSystems() {
    $sql = "SELECT * FROM operating_systems WHERE status = 'active' ORDER BY name ASC";
    return fetchAll($sql);
}

function getVpsPackage($id) {
    $sql = "SELECT * FROM vps_packages WHERE id = ? AND status = 'active'";
    return fetchOne($sql, [$id]);
}

function getOperatingSystem($id) {
    $sql = "SELECT * FROM operating_systems WHERE id = ? AND status = 'active'";
    return fetchOne($sql, [$id]);
}

function getUser($id) {
    $sql = "SELECT * FROM users WHERE id = ?";
    return fetchOne($sql, [$id]);
}

function getUserByEmail($email) {
    $sql = "SELECT * FROM users WHERE email = ?";
    return fetchOne($sql, [$email]);
}

function getUserByUsername($username) {
    $sql = "SELECT * FROM users WHERE username = ?";
    return fetchOne($sql, [$username]);
}

function createUser($data) {
    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    return insertData('users', $data);
}

function updateUser($id, $data) {
    if (isset($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    return updateData('users', $data, 'id = ?', [$id]);
}

function createVpsOrder($data) {
    return insertData('vps_orders', $data);
}

function getUserOrders($userId) {
    $sql = "SELECT vo.*, vp.name as package_name, os.name as os_name 
            FROM vps_orders vo 
            LEFT JOIN vps_packages vp ON vo.package_id = vp.id 
            LEFT JOIN operating_systems os ON vo.os_id = os.id 
            WHERE vo.user_id = ? 
            ORDER BY vo.created_at DESC";
    return fetchAll($sql, [$userId]);
}

function getOrder($id, $userId = null) {
    $sql = "SELECT vo.*, vp.name as package_name, os.name as os_name 
            FROM vps_orders vo 
            LEFT JOIN vps_packages vp ON vo.package_id = vp.id 
            LEFT JOIN operating_systems os ON vo.os_id = os.id 
            WHERE vo.id = ?";
    $params = [$id];
    
    if ($userId) {
        $sql .= " AND vo.user_id = ?";
        $params[] = $userId;
    }
    
    return fetchOne($sql, $params);
}

function updateOrder($id, $data) {
    return updateData('vps_orders', $data, 'id = ?', [$id]);
}

function createRenewal($data) {
    return insertData('renewals', $data);
}

function getOrderRenewals($orderId) {
    $sql = "SELECT * FROM renewals WHERE order_id = ? ORDER BY created_at DESC";
    return fetchAll($sql, [$orderId]);
}

function createDeposit($data) {
    return insertData('deposits', $data);
}

function getUserDeposits($userId) {
    $sql = "SELECT * FROM deposits WHERE user_id = ? ORDER BY created_at DESC";
    return fetchAll($sql, [$userId]);
}

function updateDeposit($id, $data) {
    return updateData('deposits', $data, 'id = ?', [$id]);
}

function getBankAccounts() {
    $sql = "SELECT * FROM bank_accounts WHERE status = 'active' ORDER BY bank_name ASC";
    return fetchAll($sql);
}

function updateUserBalance($userId, $amount) {
    $sql = "UPDATE users SET balance = balance + ? WHERE id = ?";
    $stmt = executeQuery($sql, [$amount, $userId]);
    return $stmt->affected_rows;
}

function calculatePrice($basePrice, $months) {
    $multipliers = [
        '1' => 1,
        '6' => 5.5,
        '12' => 10,
        '24' => 18
    ];
    
    return $basePrice * ($multipliers[$months] ?? 1);
}

function sendOrderNotification($order, $user, $package, $os) {
    $message = "🆕 <b>Đơn hàng VPS mới</b>\n\n";
    $message .= "👤 <b>Khách hàng:</b> {$user['full_name']} ({$user['username']})\n";
    $message .= "📧 <b>Email:</b> {$user['email']}\n";
    $message .= "📱 <b>Điện thoại:</b> {$user['phone']}\n\n";
    $message .= "🖥️ <b>Gói VPS:</b> {$package['name']}\n";
    $message .= "💾 <b>Cấu hình:</b> {$package['cpu']} - {$package['ram']} - {$package['storage']}\n";
    $message .= "🌍 <b>Vị trí:</b> {$package['location']}\n";
    $message .= "💻 <b>Hệ điều hành:</b> {$os['name']}\n";
    $message .= "⏰ <b>Chu kỳ:</b> {$order['billing_cycle']} tháng\n";
    $message .= "💰 <b>Giá:</b> " . formatPrice($order['total_price']) . "\n";
    $message .= "📅 <b>Thời gian:</b> " . formatDate($order['created_at']) . "\n\n";
    $message .= "🔗 <b>Link quản lý:</b> " . BASE_URL . "/admin/orders.php";
    
    return sendTelegramNotification($message);
}

function sendRenewalNotification($renewal, $order, $user) {
    $message = "🔄 <b>Gia hạn VPS</b>\n\n";
    $message .= "👤 <b>Khách hàng:</b> {$user['full_name']} ({$user['username']})\n";
    $message .= "📧 <b>Email:</b> {$user['email']}\n\n";
    $message .= "🖥️ <b>Mã đơn:</b> #{$order['id']}\n";
    $message .= "⏰ <b>Gia hạn:</b> {$renewal['months']} tháng\n";
    $message .= "💰 <b>Giá:</b> " . formatPrice($renewal['price']) . "\n";
    $message .= "📅 <b>Thời gian:</b> " . formatDate($renewal['created_at']) . "\n\n";
    $message .= "🔗 <b>Link quản lý:</b> " . BASE_URL . "/admin/renewals.php";
    
    return sendTelegramNotification($message);
}

function sendDepositNotification($deposit, $user, $bank) {
    $message = "💰 <b>Yêu cầu nạp tiền</b>\n\n";
    $message .= "👤 <b>Khách hàng:</b> {$user['full_name']} ({$user['username']})\n";
    $message .= "📧 <b>Email:</b> {$user['email']}\n\n";
    $message .= "💳 <b>Ngân hàng:</b> {$bank['bank_name']}\n";
    $message .= "💰 <b>Số tiền:</b> " . formatPrice($deposit['amount']) . "\n";
    $message .= "📅 <b>Thời gian:</b> " . formatDate($deposit['created_at']) . "\n\n";
    $message .= "🔗 <b>Link quản lý:</b> " . BASE_URL . "/admin/deposits.php";
    
    return sendTelegramNotification($message);
}
?>