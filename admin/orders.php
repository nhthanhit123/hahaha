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
    $order_id = intval($_POST['order_id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        if (!$order) {
            $_SESSION['flash_message'] = 'Không tìm thấy đơn hàng';
            $_SESSION['flash_type'] = 'error';
        } else {
            switch ($_POST['action']) {
                case 'approve':
                    // Cập nhật trạng thái đơn hàng
                    $stmt = $pdo->prepare("UPDATE orders SET status = 'processing' WHERE id = ?");
                    $stmt->execute([$order_id]);
                    
                    // Tạo dịch vụ VPS
                    $expiry_date = date('Y-m-d', strtotime("+{$order['billing_cycle']} months"));
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO vps_services (order_id, user_id, package_id, os_id, status, expiry_date) 
                        VALUES (?, ?, ?, ?, 'pending', ?)
                    ");
                    $stmt->execute([$order_id, $order['user_id'], $order['package_id'], $order['os_id'], $expiry_date]);
                    
                    $_SESSION['flash_message'] = 'Đã duyệt đơn hàng và tạo dịch vụ';
                    $_SESSION['flash_type'] = 'success';
                    break;
                    
                case 'activate':
                    // Kích hoạt dịch vụ - cấp IP và thông tin đăng nhập
                    $ip_address = $_POST['ip_address'] ?? '';
                    $username = $_POST['username'] ?? '';
                    $password = $_POST['password'] ?? '';
                    
                    if (empty($ip_address) || empty($username) || empty($password)) {
                        $_SESSION['flash_message'] = 'Vui lòng nhập đầy đủ thông tin kích hoạt';
                        $_SESSION['flash_type'] = 'error';
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE vps_services 
                            SET ip_address = ?, username = ?, password = ?, status = 'active' 
                            WHERE order_id = ?
                        ");
                        $stmt->execute([$ip_address, $username, $password, $order_id]);
                        
                        $stmt = $pdo->prepare("UPDATE orders SET status = 'active' WHERE id = ?");
                        $stmt->execute([$order_id]);
                        
                        $_SESSION['flash_message'] = 'Đã kích hoạt dịch vụ thành công';
                        $_SESSION['flash_type'] = 'success';
                    }
                    break;
                    
                case 'cancel':
                    $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
                    $stmt->execute([$order_id]);
                    
                    // Hoàn tiền cho user
                    $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                    $stmt->execute([$order['total_amount'], $order['user_id']]);
                    
                    $_SESSION['flash_message'] = 'Đã hủy đơn hàng và hoàn tiền';
                    $_SESSION['flash_type'] = 'success';
                    break;
            }
        }
    } catch(PDOException $e) {
        $_SESSION['flash_message'] = 'Lỗi: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'error';
    }
    
    redirect('orders.php');
}

// Lấy danh sách đơn hàng
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$status_filter = $_GET['status'] ?? '';

try {
    $where_clause = '';
    $params = [];
    
    if (!empty($status_filter)) {
        $where_clause = "WHERE o.status = ?";
        $params[] = $status_filter;
    }
    
    // Lấy tổng số đơn hàng
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders o $where_clause");
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Lấy danh sách đơn hàng
    $stmt = $pdo->prepare("
        SELECT o.*, u.full_name, u.email, vp.name as package_name, os.name as os_name, os.version as os_version
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN vps_packages vp ON o.package_id = vp.id
        LEFT JOIN operating_systems os ON o.os_id = os.id
        $where_clause
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $orders = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $orders = [];
    $total = 0;
}

$page_title = "Quản lý đơn hàng - Admin";
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
                <a href="services.php" class="py-3 px-4 hover:bg-gray-600 transition">
                    <i class="fas fa-server mr-2"></i>Dịch vụ
                </a>
                <a href="orders.php" class="py-3 px-4 hover:bg-gray-600 transition border-b-2 border-blue-500">
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
            <h1 class="text-2xl font-bold text-gray-900">Quản lý đơn hàng</h1>
            
            <!-- Filter -->
            <div class="flex items-center space-x-4">
                <select onchange="location.href='?status='+this.value" class="px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                    <option value="paid" <?php echo $status_filter == 'paid' ? 'selected' : ''; ?>>Đã thanh toán</option>
                    <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Đã kích hoạt</option>
                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                </select>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã đơn</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gói dịch vụ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hệ điều hành</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số tiền</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày đặt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-mono text-sm"><?php echo $order['order_code']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $order['full_name']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $order['email']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo $order['package_name']; ?></div>
                                    <div class="text-xs text-gray-500"><?php echo $order['billing_cycle']; ?> tháng</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?php 
                                        $os_icon = $order['os_type'] == 'windows' ? 'fab fa-windows text-blue-600' : 'fab fa-linux text-orange-600';
                                        ?>
                                        <i class="<?php echo $os_icon; ?> mr-1"></i>
                                        <?php echo $order['os_name']; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold"><?php echo formatMoney($order['total_amount']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $status_colors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'paid' => 'bg-blue-100 text-blue-800',
                                        'processing' => 'bg-purple-100 text-purple-800',
                                        'active' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800'
                                    ];
                                    $status_text = [
                                        'pending' => 'Chờ xử lý',
                                        'paid' => 'Đã thanh toán',
                                        'processing' => 'Đang xử lý',
                                        'active' => 'Đã kích hoạt',
                                        'cancelled' => 'Đã hủy'
                                    ];
                                    ?>
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $status_colors[$order['status']]; ?>">
                                        <?php echo $status_text[$order['status']]; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo formatDate($order['created_at']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <?php if ($order['status'] == 'pending'): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" class="text-green-600 hover:text-green-800" title="Duyệt">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['status'] == 'processing'): ?>
                                            <button onclick="showActivateModal(<?php echo $order['id']; ?>)" class="text-blue-600 hover:text-blue-800" title="Kích hoạt">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($order['status'], ['pending', 'paid', 'processing'])): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Bạn có chắc muốn hủy đơn hàng này?')">
                                                <input type="hidden" name="action" value="cancel">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-800" title="Hủy">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
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
                    <?php echo paginate($total, $page, $limit, 'orders.php'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Activate Modal -->
<div id="activateModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Kích hoạt dịch vụ VPS</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="activate">
                    <input type="hidden" name="order_id" id="activate_order_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Địa chỉ IP</label>
                        <input type="text" name="ip_address" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tên đăng nhập</label>
                        <input type="text" name="username" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mật khẩu</label>
                        <input type="text" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div class="flex space-x-3">
                        <button type="button" onclick="closeActivateModal()" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg">
                            Hủy
                        </button>
                        <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg">
                            Kích hoạt
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showActivateModal(orderId) {
    document.getElementById('activate_order_id').value = orderId;
    document.getElementById('activateModal').classList.remove('hidden');
}

function closeActivateModal() {
    document.getElementById('activateModal').classList.add('hidden');
}
</script>

<?php include 'includes/footer.php'; ?>