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
    $user_id = intval($_POST['user_id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $_SESSION['flash_message'] = 'Không tìm thấy người dùng';
            $_SESSION['flash_type'] = 'error';
        } else {
            switch ($_POST['action']) {
                case 'toggle_status':
                    $new_status = $user['status'] == 'active' ? 'inactive' : 'active';
                    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                    $stmt->execute([$new_status, $user_id]);
                    
                    $_SESSION['flash_message'] = 'Đã ' . ($new_status == 'active' ? 'kích hoạt' : 'vô hiệu hóa') . ' người dùng';
                    $_SESSION['flash_type'] = 'success';
                    break;
                    
                case 'update_balance':
                    $amount = floatval($_POST['amount'] ?? 0);
                    $reason = trim($_POST['reason'] ?? '');
                    
                    if ($amount != 0 && !empty($reason)) {
                        $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                        $stmt->execute([$amount, $user_id]);
                        
                        // Log hoạt động
                        logActivity($_SESSION['user_id'], 'update_user_balance', "Cập nhật số dư user {$user['username']}: " . ($amount > 0 ? '+' : '') . formatMoney($amount));
                        
                        $_SESSION['flash_message'] = 'Đã cập nhật số dư người dùng';
                        $_SESSION['flash_type'] = 'success';
                    } else {
                        $_SESSION['flash_message'] = 'Vui lòng nhập số tiền và lý do';
                        $_SESSION['flash_type'] = 'error';
                    }
                    break;
            }
        }
    } catch(PDOException $e) {
        $_SESSION['flash_message'] = 'Lỗi: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'error';
    }
    
    redirect('users.php');
}

// Lấy danh sách người dùng
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

try {
    $where_clause = '';
    $params = [];
    
    if (!empty($status_filter)) {
        $where_clause .= "WHERE status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($search)) {
        $where_clause .= ($where_clause ? ' AND ' : 'WHERE') . " (username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    // Lấy tổng số người dùng
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users $where_clause");
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Lấy danh sách người dùng
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        $where_clause
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $users = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $users = [];
    $total = 0;
}

$page_title = "Quản lý người dùng - Admin";
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
                <a href="users.php" class="py-3 px-4 hover:bg-gray-600 transition border-b-2 border-blue-500">
                    <i class="fas fa-users mr-2"></i>Người dùng
                </a>
                <a href="services.php" class="py-3 px-4 hover:bg-gray-600 transition">
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
            <h1 class="text-2xl font-bold text-gray-900">Quản lý người dùng</h1>
            
            <!-- Search and Filter -->
            <div class="flex items-center space-x-4">
                <form method="GET" class="flex items-center space-x-2">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Tìm kiếm..." class="px-3 py-2 border border-gray-300 rounded-lg">
                    <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                        <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Không hoạt động</option>
                        <option value="banned" <?php echo $status_filter == 'banned' ? 'selected' : ''; ?>>Bị khóa</option>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thông tin</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Liên hệ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số dư</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vai trò</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày tạo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium">#<?php echo $user['id']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $user['full_name']; ?></div>
                                    <div class="text-sm text-gray-500">@<?php echo $user['username']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo $user['email']; ?></div>
                                    <?php if ($user['phone']): ?>
                                        <div class="text-sm text-gray-500"><?php echo $user['phone']; ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-blue-600"><?php echo formatMoney($user['balance']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $role_colors = [
                                        'admin' => 'bg-purple-100 text-purple-800',
                                        'user' => 'bg-gray-100 text-gray-800'
                                    ];
                                    ?>
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $role_colors[$user['role']]; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $status_colors = [
                                        'active' => 'bg-green-100 text-green-800',
                                        'inactive' => 'bg-yellow-100 text-yellow-800',
                                        'banned' => 'bg-red-100 text-red-800'
                                    ];
                                    $status_text = [
                                        'active' => 'Hoạt động',
                                        'inactive' => 'Không hoạt động',
                                        'banned' => 'Bị khóa'
                                    ];
                                    ?>
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $status_colors[$user['status']]; ?>">
                                        <?php echo $status_text[$user['status']]; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo formatDate($user['created_at']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Bạn có chắc muốn thay đổi trạng thái người dùng này?')">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="text-<?php echo $user['status'] == 'active' ? 'yellow' : 'green'; ?>-600 hover:text-<?php echo $user['status'] == 'active' ? 'yellow' : 'green'; ?>-800" 
                                                        title="<?php echo $user['status'] == 'active' ? 'Vô hiệu hóa' : 'Kích hoạt'; ?>">
                                                    <i class="fas fa-<?php echo $user['status'] == 'active' ? 'ban' : 'check'; ?>"></i>
                                                </button>
                                            </form>
                                            
                                            <button onclick="showBalanceModal(<?php echo $user['id']; ?>, '<?php echo $user['username']; ?>', <?php echo $user['balance']; ?>)" 
                                                    class="text-blue-600 hover:text-blue-800" title="Cập nhật số dư">
                                                <i class="fas fa-wallet"></i>
                                            </button>
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
                    <?php echo paginate($total, $page, $limit, 'users.php'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Balance Modal -->
<div id="balanceModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Cập nhật số dư</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_balance">
                    <input type="hidden" name="user_id" id="balance_user_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Người dùng</label>
                        <div class="font-medium" id="balance_username"></div>
                        <div class="text-sm text-gray-500">Số dư hiện tại: <span id="balance_current"></span></div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Số tiền (+ để cộng, - để trừ)</label>
                        <input type="number" name="amount" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lý do</label>
                        <textarea name="reason" rows="2" required class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Nhập lý do cập nhật số dư..."></textarea>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button type="button" onclick="closeBalanceModal()" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg">
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
function showBalanceModal(userId, username, currentBalance) {
    document.getElementById('balance_user_id').value = userId;
    document.getElementById('balance_username').textContent = username;
    document.getElementById('balance_current').textContent = formatMoney(currentBalance);
    document.getElementById('balanceModal').classList.remove('hidden');
}

function closeBalanceModal() {
    document.getElementById('balanceModal').classList.add('hidden');
}

function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
}
</script>

<?php include 'includes/footer.php'; ?>