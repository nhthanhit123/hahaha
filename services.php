<?php
require_once 'config.php';
require_once 'database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php?redirect=services.php');
}

$orders = getUserOrders($_SESSION['user_id']);

if (isset($_GET['action']) && $_GET['action'] == 'renew' && isset($_GET['id'])) {
    $order_id = $_GET['id'];
    $order = getOrder($order_id, $_SESSION['user_id']);
    
    if (!$order) {
        redirect('services.php');
    }
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $months = $_POST['months'];
        $renewal_price = calculatePrice($order['price'], $months);
        
        $user = getUser($_SESSION['user_id']);
        
        if ($user['balance'] < $renewal_price) {
            $error = 'Số dư không đủ. Vui lòng nạp thêm tiền.';
        } else {
            $renewalData = [
                'order_id' => $order_id,
                'user_id' => $_SESSION['user_id'],
                'months' => $months,
                'price' => $renewal_price,
                'old_expiry_date' => $order['expiry_date'],
                'new_expiry_date' => date('Y-m-d', strtotime($order['expiry_date'] . " +$months months")),
                'status' => 'completed'
            ];
            
            if (createRenewal($renewalData)) {
                updateUserBalance($_SESSION['user_id'], -$renewal_price);
                
                updateOrder($order_id, [
                    'expiry_date' => $renewalData['new_expiry_date'],
                    'status' => 'active'
                ]);
                
                $_SESSION['success_message'] = 'Gia hạn thành công!';
                sendRenewalNotification($renewalData, $order, $user);
                redirect('services.php');
            } else {
                $error = 'Gia hạn thất bại. Vui lòng thử lại.';
            }
        }
    }
}

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$page_title = 'Quản lý dịch vụ - ' . SITE_NAME;

