<?php
require_once '../config.php';
require_once '../database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

if (isset($_GET['action']) && $_GET['action'] == 'update') {
    require_once '../index.php';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['activate_order'])) {
    $order_id = $_POST['order_id'];
    $ip_address = cleanInput($_POST['ip_address']);
    $username = cleanInput($_POST['username']);
    $password = cleanInput($_POST['password']);
    
    $errors = [];
    
    if (empty($ip_address)) {
        $errors[] = 'Vui lòng nhập IP Address';
    }
    
    if (empty($username)) {
        $errors[] = 'Vui lòng nhập username';
    }
    
    if (empty($password)) {
        $errors[] = 'Vui lòng nhập password';
    }
    
    if (empty($errors)) {
        $updateData = [
            'ip_address' => $ip_address,
            'username' => $username,
            'password' => $password,
            'status' => 'active'
        ];
        
        if (updateOrder($order_id, $updateData)) {
            $_SESSION['success_message'] = 'Kích hoạt VPS thành công!';
            redirect('orders.php');
        } else {
            $errors[] = 'Kích hoạt thất bại. Vui lòng thử lại.';
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $order_id = $_GET['id'];
    deleteData('vps_orders', 'id = ?', [$order_id]);
    $_SESSION['success_message'] = 'Xóa đơn hàng thành công!';
    redirect('orders.php');
}

$filter = $_GET['filter'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$whereClause = '';
$params = [];

switch($filter) {
    case 'pending':
        $whereClause = 'WHERE vo.status = ?';
        $params = ['pending'];
        break;
    case 'active':
        $whereClause = 'WHERE vo.status = ?';
        $params = ['active'];
        break;
    case 'expired':
        $whereClause = 'WHERE vo.status = ?';
        $params = ['expired'];
        break;
}

$sql = "SELECT vo.*, u.username, u.full_name, u.email, u.phone, vp.name as package_name, os.name as os_name 
        FROM vps_orders vo 
        LEFT JOIN users u ON vo.user_id = u.id 
        LEFT JOIN vps_packages vp ON vo.package_id = vp.id 
        LEFT JOIN operating_systems os ON vo.os_id = os.id 
        $whereClause 
        ORDER BY vo.created_at DESC 
        LIMIT $limit OFFSET $offset";

$orders = fetchAll($sql, $params);

$countSql = "SELECT COUNT(*) as total FROM vps_orders vo $whereClause";
$totalResult = fetchOne($countSql, $params);
$totalOrders = $totalResult['total'];
$totalPages = ceil($totalOrders / $limit);

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

ob_start();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold">
                        <i class="fas fa-cog mr-2"></i>Admin Panel
                    </h1>
                </div>
                
                <div class="flex items-center space-x-6">
                    <a href="../index.php" class="hover:text-cyan-300 transition">
                        <i class="fas fa-home mr-1"></i>Trang chủ
                    </a>
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-user-circle text-xl"></i>
                        <span><?= $_SESSION['username'] ?></span>
                    </div>
                    <a href="../logout.php" class="hover:text-cyan-300 transition">
                        <i class="fas fa-sign-out-alt mr-1"></i>Đăng xuất
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg min-h-screen">
            <nav class="p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="index.php" class="flex items-center space-x-3 text-gray-700 hover:bg-gray-100 p-3 rounded-lg transition">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="users.php" class="flex items-center space-x-3 text-gray-700 hover:bg-gray-100 p-3 rounded-lg transition">
                            <i class="fas fa-users"></i>
                            <span>Quản lý người dùng</span>
                        </a>
                    </li>
                    <li>
                        <a href="orders.php" class="flex items-center space-x-3 text-cyan-600 bg-cyan-50 p-3 rounded-lg">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Quản lý đơn hàng</span>
                        </a>
                    </li>
                    <li>
                        <a href="deposits.php" class="flex items-center space-x-3 text-gray-700 hover:bg-gray-100 p-3 rounded-lg transition">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Quản lý nạp tiền</span>
                        </a>
                    </li>
                    <li>
                        <a href="packages.php" class="flex items-center space-x-3 text-gray-700 hover:bg-gray-100 p-3 rounded-lg transition">
                            <i class="fas fa-box"></i>
                            <span>Quản lý gói VPS</span>
                        </a>
                    </li>
                    <li>
                        <a href="renewals.php" class="flex items-center space-x-3 text-gray-700 hover:bg-gray-100 p-3 rounded-lg transition">
                            <i class="fas fa-redo"></i>
                            <span>Lịch sử gia hạn</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php" class="flex items-center space-x-3 text-gray-700 hover:bg-gray-100 p-3 rounded-lg transition">
                            <i class="fas fa-cog"></i>
                            <span>Cài đặt</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Quản lý đơn hàng</h1>
                <p class="text-gray-600">Quản lý tất cả đơn hàng VPS của khách hàng</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                <?= htmlspecialchars($success_message) ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lọc theo trạng thái</label>
                        <select onchange="window.location.href='?filter='+this.value" 
                                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500">
                            <option value="">Tất cả</option>
                            <option value="pending" <?= $filter == 'pending' ? 'selected' : '' ?>>Chờ kích hoạt</option>
                            <option value="active" <?= $filter == 'active' ? 'selected' : '' ?>>Đang hoạt động</option>
                            <option value="expired" <?= $filter == 'expired' ? 'selected' : '' ?>>Hết hạn</option>
                        </select>
                    </div>
                    
                    <div class="flex-1 text-right">
                        <span class="text-sm text-gray-600">
                            Hiển thị <?= count($orders) ?> / <?= $totalOrders ?> đơn hàng
                        </span>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã đơn</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Khách hàng</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gói VPS</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">HĐH</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Giá</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày tạo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-shopping-cart text-4xl mb-3"></i>
                                        <p>Không có đơn hàng nào</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #<?= $order['id'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($order['full_name']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($order['username']) ?></div>
                                            <div class="text-xs text-gray-400"><?= htmlspecialchars($order['email']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($order['package_name']) ?>
                                            <div class="text-xs text-gray-500"><?= $order['billing_cycle'] ?> tháng</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($order['os_name']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= formatPrice($order['total_price']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                                <?php
                                                switch($order['status']) {
                                                    case 'active':
                                                        echo 'bg-green-100 text-green-800';
                                                        break;
                                                    case 'pending':
                                                        echo 'bg-yellow-100 text-yellow-800';
                                                        break;
                                                    case 'expired':
                                                        echo 'bg-red-100 text-red-800';
                                                        break;
                                                    case 'cancelled':
                                                        echo 'bg-gray-100 text-gray-800';
                                                        break;
                                                    default:
                                                        echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php
                                                switch($order['status']) {
                                                    case 'active':
                                                        echo 'Đang hoạt động';
                                                        break;
                                                    case 'pending':
                                                        echo 'Chờ kích hoạt';
                                                        break;
                                                    case 'expired':
                                                        echo 'Hết hạn';
                                                        break;
                                                    case 'cancelled':
                                                        echo 'Đã hủy';
                                                        break;
                                                    default:
                                                        echo $order['status'];
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= formatDate($order['created_at']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <?php if ($order['status'] == 'pending'): ?>
                                                    <button onclick="showActivateModal(<?= $order['id'] ?>)" 
                                                            class="text-green-600 hover:text-green-900">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button onclick="showOrderDetails(<?= htmlspecialchars(json_encode($order)) ?>)" 
                                                        class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <button onclick="confirmDelete(<?= $order['id'] ?>)" 
                                                        class="text-red-600 hover:text-red-900">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>&filter=<?= $filter ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Trước
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?>&filter=<?= $filter ?>" 
                                   class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Tiếp
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Hiển thị <span class="font-medium"><?= ($offset + 1) ?></span> đến 
                                    <span class="font-medium"><?= min($offset + $limit, $totalOrders) ?></span> của 
                                    <span class="font-medium"><?= $totalOrders ?></span> kết quả
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?= $page - 1 ?>&filter=<?= $filter ?>" 
                                           class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <a href="?page=<?= $i ?>&filter=<?= $filter ?>" 
                                           class="relative inline-flex items-center px-4 py-2 border text-sm font-medium
                                               <?= $i == $page 
                                                   ? 'z-10 bg-cyan-50 border-cyan-500 text-cyan-600' 
                                                   : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <a href="?page=<?= $page + 1 ?>&filter=<?= $filter ?>" 
                                           class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Activate Modal -->
    <div id="activateModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-md w-full">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Kích hoạt VPS</h3>
                    <button onclick="closeActivateModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="order_id" id="activate_order_id">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">IP Address *</label>
                            <input type="text" name="ip_address" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500"
                                   placeholder="192.168.1.100">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                            <input type="text" name="username" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500"
                                   placeholder="root">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                            <input type="text" name="password" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500"
                                   placeholder="Nhập password">
                        </div>
                    </div>
                    
                    <div class="mt-6 flex space-x-3">
                        <button type="submit" name="activate_order" 
                                class="flex-1 bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg font-semibold transition">
                            <i class="fas fa-check mr-2"></i>Kích hoạt
                        </button>
                        <button type="button" onclick="closeActivateModal()" 
                                class="flex-1 border border-gray-300 hover:bg-gray-50 text-gray-700 py-2 px-4 rounded-lg font-semibold transition">
                            Hủy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Chi tiết đơn hàng</h3>
                    <button onclick="closeDetailsModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div id="orderDetailsContent"></div>
                
                <div class="mt-6">
                    <button onclick="closeDetailsModal()" 
                            class="w-full border border-gray-300 hover:bg-gray-50 text-gray-700 py-2 px-4 rounded-lg font-semibold transition">
                        Đóng
                    </button>
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

        function showOrderDetails(order) {
            document.getElementById('orderDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Thông tin khách hàng</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Họ tên:</span>
                                    <span class="font-medium">${order.full_name}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Username:</span>
                                    <span class="font-medium">${order.username}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Email:</span>
                                    <span class="font-medium">${order.email}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Điện thoại:</span>
                                    <span class="font-medium">${order.phone || 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Thông tin đơn hàng</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Mã đơn:</span>
                                    <span class="font-medium">#${order.id}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Gói VPS:</span>
                                    <span class="font-medium">${order.package_name}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Hệ điều hành:</span>
                                    <span class="font-medium">${order.os_name}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Chu kỳ:</span>
                                    <span class="font-medium">${order.billing_cycle} tháng</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Giá:</span>
                                    <span class="font-medium text-cyan-600">${formatPrice(order.total_price)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    ${order.ip_address ? `
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 mb-3">Thông tin VPS</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm text-gray-500">IP Address:</span>
                                <p class="font-mono font-medium">${order.ip_address}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">Username:</span>
                                <p class="font-mono font-medium">${order.username || 'root'}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">Password:</span>
                                <p class="font-mono font-medium">${order.password || 'N/A'}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">Trạng thái:</span>
                                <p class="font-medium">${order.status}</p>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                    
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2">Thời gian</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Ngày tạo:</span>
                                <span class="font-medium">${formatDate(order.created_at)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Ngày mua:</span>
                                <span class="font-medium">${order.purchase_date || 'N/A'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Ngày hết hạn:</span>
                                <span class="font-medium">${order.expiry_date || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('detailsModal').classList.remove('hidden');
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').classList.add('hidden');
        }

        function confirmDelete(orderId) {
            if (confirm('Bạn có chắc chắn muốn xóa đơn hàng này?')) {
                window.location.href = '?action=delete&id=' + orderId;
            }
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('vi-VN').format(price) + ' VNĐ';
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'});
        }

        // Close modals when clicking outside
        document.getElementById('activateModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeActivateModal();
            }
        });

        document.getElementById('detailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDetailsModal();
            }
        });
    </script>
</body>
</html>

<?php
$content = ob_get_clean();
echo $content;
?>