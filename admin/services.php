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

// Xử lý các action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $service_id = intval($_POST['service_id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM vps_services WHERE id = ?");
        $stmt->execute([$service_id]);
        $service = $stmt->fetch();
        
        if (!$service) {
            $_SESSION['flash_message'] = 'Không tìm thấy dịch vụ';
            $_SESSION['flash_type'] = 'error';
        } else {
            switch ($_POST['action']) {
                case 'update_status':
                    $new_status = $_POST['new_status'] ?? '';
                    if (in_array($new_status, ['pending', 'active', 'suspended', 'expired', 'cancelled'])) {
                        $stmt = $pdo->prepare("UPDATE vps_services SET status = ? WHERE id = ?");
                        $stmt->execute([$new_status, $service_id]);
                        
                        $_SESSION['flash_message'] = 'Đã cập nhật trạng thái dịch vụ';
                        $_SESSION['flash_type'] = 'success';
                    }
                    break;
                    
                case 'update_details':
                    $ip_address = $_POST['ip_address'] ?? '';
                    $username = $_POST['username'] ?? '';
                    $password = $_POST['password'] ?? '';
                    $ssh_port = intval($_POST['ssh_port'] ?? 22);
                    
                    $stmt = $pdo->prepare("
                        UPDATE vps_services 
                        SET ip_address = ?, username = ?, password = ?, ssh_port = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$ip_address, $username, $password, $ssh_port, $service_id]);
                    
                    $_SESSION['flash_message'] = 'Đã cập nhật thông tin dịch vụ';
                    $_SESSION['flash_type'] = 'success';
                    break;
                    
                case 'extend_expiry':
                    $days = intval($_POST['days'] ?? 0);
                    if ($days > 0) {
                        $new_expiry = date('Y-m-d', strtotime($service['expiry_date'] . " +$days days"));
                        
                        $stmt = $pdo->prepare("UPDATE vps_services SET expiry_date = ? WHERE id = ?");
                        $stmt->execute([$new_expiry, $service_id]);
                        
                        $_SESSION['flash_message'] = 'Đã gia hạn dịch vụ';
                        $_SESSION['flash_type'] = 'success';
                    }
                    break;
            }
        }
    } catch(PDOException $e) {
        $_SESSION['flash_message'] = 'Lỗi: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'error';
    }
    
    redirect('services.php');
}

// Lấy danh sách dịch vụ
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

