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
    $package_id = intval($_POST['package_id'] ?? 0);
    
    try {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $cpu_cores = intval($_POST['cpu_cores'] ?? 1);
                $ram_gb = intval($_POST['ram_gb'] ?? 1);
                $storage_gb = intval($_POST['storage_gb'] ?? 20);
                $bandwidth_gb = intval($_POST['bandwidth_gb'] ?? 1000);
                $price_monthly = floatval($_POST['price_monthly'] ?? 0);
                $package_type = $_POST['package_type'] ?? 'standard';
                
                if (!empty($name) && $price_monthly > 0) {
                    $stmt = $pdo->prepare("
                        INSERT INTO vps_packages (name, description, cpu_cores, ram_gb, storage_gb, bandwidth_gb, price_monthly, package_type) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $description, $cpu_cores, $ram_gb, $storage_gb, $bandwidth_gb, $price_monthly, $package_type]);
                    
                    $_SESSION['flash_message'] = 'Đã thêm gói dịch vụ mới';
                    $_SESSION['flash_type'] = 'success';
                }
                break;
                
            case 'edit':
                $stmt = $pdo->prepare("SELECT * FROM vps_packages WHERE id = ?");
                $stmt->execute([$package_id]);
                $package = $stmt->fetch();
                
                if ($package) {
                    $name = trim($_POST['name'] ?? '');
                    $description = trim($_POST['description'] ?? '');
                    $cpu_cores = intval($_POST['cpu_cores'] ?? 1);
                    $ram_gb = intval($_POST['ram_gb'] ?? 1);
                    $storage_gb = intval($_POST['storage_gb'] ?? 20);
                    $bandwidth_gb = intval($_POST['bandwidth_gb'] ?? 1000);
                    $price_monthly = floatval($_POST['price_monthly'] ?? 0);
                    $package_type = $_POST['package_type'] ?? 'standard';
                    $is_active = isset($_POST['is_active']) ? 1 : 0;
                    
                    if (!empty($name) && $price_monthly > 0) {
                        $stmt = $pdo->prepare("
                            UPDATE vps_packages 
                            SET name = ?, description = ?, cpu_cores = ?, ram_gb = ?, storage_gb = ?, bandwidth_gb = ?, price_monthly = ?, package_type = ?, is_active = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$name, $description, $cpu_cores, $ram_gb, $storage_gb, $bandwidth_gb, $price_monthly, $package_type, $is_active, $package_id]);
                        
                        $_SESSION['flash_message'] = 'Đã cập nhật gói dịch vụ';
                        $_SESSION['flash_type'] = 'success';
                    }
                }
                break;
                
            case 'toggle_status':
                $stmt = $pdo->prepare("SELECT is_active FROM vps_packages WHERE id = ?");
                $stmt->execute([$package_id]);
                $package = $stmt->fetch();
                
                if ($package) {
                    $new_status = $package['is_active'] ? 0 : 1;
                    $stmt = $pdo->prepare("UPDATE vps_packages SET is_active = ? WHERE id = ?");
                    $stmt->execute([$new_status, $package_id]);
                    
                    $_SESSION['flash_message'] = 'Đã ' . ($new_status ? 'kích hoạt' : 'vô hiệu hóa') . ' gói dịch vụ';
                    $_SESSION['flash_type'] = 'success';
                }
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM vps_packages WHERE id = ?");
                $stmt->execute([$package_id]);
                
                $_SESSION['flash_message'] = 'Đã xóa gói dịch vụ';
                $_SESSION['flash_type'] = 'success';
                break;
        }
    } catch(PDOException $e) {
        $_SESSION['flash_message'] = 'Lỗi: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'error';
    }
    
    redirect('packages.php');
}

// Lấy danh sách gói dịch vụ
try {
    $stmt = $pdo->prepare("SELECT * FROM vps_packages ORDER BY price_monthly ASC");
    $stmt->execute();
    $packages = $stmt->fetchAll();
} catch(PDOException $e) {
    $packages = [];
}

