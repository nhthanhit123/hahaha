<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit();
}

header('Content-Type: application/json');

$order_id = intval($_GET['id'] ?? 0);

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT o.*, vp.name as package_name, os.name as os_name, os.version as os_version,
               vs.ip_address, vs.status as service_status, vs.expiry_date
        FROM orders o
        LEFT JOIN vps_packages vp ON o.package_id = vp.id
        LEFT JOIN operating_systems os ON o.os_id = os.id
        LEFT JOIN vps_services vs ON o.id = vs.order_id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
        exit();
    }
    
    echo json_encode(['success' => true, 'order' => $order]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống']);
}
?>