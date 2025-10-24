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

// Lấy danh sách đơn hàng của user
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$status_filter = $_GET['status'] ?? '';

try {
    $where_clause = "WHERE o.user_id = ?";
    $params = [$_SESSION['user_id']];
    
    if (!empty($status_filter)) {
        $where_clause .= " AND o.status = ?";
        $params[] = $status_filter;
    }
    
    // Lấy tổng số đơn hàng
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders o $where_clause");
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Lấy danh sách đơn hàng
    $stmt = $pdo->prepare("
        SELECT o.*, vp.name as package_name, os.name as os_name, os.version as os_version,
               vs.ip_address, vs.status as service_status, vs.expiry_date
        FROM orders o
        LEFT JOIN vps_packages vp ON o.package_id = vp.id
        LEFT JOIN operating_systems os ON o.os_id = os.id
        LEFT JOIN vps_services vs ON o.id = vs.order_id
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

$page_title = "Lịch sử đơn hàng - " . SITE_NAME;
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Lịch sử đơn hàng</h1>
            <a href="packages.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                <i class="fas fa-plus mr-2"></i>Mua VPS mới
            </a>
        </div>

        <!-- Filter -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="flex flex-wrap items-center gap-4">
                <span class="text-sm font-medium text-gray-700">Lọc theo trạng thái:</span>
                <div class="flex flex-wrap gap-2">
                    <a href="orders.php" class="px-3 py-1 text-sm rounded-full <?php echo empty($status_filter) ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition">
                        Tất cả
                    </a>
                    <a href="?status=pending" class="px-3 py-1 text-sm rounded-full <?php echo $status_filter == 'pending' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition">
                        Chờ xử lý
                    </a>
                    <a href="?status=paid" class="px-3 py-1 text-sm rounded-full <?php echo $status_filter == 'paid' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition">
                        Đã thanh toán
                    </a>
                    <a href="?status=processing" class="px-3 py-1 text-sm rounded-full <?php echo $status_filter == 'processing' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition">
                        Đang xử lý
                    </a>
                    <a href="?status=active" class="px-3 py-1 text-sm rounded-full <?php echo $status_filter == 'active' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition">
                        Đã kích hoạt
                    </a>
                    <a href="?status=cancelled" class="px-3 py-1 text-sm rounded-full <?php echo $status_filter == 'cancelled' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition">
                        Đã hủy
                    </a>
                </div>
            </div>
        </div>

        <?php if (empty($orders)): ?>
            <div class="bg-white rounded-lg shadow-lg p-12 text-center">
                <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-600 mb-2">Không tìm thấy đơn hàng nào</h2>
                <p class="text-gray-500 mb-6">Bạn chưa có đơn hàng nào hoặc không có đơn hàng nào phù hợp với bộ lọc</p>
                <a href="packages.php" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                    <i class="fas fa-shopping-cart mr-2"></i>Mua VPS ngay
                </a>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã đơn hàng</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gói dịch vụ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hệ điều hành</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chu kỳ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số tiền</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày đặt</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($orders as $order): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="font-mono text-sm font-medium"><?php echo $order['order_code']; ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($order['package_name']); ?></div>
                                        <?php if ($order['ip_address']): ?>
                                            <div class="text-xs text-gray-500">IP: <?php echo $order['ip_address']; ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <?php 
                                            $os_icon = strpos(strtolower($order['os_name']), 'windows') !== false ? 'fab fa-windows text-blue-600' : 'fab fa-linux text-orange-600';
                                            ?>
                                            <i class="<?php echo $os_icon; ?> mr-1"></i>
                                            <?php echo htmlspecialchars($order['os_name']); ?>
                                            <?php if ($order['os_version']): ?>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($order['os_version']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?php echo $order['billing_cycle']; ?> tháng</div>
                                        <?php if ($order['expiry_date']): ?>
                                            <div class="text-xs text-gray-500">Hết hạn: <?php echo formatDate($order['expiry_date']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-semibold text-blue-600"><?php echo formatMoney($order['total_amount']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $status_colors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'paid' => 'bg-blue-100 text-blue-800',
                                            'processing' => 'bg-purple-100 text-purple-800',
                                            'active' => 'bg-green-100 text-green-800',
                                            'expired' => 'bg-gray-100 text-gray-800',
                                            'cancelled' => 'bg-red-100 text-red-800'
                                        ];
                                        $status_text = [
                                            'pending' => 'Chờ xử lý',
                                            'paid' => 'Đã thanh toán',
                                            'processing' => 'Đang xử lý',
                                            'active' => 'Đã kích hoạt',
                                            'expired' => 'Hết hạn',
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
                                            <?php if ($order['status'] == 'active' && $order['ip_address']): ?>
                                                <a href="services.php" class="text-blue-600 hover:text-blue-800 text-sm" title="Xem dịch vụ">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($order['status'] == 'active' && $order['expiry_date'] && strtotime($order['expiry_date']) > time()): ?>
                                                <a href="renew.php?service_id=<?php echo $order['id']; ?>" class="text-green-600 hover:text-green-800 text-sm" title="Gia hạn">
                                                    <i class="fas fa-sync"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <button onclick="showOrderDetails(<?php echo $order['id']; ?>)" class="text-gray-600 hover:text-gray-800 text-sm" title="Chi tiết">
                                                <i class="fas fa-info-circle"></i>
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
                        <?php echo paginate($total, $page, $limit, 'orders.php'); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Chi tiết đơn hàng</h2>
                    <button onclick="closeOrderModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div id="orderDetails">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showOrderDetails(orderId) {
    // Hiển thị loading
    document.getElementById('orderDetails').innerHTML = `
        <div class="text-center py-8">
            <i class="fas fa-spinner fa-spin text-4xl text-blue-600"></i>
            <p class="mt-4 text-gray-600">Đang tải thông tin...</p>
        </div>
    `;
    
    document.getElementById('orderModal').classList.remove('hidden');
    
    // Gọi AJAX để lấy chi tiết đơn hàng
    fetch(`api/order-details.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayOrderDetails(data.order);
            } else {
                document.getElementById('orderDetails').innerHTML = `
                    <div class="text-center py-8 text-red-600">
                        <i class="fas fa-exclamation-triangle text-4xl mb-3"></i>
                        <p>${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('orderDetails').innerHTML = `
                <div class="text-center py-8 text-red-600">
                    <i class="fas fa-exclamation-triangle text-4xl mb-3"></i>
                    <p>Lỗi khi tải thông tin đơn hàng</p>
                </div>
            `;
        });
}

function displayOrderDetails(order) {
    const osIcon = order.os_name && order.os_name.toLowerCase().includes('windows') ? 'fab fa-windows text-blue-600' : 'fab fa-linux text-orange-600';
    
    document.getElementById('orderDetails').innerHTML = `
        <div class="space-y-6">
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-3">Thông tin đơn hàng</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Mã đơn hàng:</span>
                        <div class="font-medium">${order.order_code}</div>
                    </div>
                    <div>
                        <span class="text-gray-500">Trạng thái:</span>
                        <div class="font-medium">${getOrderStatusText(order.status)}</div>
                    </div>
                    <div>
                        <span class="text-gray-500">Ngày đặt:</span>
                        <div class="font-medium">${formatDate(order.created_at)}</div>
                    </div>
                    <div>
                        <span class="text-gray-500">Tổng thanh toán:</span>
                        <div class="font-medium text-blue-600">${formatMoney(order.total_amount)}</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-3">Thông tin dịch vụ</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Gói dịch vụ:</span>
                        <div class="font-medium">${order.package_name || 'N/A'}</div>
                    </div>
                    <div>
                        <span class="text-gray-500">Hệ điều hành:</span>
                        <div class="font-medium">
                            <i class="${osIcon} mr-1"></i>
                            ${order.os_name || 'N/A'} ${order.os_version || ''}
                        </div>
                    </div>
                    <div>
                        <span class="text-gray-500">Chu kỳ:</span>
                        <div class="font-medium">${order.billing_cycle} tháng</div>
                    </div>
                    <div>
                        <span class="text-gray-500">IP Address:</span>
                        <div class="font-medium font-mono">${order.ip_address || 'Chưa cấp phát'}</div>
                    </div>
                </div>
            </div>
            
            ${order.notes ? `
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 mb-3">Ghi chú</h3>
                    <div class="text-sm text-gray-700">${order.notes}</div>
                </div>
            ` : ''}
        </div>
    `;
}

function closeOrderModal() {
    document.getElementById('orderModal').classList.add('hidden');
}

function getOrderStatusText(status) {
    const statusMap = {
        'pending': 'Chờ xử lý',
        'paid': 'Đã thanh toán',
        'processing': 'Đang xử lý',
        'active': 'Đã kích hoạt',
        'expired': 'Hết hạn',
        'cancelled': 'Đã hủy'
    };
    return statusMap[status] || status;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
}

function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
}
</script>

<?php include 'includes/footer.php'; ?>