$page_title = "Quản lý gói dịch vụ - Admin";
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
                <a href="deposits.php" class="py-3 px-4 hover:bg-gray-600 transition">
                    <i class="fas fa-credit-card mr-2"></i>Nạp tiền
                </a>
                <a href="packages.php" class="py-3 px-4 hover:bg-gray-600 transition border-b-2 border-blue-500">
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
            <h1 class="text-2xl font-bold text-gray-900">Quản lý gói dịch vụ</h1>
            <button onclick="showAddModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                <i class="fas fa-plus mr-2"></i>Thêm gói mới
            </button>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên gói</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cấu hình</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Giá</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loại</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($packages as $package): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium">#<?php echo $package['id']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($package['name']); ?></div>
                                    <?php if ($package['description']): ?>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars(substr($package['description'], 0, 50)) . '...'; ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs text-gray-900">
                                        <div><i class="fas fa-microchip text-blue-500 mr-1"></i><?php echo $package['cpu_cores']; ?> Core</div>
                                        <div><i class="fas fa-memory text-green-500 mr-1"></i><?php echo $package['ram_gb']; ?> GB RAM</div>
                                        <div><i class="fas fa-hdd text-purple-500 mr-1"></i><?php echo $package['storage_gb']; ?> GB SSD</div>
                                        <div><i class="fas fa-tachometer-alt text-orange-500 mr-1"></i><?php echo $package['bandwidth_gb']; ?> GB</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-blue-600"><?php echo formatMoney($package['price_monthly']); ?>/tháng</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">
                                        <?php echo ucfirst($package['package_type']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $package['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $package['is_active'] ? 'Hoạt động' : 'Vô hiệu'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <button onclick="showEditModal(<?php echo $package['id']; ?>)" class="text-blue-600 hover:text-blue-800 text-sm" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <form method="POST" class="inline" onsubmit="return confirm('Bạn có chắc muốn thay đổi trạng thái gói dịch vụ này?')">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                                            <button type="submit" class="text-<?php echo $package['is_active'] ? 'yellow' : 'green'; ?>-600 hover:text-<?php echo $package['is_active'] ? 'yellow' : 'green'; ?>-800 text-sm" 
                                                    title="<?php echo $package['is_active'] ? 'Vô hiệu hóa' : 'Kích hoạt'; ?>">
                                                <i class="fas fa-<?php echo $package['is_active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa gói dịch vụ này?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm" title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Package Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold">Thêm gói dịch vụ mới</h3>
                    <button onclick="closeAddModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tên gói dịch vụ *</label>
                            <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Loại gói</label>
                            <select name="package_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="standard">Standard</option>
                                <option value="cheap">Giá rẻ</option>
                                <option value="business">Business</option>
                                <option value="enterprise">Enterprise</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mô tả</label>
                        <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">CPU Core</label>
                            <input type="number" name="cpu_cores" value="1" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">RAM (GB)</label>
                            <input type="number" name="ram_gb" value="1" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Storage (GB)</label>
                            <input type="number" name="storage_gb" value="20" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Bandwidth (GB)</label>
                            <input type="number" name="bandwidth_gb" value="1000" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Giá tháng (VNĐ) *</label>
                        <input type="number" name="price_monthly" step="0.01" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="button" onclick="closeAddModal()" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg mr-3">
                            Hủy
                        </button>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg">
                            Thêm gói dịch vụ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Package Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold">Cập nhật gói dịch vụ</h3>
                    <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="package_id" id="edit_package_id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tên gói dịch vụ *</label>
                            <input type="text" name="name" id="edit_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Loại gói</label>
                            <select name="package_type" id="edit_package_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="standard">Standard</option>
                                <option value="cheap">Giá rẻ</option>
                                <option value="business">Business</option>
                                <option value="enterprise">Enterprise</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mô tả</label>
                        <textarea name="description" id="edit_description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">CPU Core</label>
                            <input type="number" name="cpu_cores" id="edit_cpu_cores" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">RAM (GB)</label>
                            <input type="number" name="ram_gb" id="edit_ram_gb" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Storage (GB)</label>
                            <input type="number" name="storage_gb" id="edit_storage_gb" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Bandwidth (GB)</label>
                            <input type="number" name="bandwidth_gb" id="edit_bandwidth_gb" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Giá tháng (VNĐ) *</label>
                            <input type="number" name="price_monthly" id="edit_price_monthly" step="0.01" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="edit_is_active" class="mr-2">
                            <label for="edit_is_active" class="text-sm font-medium text-gray-700">Kích hoạt</label>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="button" onclick="closeEditModal()" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg mr-3">
                            Hủy
                        </button>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg">
                            Cập nhật gói dịch vụ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const packages = <?php echo json_encode($packages); ?>;

function showAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

function showEditModal(packageId) {
    const package = packages.find(p => p.id == packageId);
    if (!package) return;
    
    document.getElementById('edit_package_id').value = packageId;
    document.getElementById('edit_name').value = package.name;
    document.getElementById('edit_package_type').value = package.package_type;
    document.getElementById('edit_description').value = package.description || '';
    document.getElementById('edit_cpu_cores').value = package.cpu_cores;
    document.getElementById('edit_ram_gb').value = package.ram_gb;
    document.getElementById('edit_storage_gb').value = package.storage_gb;
    document.getElementById('edit_bandwidth_gb').value = package.bandwidth_gb;
    document.getElementById('edit_price_monthly').value = package.price_monthly;
    document.getElementById('edit_is_active').checked = package.is_active;
    
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>

<?php include 'includes/footer.php'; ?>