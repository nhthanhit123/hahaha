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
    $deposit_id = intval($_POST['deposit_id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM deposits WHERE id = ?");
        $stmt->execute([$deposit_id]);
        $deposit = $stmt->fetch();
        
        if (!$deposit) {
            $_SESSION['flash_message'] = 'Không tìm thấy yêu cầu nạp tiền';
            $_SESSION['flash_type'] = 'error';
        } else {
            switch ($_POST['action']) {
                case 'approve':
                    // Cộng tiền cho user
                    $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                    $stmt->execute([$deposit['amount'], $deposit['user_id']]);
                    
                    // Cập nhật trạng thái deposit
                    $stmt = $pdo->prepare("
                        UPDATE deposits 
                        SET status = 'approved', approved_by = ?, approved_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$_SESSION['user_id'], $deposit_id]);
                    
                    // Lấy thông tin user để gửi thông báo
                    $stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
                    $stmt->execute([$deposit['user_id']]);
                    $user = $stmt->fetch();
                    
                    // Gửi thông báo Telegram
                    $telegram_message = "
💰 NẠP TIỀN ĐÃ DUYỆT

📋 Mã giao dịch: {$deposit['deposit_code']}
👤 Khách hàng: {$user['full_name']} ({$user['email']})
💰 Số tiền: " . formatMoney($deposit['amount']) . "
🏦 Ngân hàng: " . BANK_INFO[$deposit['bank_code']]['name'] . "
📅 Ngày duyệt: " . date('d/m/Y H:i') . "
✅ Đã cộng vào tài khoản

Admin duyệt: {$_SESSION['user_name']}
                    ";
                    
                    sendTelegram($telegram_message);
                    
                    $_SESSION['flash_message'] = 'Đã duyệt yêu cầu nạp tiền';
                    $_SESSION['flash_type'] = 'success';
                    break;
                    
                case 'reject':
                    $reason = $_POST['reason'] ?? '';
                    
                    $stmt = $pdo->prepare("
                        UPDATE deposits 
                        SET status = 'rejected', notes = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$reason, $deposit_id]);
                    
                    $_SESSION['flash_message'] = 'Đã từ chối yêu cầu nạp tiền';
                    $_SESSION['flash_type'] = 'warning';
                    break;
            }
        }
    } catch(PDOException $e) {
        $_SESSION['flash_message'] = 'Lỗi: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'error';
    }
    
    redirect('deposits.php');
}

// Lấy danh sách yêu cầu nạp tiền
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$status_filter = $_GET['status'] ?? '';

try {
    $where_clause = '';
    $params = [];
    
    if (!empty($status_filter)) {
        $where_clause = "WHERE d.status = ?";
        $params[] = $status_filter;
    }
    
    // Lấy tổng số yêu cầu
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM deposits d $where_clause");
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Lấy danh sách yêu cầu
    $stmt = $pdo->prepare("
        SELECT d.*, u.full_name, u.email, u.balance
        FROM deposits d
        LEFT JOIN users u ON d.user_id = u.id
        $where_clause
        ORDER BY d.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $deposits = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $deposits = [];
    $total = 0;
}

$page_title = "Quản lý nạp tiền - Admin";
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
                <a href="orders.php" class="py-3 px-4 hover:bg-gray-600 transition">
                    <i class="fas fa-shopping-cart mr-2"></i>Đơn hàng
                </a>
                <a href="deposits.php" class="py-3 px-4 hover:bg-gray-600 transition border-b-2 border-blue-500">
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
            <h1 class="text-2xl font-bold text-gray-900">Quản lý nạp tiền</h1>
            
            <!-- Filter -->
            <div class="flex items-center space-x-4">
                <select onchange="location.href='?status='+this.value" class="px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Chờ duyệt</option>
                    <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Đã duyệt</option>
                    <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Từ chối</option>
                </select>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã giao dịch</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số tiền</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngân hàng</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số dư hiện tại</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày yêu cầu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($deposits as $deposit): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-mono text-sm"><?php echo $deposit['deposit_code']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $deposit['full_name']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $deposit['email']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-blue-600"><?php echo formatMoney($deposit['amount']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?php 
                                        $bank = BANK_INFO[$deposit['bank_code']] ?? null;
                                        echo $bank ? $bank['name'] : $deposit['bank_code'];
                                        ?>
                                    </div>
                                    <div class="text-xs text-gray-500"><?php echo $deposit['bank_account']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium"><?php echo formatMoney($deposit['balance']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $status_colors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800'
                                    ];
                                    $status_text = [
                                        'pending' => 'Chờ duyệt',
                                        'approved' => 'Đã duyệt',
                                        'rejected' => 'Từ chối'
                                    ];
                                    ?>
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $status_colors[$deposit['status']]; ?>">
                                        <?php echo $status_text[$deposit['status']]; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo formatDate($deposit['created_at']); ?></div>
                                    <?php if ($deposit['approved_at']): ?>
                                        <div class="text-xs text-gray-500">Duyệt: <?php echo formatDate($deposit['approved_at']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <?php if ($deposit['status'] == 'pending'): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="deposit_id" value="<?php echo $deposit['id']; ?>">
                                                <button type="submit" class="text-green-600 hover:text-green-800" title="Duyệt">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            
                                            <button onclick="showRejectModal(<?php echo $deposit['id']; ?>)" class="text-red-600 hover:text-red-800" title="Từ chối">
                                                <i class="fas fa-times"></i>
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
                    <?php echo paginate($total, $page, $limit, 'deposits.php'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Từ chối yêu cầu nạp tiền</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="deposit_id" id="reject_deposit_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lý do từ chối</label>
                        <textarea name="reason" rows="3" required class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Nhập lý do từ chối..."></textarea>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button type="button" onclick="closeRejectModal()" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg">
                            Hủy
                        </button>
                        <button type="submit" class="flex-1 bg-red-600 text-white py-2 rounded-lg">
                            Từ chối
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showRejectModal(depositId) {
    document.getElementById('reject_deposit_id').value = depositId;
    document.getElementById('rejectModal').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}
</script>

<?php include 'includes/footer.php'; ?>