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

$service_id = intval($_GET['id'] ?? 0);

if ($service_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID dịch vụ không hợp lệ']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT vs.*, vp.name as package_name, vp.cpu_cores, vp.ram_gb, vp.storage_gb, vp.bandwidth_gb,
               os.name as os_name, os.version as os_version, os.os_type,
               o.order_code, o.billing_cycle, o.total_amount
        FROM vps_services vs
        LEFT JOIN vps_packages vp ON vs.package_id = vp.id
        LEFT JOIN operating_systems os ON vs.os_id = os.id
        LEFT JOIN orders o ON vs.order_id = o.id
        WHERE vs.id = ? AND vs.user_id = ?
    ");
    $stmt->execute([$service_id, $_SESSION['user_id']]);
    $service = $stmt->fetch();
    
    if (!$service) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy dịch vụ']);
        exit();
    }
    
    // Format data for JSON response
    $service['created_at_formatted'] = formatDate($service['created_at']);
    $service['expiry_date_formatted'] = $service['expiry_date'] ? formatDate($service['expiry_date']) : null;
    
    echo json_encode(['success' => true, 'service' => $service]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống']);
}
?>