ob_start();
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Quản lý dịch vụ VPS</h1>
            <p class="text-gray-600">Quản lý và gia hạn các dịch vụ VPS của bạn</p>
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
        
        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">
                            <?= htmlspecialchars($error) ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- User Balance Card -->
        <div class="bg-gradient-to-r from-cyan-500 to-blue-500 rounded-lg shadow-lg p-6 mb-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold mb-1">Số dư tài khoản</h3>
                    <p class="text-3xl font-bold"><?= formatPrice($_SESSION['balance']) ?></p>
                </div>
                <div class="text-right">
                    <a href="deposit.php" class="bg-white text-cyan-600 hover:bg-gray-100 px-6 py-2 rounded-lg font-semibold transition">
                        <i class="fas fa-plus-circle mr-2"></i>Nạp tiền
                    </a>
                </div>
            </div>
        </div>
        
        <?php if (empty($orders)): ?>
            <!-- Empty State -->
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <i class="fas fa-server text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">Chưa có dịch vụ nào</h3>
                <p class="text-gray-500 mb-6">Bạn chưa mua gói VPS nào. Hãy chọn gói phù hợp với nhu cầu của bạn.</p>
                <a href="packages.php" class="bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 text-white py-2 px-6 rounded-lg font-semibold transition">
                    <i class="fas fa-shopping-cart mr-2"></i>Xem gói VPS
                </a>
            </div>
        <?php else: ?>
            <!-- Services List -->
            <div class="space-y-6">
                <?php foreach ($orders as $order): ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <div class="p-6">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center mb-4">
                                        <h3 class="text-xl font-bold text-gray-900 mr-3">
                                            <?= htmlspecialchars($order['package_name']) ?>
                                        </h3>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold
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
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                                        <div>
                                            <span class="text-sm text-gray-500">Hệ điều hành:</span>
                                            <p class="font-medium text-gray-900"><?= htmlspecialchars($order['os_name']) ?></p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-500">Chu kỳ:</span>
                                            <p class="font-medium text-gray-900"><?= $order['billing_cycle'] ?> tháng</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-500">Giá:</span>
                                            <p class="font-medium text-gray-900"><?= formatPrice($order['total_price']) ?></p>
                                        </div>
                                    </div>
                                    
                                    <?php if ($order['status'] == 'active' && $order['ip_address']): ?>
                                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                            <h4 class="font-semibold text-gray-800 mb-3">Thông tin đăng nhập</h4>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <span class="text-sm text-gray-500">IP Address:</span>
                                                    <p class="font-mono font-medium text-gray-900"><?= htmlspecialchars($order['ip_address']) ?></p>
                                                </div>
                                                <div>
                                                    <span class="text-sm text-gray-500">Username:</span>
                                                    <p class="font-mono font-medium text-gray-900"><?= htmlspecialchars($order['username'] ?? 'root') ?></p>
                                                </div>
                                                <div>
                                                    <span class="text-sm text-gray-500">Password:</span>
                                                    <div class="flex items-center">
                                                        <p class="font-mono font-medium text-gray-900 mr-2" id="password-<?= $order['id'] ?>">
                                                            <?= $order['password'] ? str_repeat('*', strlen($order['password'])) : 'N/A' ?>
                                                        </p>
                                                        <?php if ($order['password']): ?>
                                                            <button onclick="togglePassword(<?= $order['id'] ?>, '<?= htmlspecialchars($order['password']) ?>')" 
                                                                    class="text-cyan-500 hover:text-cyan-600">
                                                                <i class="fas fa-eye" id="eye-<?= $order['id'] ?>"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="text-sm text-gray-500">Ngày hết hạn:</span>
                                                    <p class="font-medium text-gray-900"><?= formatDate($order['expiry_date']) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex items-center text-sm text-gray-500">
                                        <i class="fas fa-calendar mr-2"></i>
                                        Mua ngày: <?= formatDate($order['purchase_date']) ?>
                                        <?php if ($order['expiry_date']): ?>
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-clock mr-2"></i>
                                            Hết hạn: <?= formatDate($order['expiry_date']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="flex flex-col space-y-2 mt-4 lg:mt-0 lg:ml-6">
                                    <?php if ($order['status'] == 'active'): ?>
                                        <button onclick="showRenewalModal(<?= $order['id'] ?>, <?= $order['price'] ?>, '<?= htmlspecialchars($order['package_name']) ?>', '<?= $order['expiry_date'] ?>')" 
                                                class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg font-semibold transition">
                                            <i class="fas fa-redo mr-2"></i>Gia hạn
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button onclick="showOrderDetails(<?= $order['id'] ?>)" 
                                            class="border border-gray-300 hover:bg-gray-50 text-gray-700 py-2 px-4 rounded-lg font-semibold transition">
                                        <i class="fas fa-info-circle mr-2"></i>Chi tiết
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Renewal Modal -->
<div id="renewalModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-900">Gia hạn VPS</h3>
                <button onclick="closeRenewalModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" id="renewalForm">
                <input type="hidden" name="order_id" id="renewal_order_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Gói dịch vụ
                    </label>
                    <p class="font-medium text-gray-900" id="renewal_package_name"></p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Hết hạn hiện tại
                    </label>
                    <p class="font-medium text-gray-900" id="renewal_current_expiry"></p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Chọn thời gian gia hạn *
                    </label>
                    <select name="months" id="renewal_months" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">-- Chọn thời gian --</option>
                        <option value="1">1 tháng</option>
                        <option value="6">6 tháng (giảm 5%)</option>
                        <option value="12">12 tháng (giảm 17%)</option>
                        <option value="24">24 tháng (giảm 25%)</option>
                    </select>
                </div>
                
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Tổng tiền:</span>
                        <span class="text-xl font-bold text-cyan-600" id="renewal_total_price">0 VNĐ</span>
                    </div>
                </div>
                
                <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-wallet text-yellow-600 mr-2"></i>
                        <span class="text-sm font-medium text-yellow-800">
                            Số dư: <?= formatPrice($_SESSION['balance']) ?>
                        </span>
                    </div>
                </div>
                
                <div class="flex space-x-3">
                    <button type="submit" 
                            class="flex-1 bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 text-white py-2 px-4 rounded-lg font-semibold transition">
                        <i class="fas fa-check mr-2"></i>Xác nhận gia hạn
                    </button>
                    <button type="button" onclick="closeRenewalModal()" 
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
let currentPrice = 0;

function showRenewalModal(orderId, price, packageName, currentExpiry) {
    currentPrice = price;
    document.getElementById('renewal_order_id').value = orderId;
    document.getElementById('renewal_package_name').textContent = packageName;
    document.getElementById('renewal_current_expiry').textContent = formatDate(currentExpiry);
    document.getElementById('renewal_months').value = '';
    document.getElementById('renewal_total_price').textContent = '0 VNĐ';
    document.getElementById('renewalModal').classList.remove('hidden');
}

function closeRenewalModal() {
    document.getElementById('renewalModal').classList.add('hidden');
}

function showOrderDetails(orderId) {
    // Fetch order details via AJAX or use existing data
    const orders = <?= json_encode($orders) ?>;
    const order = orders.find(o => o.id == orderId);
    
    if (!order) return;
    
    document.getElementById('orderDetailsContent').innerHTML = `
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h4 class="font-semibold text-gray-800 mb-2">Thông tin dịch vụ</h4>
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
                            <span class="text-gray-600">Trạng thái:</span>
                            <span class="font-medium">${getStatusText(order.status)}</span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-semibold text-gray-800 mb-2">Thông tin thanh toán</h4>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Giá gói:</span>
                            <span class="font-medium">${formatPrice(order.price)}/tháng</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Chu kỳ:</span>
                            <span class="font-medium">${order.billing_cycle} tháng</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tổng tiền:</span>
                            <span class="font-medium text-cyan-600">${formatPrice(order.total_price)}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Ngày mua:</span>
                            <span class="font-medium">${formatDate(order.purchase_date)}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            ${order.ip_address ? `
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="font-semibold text-gray-800 mb-3">Thông tin đăng nhập</h4>
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
                        <span class="text-sm text-gray-500">Hết hạn:</span>
                        <p class="font-medium">${formatDate(order.expiry_date)}</p>
                    </div>
                </div>
            </div>
            ` : ''}
            
            <div>
                <h4 class="font-semibold text-gray-800 mb-2">Lịch sử gia hạn</h4>
                <div id="renewalHistory_${orderId}">
                    <p class="text-gray-500 text-sm">Đang tải...</p>
                </div>
            </div>
        </div>
    `;
    
    // Load renewal history
    loadRenewalHistory(orderId);
    
    document.getElementById('detailsModal').classList.remove('hidden');
}

function closeDetailsModal() {
    document.getElementById('detailsModal').classList.add('hidden');
}

function loadRenewalHistory(orderId) {
    // This would typically be an AJAX call to fetch renewal history
    // For now, we'll show a placeholder
    setTimeout(() => {
        document.getElementById(`renewalHistory_${orderId}`).innerHTML = 
            '<p class="text-gray-500 text-sm">Chưa có lịch sử gia hạn nào.</p>';
    }, 500);
}

function getStatusText(status) {
    switch(status) {
        case 'active': return 'Đang hoạt động';
        case 'pending': return 'Chờ kích hoạt';
        case 'expired': return 'Hết hạn';
        case 'cancelled': return 'Đã hủy';
        default: return status;
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'});
}

function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price) + ' VNĐ';
}