try {
    $where_clause = '';
    $params = [];
    
    if (!empty($status_filter)) {
        $where_clause .= "WHERE vs.status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($search)) {
        $where_clause .= ($where_clause ? ' AND ' : 'WHERE') . " (u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ? OR vp.name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    // Lấy tổng số dịch vụ
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM vps_services vs LEFT JOIN users u ON vs.user_id = u.id LEFT JOIN vps_packages vp ON vs.package_id = vp.id $where_clause");
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Lấy danh sách dịch vụ
    $stmt = $pdo->prepare("
        SELECT vs.*, u.username, u.email, u.full_name, vp.name as package_name, os.name as os_name, os.version as os_version,
               o.order_code, o.billing_cycle
        FROM vps_services vs
        LEFT JOIN users u ON vs.user_id = u.id
        LEFT JOIN vps_packages vp ON vs.package_id = vp.id
        LEFT JOIN operating_systems os ON vs.os_id = os.id
        LEFT JOIN orders o ON vs.order_id = o.id
        $where_clause
        ORDER BY vs.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $services = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $services = [];
    $total = 0;
}

$page_title = "Quản lý dịch vụ - Admin";
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
                <a href="index.php" class="py-3 px-4 hover:bg-gray-600 transition">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <a href="users.php" class="py-3 px-4 hover:bg-gray-600 transition">
                    <i class="fas fa-users mr-2"></i>Người dùng
                </a>
                <a href="services.php" class="py-3 px-4 hover:bg-gray-600 transition border-b-2 border-blue-500">
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
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Quản lý dịch vụ VPS</h1>
            
            <!-- Search and Filter -->
            <div class="flex items-center space-x-4">
                <form method="GET" class="flex items-center space-x-2">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Tìm kiếm..." class="px-3 py-2 border border-gray-300 rounded-lg">
                    <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                        <option value="suspended" <?php echo $status_filter == 'suspended' ? 'selected' : ''; ?>>Tạm dừng</option>
                        <option value="expired" <?php echo $status_filter == 'expired' ? 'selected' : ''; ?>>Hết hạn</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                    </select>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gói dịch vụ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thông tin VPS</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hết hạn</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày tạo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($services as $service): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium">#<?php echo $service['id']; ?></div>
                                    <div class="text-xs text-gray-500"><?php echo $service['order_code']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $service['full_name']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $service['email']; ?></div>
                                    <div class="text-xs text-gray-500">@<?php echo $service['username']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $service['package_name']; ?></div>
                                    <div class="text-xs text-gray-500"><?php echo $service['os_name']; ?> <?php echo $service['os_version']; ?></div>
                                    <div class="text-xs text-gray-500"><?php echo $service['billing_cycle']; ?> tháng</div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($service['ip_address']): ?>
                                        <div class="text-sm font-mono"><?php echo $service['ip_address']; ?></div>
                                        <div class="text-xs text-gray-500">User: <?php echo $service['username']; ?></div>
                                        <div class="text-xs text-gray-500">Port: <?php echo $service['ssh_port']; ?></div>
                                    <?php else: ?>
                                        <div class="text-sm text-gray-500">Chưa cấp phát</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo formatDate($service['expiry_date']); ?></div>
                                    <?php 
                                    $days_left = floor((strtotime($service['expiry_date']) - time()) / (60 * 60 * 24));
                                    if ($days_left <= 7 && $days_left > 0) {
                                        echo '<div class="text-xs text-yellow-600">Còn ' . $days_left . ' ngày</div>';
                                    } elseif ($days_left <= 0) {
                                        echo '<div class="text-xs text-red-600">Đã hết hạn</div>';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $status_colors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'active' => 'bg-green-100 text-green-800',
                                        'suspended' => 'bg-red-100 text-red-800',
                                        'expired' => 'bg-gray-100 text-gray-800',
                                        'cancelled' => 'bg-gray-100 text-gray-800'
                                    ];
                                    $status_text = [
                                        'pending' => 'Chờ xử lý',
                                        'active' => 'Hoạt động',
                                        'suspended' => 'Tạm dừng',
                                        'expired' => 'Hết hạn',
                                        'cancelled' => 'Đã hủy'
                                    ];
                                    ?>
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $status_colors[$service['status']]; ?>">
                                        <?php echo $status_text[$service['status']]; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo formatDate($service['created_at']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <button onclick="showDetailsModal(<?php echo $service['id']; ?>)" class="text-blue-600 hover:text-blue-800 text-sm" title="Chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <button onclick="showEditModal(<?php echo $service['id']; ?>)" class="text-green-600 hover:text-green-800 text-sm" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <button onclick="showStatusModal(<?php echo $service['id']; ?>, '<?php echo $service['status']; ?>')" class="text-yellow-600 hover:text-yellow-800 text-sm" title="Đổi trạng thái">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total > $limit): ?>
                <div class="px-6 py-4 border-t border-gray-200">
                    <?php echo paginate($total, $page, $limit, 'services.php'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Service Details Modal -->
<div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold">Chi tiết dịch vụ</h3>
                    <button onclick="closeDetailsModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div id="serviceDetails"></div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Service Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Cập nhật thông tin VPS</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_details">
                    <input type="hidden" name="service_id" id="edit_service_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Địa chỉ IP</label>
                        <input type="text" name="ip_address" id="edit_ip_address" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tên đăng nhập</label>
                        <input type="text" name="username" id="edit_username" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mật khẩu</label>
                        <input type="text" name="password" id="edit_password" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Port SSH</label>
                        <input type="number" name="ssh_port" id="edit_ssh_port" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div class="flex space-x-3">
                        <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg">
                            Hủy
                        </button>
                        <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg">
                            Lưu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Status Modal -->
<div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Đổi trạng thái dịch vụ</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="service_id" id="status_service_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái mới</label>
                        <select name="new_status" id="new_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="pending">Chờ xử lý</option>
                            <option value="active">Hoạt động</option>
                            <option value="suspended">Tạm dừng</option>
                            <option value="expired">Hết hạn</option>
                            <option value="cancelled">Đã hủy</option>
                        </select>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button type="button" onclick="closeStatusModal()" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg">
                            Hủy
                        </button>
                        <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg">
                            Cập nhật
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const services = <?php echo json_encode($services); ?>;

function showDetailsModal(serviceId) {
    const service = services.find(s => s.id == serviceId);
    if (!service) return;
    
    const detailsHtml = `
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-sm text-gray-500">ID dịch vụ:</span>
                    <div class="font-medium">#${service.id}</div>
                </div>
                <div>
                    <span class="text-sm text-gray-500">Mã đơn hàng:</span>
                    <div class="font-medium">${service.order_code}</div>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-sm text-gray-500">Khách hàng:</span>
                    <div class="font-medium">${service.full_name}</div>
                    <div class="text-sm text-gray-500">${service.email}</div>
                </div>
                <div>
                    <span class="text-sm text-gray-500">Gói dịch vụ:</span>
                    <div class="font-medium">${service.package_name}</div>
                    <div class="text-sm text-gray-500">${service.os_name} ${service.os_version || ''}</div>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-sm text-gray-500">Địa chỉ IP:</span>
                    <div class="font-medium font-mono">${service.ip_address || 'Chưa cấp phát'}</div>
                </div>
                <div>
                    <span class="text-sm text-gray-500">Thông tin đăng nhập:</span>
                    <div class="text-sm">
                        <div>User: ${service.username || 'N/A'}</div>
                        <div>Pass: ${service.password ? '******' : 'N/A'}</div>
                        <div>Port: ${service.ssh_port || 22}</div>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-sm text-gray-500">Ngày tạo:</span>
                    <div class="font-medium">${formatDate(service.created_at)}</div>
                </div>
                <div>
                    <span class="text-sm text-gray-500">Ngày hết hạn:</span>
                    <div class="font-medium">${formatDate(service.expiry_date)}</div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('serviceDetails').innerHTML = detailsHtml;
    document.getElementById('detailsModal').classList.remove('hidden');
}

function showEditModal(serviceId) {
    const service = services.find(s => s.id == serviceId);
    if (!service) return;
    
    document.getElementById('edit_service_id').value = serviceId;
    document.getElementById('edit_ip_address').value = service.ip_address || '';
    document.getElementById('edit_username').value = service.username || '';
    document.getElementById('edit_password').value = service.password || '';
    document.getElementById('edit_ssh_port').value = service.ssh_port || 22;
    
    document.getElementById('editModal').classList.remove('hidden');
}

function showStatusModal(serviceId, currentStatus) {
    document.getElementById('status_service_id').value = serviceId;
    document.getElementById('new_status').value = currentStatus;
    
    document.getElementById('statusModal').classList.remove('hidden');
}

function closeDetailsModal() {
    document.getElementById('detailsModal').classList.add('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
}
</script>

<?php include 'includes/footer.php'; ?>