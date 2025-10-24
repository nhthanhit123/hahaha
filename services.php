<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = 'Vui lòng đăng nhập để tiếp tục';
    $_SESSION['flash_type'] = 'warning';
    redirect('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

// Lấy danh sách dịch vụ VPS
$services = [];
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$total = 0;

try {
    // Lấy tổng số dịch vụ
    $total = getRow("SELECT COUNT(*) as total FROM services WHERE user_id = ?", [$_SESSION['user_id']])['total'];
    
    // Lấy danh sách dịch vụ
    $services = getRows("
        SELECT s.*, p.name as package_name, p.cpu_cores, p.ram_gb, p.storage_gb, p.bandwidth_gb,
               o.order_code, o.billing_cycle, o.total_amount
        FROM services s
        LEFT JOIN packages p ON s.package_id = p.id
        LEFT JOIN orders o ON s.order_id = o.id
        WHERE s.user_id = ?
        ORDER BY s.created_at DESC
        LIMIT ? OFFSET ?
    ", [$_SESSION['user_id'], $limit, $offset]);
    
} catch(Exception $e) {
    $services = [];
    $total = 0;
}

$page_title = "Quản lý dịch vụ VPS - " . SITE_NAME;
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Dịch vụ VPS của tôi</h1>
            <a href="packages.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                <i class="fas fa-plus mr-2"></i>Mua VPS mới
            </a>
        </div>

        <?php if (empty($services)): ?>
            <div class="bg-white rounded-lg shadow-lg p-12 text-center">
                <i class="fas fa-server text-6xl text-gray-300 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-600 mb-2">Bạn chưa có dịch vụ VPS nào</h2>
                <p class="text-gray-500 mb-6">Hãy mua VPS đầu tiên để trải nghiệm dịch vụ của chúng tôi</p>
                <a href="packages.php" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                    <i class="fas fa-shopping-cart mr-2"></i>Mua VPS ngay
                </a>
            </div>
        <?php else: ?>
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <?php
                $stats = [
                    'total' => count($services),
                    'active' => 0,
                    'pending' => 0,
                    'expired' => 0
                ];
                
                foreach ($services as $service) {
                    if ($service['status'] == 'active') $stats['active']++;
                    elseif ($service['status'] == 'pending') $stats['pending']++;
                    elseif ($service['status'] == 'expired') $stats['expired']++;
                }
                ?>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 rounded-full">
                            <i class="fas fa-server text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Tổng dịch vụ</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-full">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Đang hoạt động</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $stats['active']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-100 rounded-full">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Chờ xử lý</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $stats['pending']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-100 rounded-full">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Hết hạn</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $stats['expired']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Services List -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900">Danh sách dịch vụ</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thông tin</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cấu hình</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP & Truy cập</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời hạn</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($services as $service): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($service['package_name']); ?></div>
                                        <div class="text-sm text-gray-500">
                                            <?php 
                                            $os_icon = 'fab fa-linux text-orange-600'; // 默认Linux
                                            ?>
                                            <i class="<?php echo $os_icon; ?> mr-1"></i>
                                            <?php echo htmlspecialchars($service['os'] ?: 'N/A'); ?>
                                        </div>
                                        <div class="text-xs text-gray-400">Mã đơn: <?php echo $service['order_code']; ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <div><i class="fas fa-microchip text-blue-500 mr-1"></i><?php echo $service['cpu_cores']; ?> Core</div>
                                            <div><i class="fas fa-memory text-green-500 mr-1"></i><?php echo $service['ram_gb']; ?> GB RAM</div>
                                            <div><i class="fas fa-hdd text-purple-500 mr-1"></i><?php echo $service['storage_gb']; ?> GB SSD</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($service['status'] == 'active' && $service['ip_address']): ?>
                                            <div class="text-sm">
                                                <div class="font-mono text-gray-900"><?php echo $service['ip_address']; ?></div>
                                                <div class="text-gray-500">
                                                    User: <span class="font-mono"><?php echo $service['username'] ?: 'N/A'; ?></span>
                                                </div>
                                                <div class="text-gray-500">
                                                    Pass: <span class="font-mono"><?php echo $service['password'] ? '******' : 'N/A'; ?></span>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-sm text-gray-500">Chưa cấp phát</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm">
                                            <?php if ($service['next_due_date']): ?>
                                                <div class="text-gray-900"><?php echo formatDate($service['next_due_date']); ?></div>
                                                <?php 
                                                $days_left = floor((strtotime($service['next_due_date']) - time()) / (60 * 60 * 24));
                                                if ($days_left <= 7 && $days_left > 0) {
                                                    echo '<div class="text-yellow-600 text-xs">Còn ' . $days_left . ' ngày</div>';
                                                } elseif ($days_left <= 0) {
                                                    echo '<div class="text-red-600 text-xs">Đã hết hạn</div>';
                                                }
                                                ?>
                                            <?php else: ?>
                                                <div class="text-gray-500">N/A</div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $status_colors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'active' => 'bg-green-100 text-green-800',
                                            'suspended' => 'bg-red-100 text-red-800',
                                            'terminated' => 'bg-gray-100 text-gray-800'
                                        ];
                                        $status_text = [
                                            'pending' => 'Chờ xử lý',
                                            'active' => 'Hoạt động',
                                            'suspended' => 'Tạm dừng',
                                            'terminated' => 'Đã终止'
                                        ];
                                        ?>
                                        <span class="px-2 py-1 text-xs rounded-full <?php echo $status_colors[$service['status']]; ?>">
                                            <?php echo $status_text[$service['status']]; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <?php if ($service['status'] == 'active'): ?>
                                                <button onclick="showServiceDetails(<?php echo $service['id']; ?>)" 
                                                        class="text-blue-600 hover:text-blue-800 text-sm">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($service['next_due_date'] && strtotime($service['next_due_date']) > time()): ?>
                                                    <a href="renew.php?service_id=<?php echo $service['id']; ?>" 
                                                       class="text-green-600 hover:text-green-800 text-sm">
                                                        <i class="fas fa-sync"></i>
                                                    </a>
                                                <?php endif; ?>
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
                        <?php echo paginate($total, $page, $limit, 'services.php'); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Service Details Modal -->
<div id="serviceModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Chi tiết dịch vụ VPS</h2>
                    <button onclick="closeServiceModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div id="serviceDetails">
                    <!-- Service details will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showServiceDetails(serviceId) {
    // Hiển thị loading
    document.getElementById('serviceDetails').innerHTML = `
        <div class="text-center py-8">
            <i class="fas fa-spinner fa-spin text-4xl text-blue-600"></i>
            <p class="mt-4 text-gray-600">Đang tải thông tin...</p>
        </div>
    `;
    
    document.getElementById('serviceModal').classList.remove('hidden');
    
    // Gọi AJAX để lấy chi tiết dịch vụ
    fetch(`api/service-details.php?id=${serviceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayServiceDetails(data.service);
            } else {
                document.getElementById('serviceDetails').innerHTML = `
                    <div class="text-center py-8 text-red-600">
                        <i class="fas fa-exclamation-triangle text-4xl mb-3"></i>
                        <p>${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('serviceDetails').innerHTML = `
                <div class="text-center py-8 text-red-600">
                    <i class="fas fa-exclamation-triangle text-4xl mb-3"></i>
                    <p>Lỗi khi tải thông tin dịch vụ</p>
                </div>
            `;
        });
}

function displayServiceDetails(service) {
    const osIcon = service.os_type === 'windows' ? 'fab fa-windows text-blue-600' : 'fab fa-linux text-orange-600';
    
    document.getElementById('serviceDetails').innerHTML = `
        <div class="space-y-6">
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-3">Thông tin cơ bản</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Gói dịch vụ:</span>
                        <div class="font-medium">${service.package_name}</div>
                    </div>
                    <div>
                        <span class="text-gray-500">Mã đơn hàng:</span>
                        <div class="font-medium">${service.order_code}</div>
                    </div>
                    <div>
                        <span class="text-gray-500">Hệ điều hành:</span>
                        <div class="font-medium">
                            <i class="${osIcon} mr-1"></i>
                            ${service.os_name} ${service.os_version || ''}
                        </div>
                    </div>
                    <div>
                        <span class="text-gray-500">Ngày tạo:</span>
                        <div class="font-medium">${formatDate(service.created_at)}</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-3">Cấu hình</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">CPU:</span>
                        <div class="font-medium">${service.cpu_cores} Core</div>
                    </div>
                    <div>
                        <span class="text-gray-500">RAM:</span>
                        <div class="font-medium">${service.ram_gb} GB</div>
                    </div>
                    <div>
                        <span class="text-gray-500">Ổ cứng:</span>
                        <div class="font-medium">${service.storage_gb} GB SSD</div>
                    </div>
                    <div>
                        <span class="text-gray-500">Băng thông:</span>
                        <div class="font-medium">${service.bandwidth_gb} GB/tháng</div>
                    </div>
                </div>
            </div>
            
            ${service.status === 'active' && service.ip_address ? `
                <div class="bg-blue-50 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 mb-3">Thông tin truy cập</h3>
                    <div class="space-y-3 text-sm">
                        <div>
                            <span class="text-gray-500">Địa chỉ IP:</span>
                            <div class="font-mono font-medium bg-white px-2 py-1 rounded">${service.ip_address}</div>
                        </div>
                        ${service.username ? `
                            <div>
                                <span class="text-gray-500">Tên đăng nhập:</span>
                                <div class="font-mono font-medium bg-white px-2 py-1 rounded">${service.username}</div>
                            </div>
                        ` : ''}
                        ${service.password ? `
                            <div>
                                <span class="text-gray-500">Mật khẩu:</span>
                                <div class="flex items-center space-x-2">
                                    <div class="font-mono font-medium bg-white px-2 py-1 rounded" id="passwordField">••••••••</div>
                                    <button onclick="togglePassword()" class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-eye" id="passwordToggle"></i>
                                    </button>
                                </div>
                            </div>
                        ` : ''}
                        <div>
                            <span class="text-gray-500">Port SSH:</span>
                            <div class="font-medium">${service.ssh_port || 22}</div>
                        </div>
                    </div>
                </div>
            ` : ''}
            
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-3">Thời hạn</h3>
                <div class="text-sm">
                    <div>
                        <span class="text-gray-500">Ngày hết hạn:</span>
                        <div class="font-medium">${service.expiry_date ? formatDate(service.expiry_date) : 'N/A'}</div>
                    </div>
                    ${service.expiry_date ? `
                        <div class="mt-2">
                            ${getDaysRemaining(service.expiry_date)}
                        </div>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

function closeServiceModal() {
    document.getElementById('serviceModal').classList.add('hidden');
}

function togglePassword() {
    const passwordField = document.getElementById('passwordField');
    const passwordToggle = document.getElementById('passwordToggle');
    const actualPassword = passwordField.dataset.actualPassword || '••••••••';
    
    if (passwordField.textContent === '••••••••') {
        passwordField.textContent = actualPassword;
        passwordToggle.className = 'fas fa-eye-slash';
    } else {
        passwordField.textContent = '••••••••';
        passwordToggle.className = 'fas fa-eye';
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
}

function getDaysRemaining(expiryDate) {
    const now = new Date();
    const expiry = new Date(expiryDate);
    const diffTime = expiry - now;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays > 0) {
        if (diffDays <= 7) {
            return `<span class="text-yellow-600">Còn ${diffDays} ngày</span>`;
        } else {
            return `<span class="text-green-600">Còn ${diffDays} ngày</span>`;
        }
    } else {
        return '<span class="text-red-600">Đã hết hạn</span>';
    }
}
</script>

<?php include 'includes/footer.php'; ?>