function calculatePrice(basePrice, months) {
    const multipliers = {
        '1': 1,
        '6': 5.5,
        '12': 10,
        '24': 18
    };
    
    return basePrice * (multipliers[months] || 1);
}

function togglePassword(orderId, password) {
    const passwordEl = document.getElementById(`password-${orderId}`);
    const eyeEl = document.getElementById(`eye-${orderId}`);
    
    if (passwordEl.textContent.includes('*')) {
        passwordEl.textContent = password;
        eyeEl.classList.remove('fa-eye');
        eyeEl.classList.add('fa-eye-slash');
    } else {
        passwordEl.textContent = '*'.repeat(password.length);
        eyeEl.classList.remove('fa-eye-slash');
        eyeEl.classList.add('fa-eye');
    }
}

// Update renewal price when months change
document.getElementById('renewal_months')?.addEventListener('change', function() {
    const months = this.value;
    if (months) {
        const totalPrice = calculatePrice(currentPrice, months);
        document.getElementById('renewal_total_price').textContent = formatPrice(totalPrice);
    } else {
        document.getElementById('renewal_total_price').textContent = '0 VNĐ';
    }
});

// Close modals when clicking outside
document.getElementById('renewalModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRenewalModal();
    }
});

document.getElementById('detailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDetailsModal();
    }
});
</script>

<?php
$content = ob_get_clean();
include 'includes/header.php';
?>