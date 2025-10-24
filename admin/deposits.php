<?php
require_once '../config.php';
require_once '../database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_deposit'])) {
    $deposit_id = $_POST['deposit_id'];
    $deposit = fetchOne("SELECT * FROM deposits WHERE id = ?", [$deposit_id]);
    
    if ($deposit && $deposit['status'] == 'pending') {
        updateDeposit($deposit_id, ['status' => 'completed']);
        updateUserBalance($deposit['user_id'], $deposit['amount']);
        
        $_SESSION['success_message'] = 'Duyệt nạp tiền thành công!';
        redirect('deposits.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reject_deposit'])) {
    $deposit_id = $_POST['deposit_id'];
    $deposit = fetchOne("SELECT * FROM deposits WHERE id = ?", [$deposit_id]);
    
    if ($deposit && $deposit['status'] == 'pending') {
        updateDeposit($deposit_id, ['status' => 'failed']);
        
        $_SESSION['success_message'] = 'Từ chối nạp tiền thành công!';
        redirect('deposits.php');
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $deposit_id = $_GET['id'];
    deleteData('deposits', 'id = ?', [$deposit_id]);
    $_SESSION['success_message'] = 'Xóa giao dịch thành công!';
    redirect('deposits.php');
}

$filter = $_GET['filter'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$whereClause = '';
$params = [];

switch($filter) {
    case 'pending':
        $whereClause = 'WHERE d.status = ?';
        $params = ['pending'];
        break;
    case 'completed':
        $whereClause = 'WHERE d.status = ?';
        $params = ['completed'];
        break;
    case 'failed':
        $whereClause = 'WHERE d.status = ?';
        $params = ['failed'];
        break;
}

$sql = "SELECT d.*, u.username, u.full_name, u.email 
        FROM deposits d 
        LEFT JOIN users u ON d.user_id = u.id 
        $whereClause 
        ORDER BY d.created_at DESC 
        LIMIT $limit OFFSET $offset";

$deposits = fetchAll($sql, $params);

$countSql = "SELECT COUNT(*) as total FROM deposits d $whereClause";
$totalResult = fetchOne($countSql, $params);
$totalDeposits = $totalResult['total'];
$totalPages = ceil($totalDeposits / $limit);

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
    <title>Quản lý nạp tiền - Admin Panel</title>
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
                        <a href="orders.php" class="flex items-center space-x-3 text-gray-700 hover:bg-gray-100 p-3 rounded-lg transition">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Quản lý đơn hàng</span>
                        </a>
                    </li>
                    <li>
                        <a href="deposits.php" class="flex items-center space-x-3 text-cyan-600 bg-cyan-50 p-3 rounded-lg">
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
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Quản lý nạp tiền</h1>
                <p class="text-gray-600">Duyệt và quản lý các yêu cầu nạp tiền của khách hàng</p>
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

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Tổng giao dịch</p>
                            <p class="text-3xl font-bold text-gray-900"><?= $totalDeposits ?></p>
                        </div>
                        <div class="text-3xl text-blue-500">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                    </div>
                </div>
                
                <?php
                $pendingCount = count(fetchAll("SELECT id FROM deposits WHERE status = 'pending'"));
                $completedCount = count(fetchAll("SELECT id FROM deposits WHERE status = 'completed'"));
                $totalAmount = fetchOne("SELECT SUM(amount) as total FROM deposits WHERE status = 'completed'")['total'] ?? 0;
                ?>
                
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Chờ duyệt</p>
                            <p class="text-3xl font-bold text-yellow-600"><?= $pendingCount ?></p>
                        </div>
                        <div class="text-3xl text-yellow-500">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Đã duyệt</p>
                            <p class="text-3xl font-bold text-green-600"><?= $completedCount ?></p>
                        </div>
                        <div class="text-3xl text-green-500">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Tổng tiền</p>
                            <p class="text-2xl font-bold text-cyan-600"><?= formatPrice($totalAmount) ?></p>
                        </div>
                        <div class="text-3xl text-cyan-500">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lọc theo trạng thái</label>
                        <select onchange="window.location.href='?filter='+this.value" 
                                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500">
                            <option value="">Tất cả</option>
                            <option value="pending" <?= $filter == 'pending' ? 'selected' : '' ?>>Chờ duyệt</option>
                            <option value="completed" <?= $filter == 'completed' ? 'selected' : '' ?>>Đã duyệt</option>
                            <option value="failed" <?= $filter == 'failed' ? 'selected' : '' ?>>Đã từ chối</option>
                        </select>
                    </div>
                    
                    <div class="flex-1 text-right">
                        <span class="text-sm text-gray-600">
                            Hiển thị <?= count($deposits) ?> / <?= $totalDeposits ?> giao dịch
                        </span>
                    </div>
                </div>
            </div>

            <!-- Deposits Table -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã GD</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Khách hàng</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngân hàng</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số tiền</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã GD</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày tạo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($deposits)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-money-bill-wave text-4xl mb-3"></i>
                                        <p>Không có giao dịch nào</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($deposits as $deposit): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #<?= $deposit['id'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($deposit['full_name']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($deposit['username']) ?></div>
                                            <div class="text-xs text-gray-400"><?= htmlspecialchars($deposit['email']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($deposit['bank_name']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-cyan-600">
                                            <?= formatPrice($deposit['amount']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($deposit['transaction_id'] ?: 'N/A') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                                <?php
                                                switch($deposit['status']) {
                                                    case 'completed':
                                                        echo 'bg-green-100 text-green-800';
                                                        break;
                                                    case 'pending':
                                                        echo 'bg-yellow-100 text-yellow-800';
                                                        break;
                                                    case 'failed':
                                                        echo 'bg-red-100 text-red-800';
                                                        break;
                                                    default:
                                                        echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php
                                                switch($deposit['status']) {
                                                    case 'completed':
                                                        echo 'Đã duyệt';
                                                        break;
                                                    case 'pending':
                                                        echo 'Chờ duyệt';
                                                        break;
                                                    case 'failed':
                                                        echo 'Đã từ chối';
                                                        break;
                                                    default:
                                                        echo $deposit['status'];
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= formatDate($deposit['created_at']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <?php if ($deposit['status'] == 'pending'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="deposit_id" value="<?= $deposit['id'] ?>">
                                                        <button type="submit" name="approve_deposit" 
                                                                class="text-green-600 hover:text-green-900"
                                                                onclick="return confirm('Duyệt yêu cầu nạp tiền này?')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="deposit_id" value="<?= $deposit['id'] ?>">
                                                        <button type="submit" name="reject_deposit" 
                                                                class="text-red-600 hover:text-red-900"
                                                                onclick="return confirm('Từ chối yêu cầu nạp tiền này?')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <button onclick="showDepositDetails(<?= htmlspecialchars(json_encode($deposit)) ?>)" 
                                                        class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <button onclick="confirmDelete(<?= $deposit['id'] ?>)" 
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
                                    <span class="font-medium"><?= min($offset + $limit, $totalDeposits) ?></span> của 
                                    <span class="font-medium"><?= $totalDeposits ?></span> kết quả
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

    <!-- Deposit Details Modal -->
    <div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-md w-full">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Chi tiết giao dịch</h3>
                    <button onclick="closeDetailsModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div id="depositDetailsContent"></div>
                
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
        function showDepositDetails(deposit) {
            document.getElementById('depositDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2">Thông tin khách hàng</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Họ tên:</span>
                                <span class="font-medium">${deposit.full_name}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Username:</span>
                                <span class="font-medium">${deposit.username}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Email:</span>
                                <span class="font-medium">${deposit.email}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2">Thông tin giao dịch</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Mã giao dịch:</span>
                                <span class="font-medium">#${deposit.id}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Ngân hàng:</span>
                                <span class="font-medium">${deposit.bank_name}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Số tiền:</span>
                                <span class="font-medium text-cyan-600">${formatPrice(deposit.amount)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Mã GD khách:</span>
                                <span class="font-medium">${deposit.transaction_id || 'N/A'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Trạng thái:</span>
                                <span class="font-medium">${deposit.status}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2">Thời gian</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Ngày tạo:</span>
                                <span class="font-medium">${formatDate(deposit.created_at)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Cập nhật:</span>
                                <span class="font-medium">${formatDate(deposit.updated_at)}</span>
                            </div>
                        </div>
                    </div>
                    
                    ${deposit.notes ? `
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2">Ghi chú</h4>
                        <p class="text-gray-600">${deposit.notes}</p>
                    </div>
                    ` : ''}
                </div>
            `;
            
            document.getElementById('detailsModal').classList.remove('hidden');
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').classList.add('hidden');
        }

        function confirmDelete(depositId) {
            if (confirm('Bạn có chắc chắn muốn xóa giao dịch này?')) {
                window.location.href = '?action=delete&id=' + depositId;
            }
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('vi-VN').format(price) + ' VNĐ';
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'});
        }

        // Close modal when clicking outside
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