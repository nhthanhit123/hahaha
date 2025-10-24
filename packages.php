<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// Lấy danh sách gói VPS
$packages = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM vps_packages WHERE is_active = 1 ORDER BY price_monthly ASC");
    $stmt->execute();
    $packages = $stmt->fetchAll();
} catch(PDOException $e) {
    // Nếu chưa có gói nào, thử lấy từ API
    $packages = getVPSPackages();
    
    // Lưu vào database
    if (!empty($packages)) {
        try {
            foreach ($packages as $package) {
                $stmt = $pdo->prepare("
                    INSERT INTO vps_packages (name, description, cpu_cores, ram_gb, storage_gb, bandwidth_gb, price_monthly, original_price) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $package['name'],
                    implode(' | ', $package['specs']),
                    1, // Default values
                    1,
                    20,
                    1000,
                    $package['price'],
                    $package['price'] / 1.05
                ]);
            }
            
            // Lấy lại từ database
            $stmt = $pdo->prepare("SELECT * FROM vps_packages WHERE is_active = 1 ORDER BY price_monthly ASC");
            $stmt->execute();
            $packages = $stmt->fetchAll();
        } catch(PDOException $e) {
            // Bỏ qua lỗi
        }
    }
}

// Lấy danh sách hệ điều hành
$operating_systems = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM operating_systems WHERE is_active = 1 ORDER BY sort_order ASC");
    $stmt->execute();
    $operating_systems = $stmt->fetchAll();
} catch(PDOException $e) {
    $operating_systems = [];
}

$page_title = "Gói dịch vụ VPS - " . SITE_NAME;
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white py-16">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Gói dịch vụ VPS</h1>
            <p class="text-xl text-blue-100 mb-8">Chọn gói phù hợp với nhu cầu của bạn</p>
            <div class="flex justify-center space-x-4">
                <button class="bg-white text-blue-600 px-6 py-2 rounded-lg font-semibold hover:bg-blue-50 transition" onclick="filterPackages('all')">
                    Tất cả
                </button>
                <button class="bg-blue-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-400 transition" onclick="filterPackages('cheap')">
                    Giá rẻ
                </button>
                <button class="bg-blue-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-400 transition" onclick="filterPackages('business')">
                    Doanh nghiệp
                </button>
                <button class="bg-blue-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-400 transition" onclick="filterPackages('enterprise')">
                Cao cấp
                </button>
            </div>
        </div>
    </div>

    <!-- Packages Grid -->
    <div class="container mx-auto px-4 py-12">
        <?php if (empty($packages)): ?>
            <div class="text-center py-16">
                <i class="fas fa-server text-6xl text-gray-300 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-600 mb-2">Chưa có gói dịch vụ nào</h2>
                <p class="text-gray-500">Vui lòng quay lại sau hoặc liên hệ admin để thêm gói dịch vụ.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="packagesGrid">
                <?php foreach ($packages as $index => $package): ?>
                    <?php
                    $isPopular = $index === 1; // Gói thứ 2 là popular
                    $packageClass = $isPopular ? 'border-2 border-blue-500 transform scale-105' : 'border border-gray-200';
                    ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden card-hover <?php echo $packageClass; ?>" data-category="<?php echo $package['package_type']; ?>">
                        <?php if ($isPopular): ?>
                            <div class="bg-blue-500 text-white text-center py-2 text-sm font-semibold">
                                Phổ biến nhất
                            </div>
                        <?php endif; ?>
                        
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($package['name']); ?></h3>
                            
                            <div class="mb-6">
                                <div class="flex items-baseline">
                                    <span class="text-3xl font-bold text-blue-600"><?php echo number_format($package['price_monthly'], 0, ',', '.'); ?></span>
                                    <span class="text-gray-500 ml-1">đ/tháng</span>
                                </div>
                                <?php if ($package['original_price'] && $package['original_price'] < $package['price_monthly']): ?>
                                    <div class="text-sm text-gray-500 line-through">
                                        Giá gốc: <?php echo formatMoney($package['original_price']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="space-y-3 mb-6">
                                <div class="flex items-center text-gray-700">
                                    <i class="fas fa-microchip w-5 text-blue-500"></i>
                                    <span class="ml-3"><?php echo $package['cpu_cores']; ?> CPU Core</span>
                                </div>
                                <div class="flex items-center text-gray-700">
                                    <i class="fas fa-memory w-5 text-blue-500"></i>
                                    <span class="ml-3"><?php echo $package['ram_gb']; ?> GB RAM</span>
                                </div>
                                <div class="flex items-center text-gray-700">
                                    <i class="fas fa-hdd w-5 text-blue-500"></i>
                                    <span class="ml-3"><?php echo $package['storage_gb']; ?> GB SSD</span>
                                </div>
                                <div class="flex items-center text-gray-700">
                                    <i class="fas fa-tachometer-alt w-5 text-blue-500"></i>
                                    <span class="ml-3"><?php echo $package['bandwidth_gb']; ?> GB Bandwidth</span>
                                </div>
                            </div>
                            
                            <?php if ($package['description']): ?>
                                <div class="text-sm text-gray-600 mb-6">
                                    <?php echo htmlspecialchars($package['description']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="space-y-2">
                                <button onclick="selectPackage(<?php echo $package['id']; ?>, '<?php echo htmlspecialchars($package['name']); ?>', <?php echo $package['price_monthly']; ?>)" 
                                        class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 transition">
                                    <i class="fas fa-shopping-cart mr-2"></i>Chọn gói này
                                </button>
                                
                                <button onclick="showDetails(<?php echo $package['id']; ?>)" 
                                        class="w-full bg-gray-100 text-gray-700 py-2 px-4 rounded-lg font-semibold hover:bg-gray-200 transition">
                                    <i class="fas fa-info-circle mr-2"></i>Chi tiết
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Features Section -->
    <div class="bg-white py-16">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Tại sao chọn chúng tôi?</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-rocket text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Tốc độ cao</h3>
                    <p class="text-gray-600">CPU thế hệ mới, SSD NVMe siêu tốc</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shield-alt text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Bảo mật</h3>
                    <p class="text-gray-600">Firewall mạnh mẽ, bảo vệ DDoS</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-headset text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Hỗ trợ 24/7</h3>
                    <p class="text-gray-600">Đội ngũ kỹ thuật chuyên nghiệp</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-dollar-sign text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Giá tốt nhất</h3>
                    <p class="text-gray-600">Cạnh tranh nhất thị trường</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- OS Selection Modal -->
<div id="osModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Chọn hệ điều hành</h2>
                    <button onclick="closeOSModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div class="mb-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-sm text-blue-800">
                            <strong>Gói đã chọn:</strong> <span id="selectedPackageName"></span> - 
                            <span id="selectedPackagePrice"></span>/tháng
                        </p>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">
                    <?php foreach ($operating_systems as $os): ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="os_id" value="<?php echo $os['id']; ?>" class="hidden peer" required>
                            <div class="border-2 border-gray-200 rounded-lg p-4 text-center hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition">
                                <div class="text-3xl mb-2">
                                    <?php if ($os['os_type'] == 'windows'): ?>
                                        <i class="fab fa-windows text-blue-600"></i>
                                    <?php else: ?>
                                        <i class="fab fa-linux text-orange-600"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="font-medium text-sm"><?php echo htmlspecialchars($os['name']); ?></div>
                                <?php if ($os['version']): ?>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($os['version']); ?></div>
                                <?php endif; ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chu kỳ thanh toán</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <label class="cursor-pointer">
                            <input type="radio" name="billing_cycle" value="1" class="hidden peer" checked>
                            <div class="border-2 border-gray-200 rounded-lg p-3 text-center hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition">
                                <div class="font-semibold">1 tháng</div>
                                <div class="text-sm text-gray-500" id="price_1"></div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="billing_cycle" value="6" class="hidden peer">
                            <div class="border-2 border-gray-200 rounded-lg p-3 text-center hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition">
                                <div class="font-semibold">6 tháng</div>
                                <div class="text-sm text-gray-500" id="price_6"></div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="billing_cycle" value="12" class="hidden peer">
                            <div class="border-2 border-gray-200 rounded-lg p-3 text-center hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition">
                                <div class="font-semibold">12 tháng</div>
                                <div class="text-sm text-gray-500" id="price_12"></div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="billing_cycle" value="24" class="hidden peer">
                            <div class="border-2 border-gray-200 rounded-lg p-3 text-center hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition">
                                <div class="font-semibold">24 tháng</div>
                                <div class="text-sm text-gray-500" id="price_24"></div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <form id="orderForm" method="POST" action="order.php">
                    <input type="hidden" name="package_id" id="order_package_id">
                    <input type="hidden" name="os_id" id="order_os_id">
                    <input type="hidden" name="billing_cycle" id="order_billing_cycle">
                    <input type="hidden" name="total_amount" id="order_total_amount">
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú (tùy chọn)</label>
                        <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nhập ghi chú nếu có..."></textarea>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold">Tổng thanh toán:</span>
                            <span class="text-2xl font-bold text-blue-600" id="totalAmount">0đ</span>
                        </div>
                    </div>
                    
                    <div class="flex space-x-4">
                        <button type="button" onclick="closeOSModal()" class="flex-1 bg-gray-200 text-gray-700 py-3 px-4 rounded-lg font-semibold hover:bg-gray-300 transition">
                            Hủy
                        </button>
                        <button type="submit" class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 transition">
                            <i class="fas fa-check mr-2"></i>Đặt hàng ngay
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let selectedPackage = {
    id: null,
    name: '',
    price: 0
};

function filterPackages(category) {
    const packages = document.querySelectorAll('#packagesGrid > div');
    packages.forEach(pkg => {
        if (category === 'all' || pkg.dataset.category === category) {
            pkg.style.display = 'block';
        } else {
            pkg.style.display = 'none';
        }
    });
    
    // Update button styles
    const buttons = document.querySelectorAll('button[onclick^="filterPackages"]');
    buttons.forEach(btn => {
        if (btn.getAttribute('onclick').includes(category)) {
            btn.className = 'bg-white text-blue-600 px-6 py-2 rounded-lg font-semibold hover:bg-blue-50 transition';
        } else {
            btn.className = 'bg-blue-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-400 transition';
        }
    });
}

function selectPackage(packageId, packageName, price) {
    selectedPackage = {
        id: packageId,
        name: packageName,
        price: price
    };
    
    document.getElementById('selectedPackageName').textContent = packageName;
    document.getElementById('selectedPackagePrice').textContent = formatMoney(price);
    document.getElementById('order_package_id').value = packageId;
    
    // Update billing cycle prices
    updateBillingPrices();
    
    // Show modal
    document.getElementById('osModal').classList.remove('hidden');
}

function closeOSModal() {
    document.getElementById('osModal').classList.add('hidden');
}

function updateBillingPrices() {
    const price = selectedPackage.price;
    document.getElementById('price_1').textContent = formatMoney(price);
    document.getElementById('price_6').textContent = formatMoney(price * 6);
    document.getElementById('price_12').textContent = formatMoney(price * 12);
    document.getElementById('price_24').textContent = formatMoney(price * 24);
    
    updateTotalAmount();
}

function updateTotalAmount() {
    const billingCycle = document.querySelector('input[name="billing_cycle"]:checked').value;
    const total = selectedPackage.price * parseInt(billingCycle);
    document.getElementById('totalAmount').textContent = formatMoney(total);
    document.getElementById('order_total_amount').value = total;
}

function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
}

function showDetails(packageId) {
    // Could implement a details modal or redirect to details page
    alert('Tính năng đang được phát triển');
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Billing cycle change
    document.querySelectorAll('input[name="billing_cycle"]').forEach(input => {
        input.addEventListener('change', updateTotalAmount);
    });
    
    // OS selection
    document.querySelectorAll('input[name="os_id"]').forEach(input => {
        input.addEventListener('change', function() {
            document.getElementById('order_os_id').value = this.value;
        });
    });
    
    // Billing cycle selection
    document.querySelectorAll('input[name="billing_cycle"]').forEach(input => {
        input.addEventListener('change', function() {
            document.getElementById('order_billing_cycle').value = this.value;
        });
    });
    
    // Form submission
    document.getElementById('orderForm').addEventListener('submit', function(e) {
        const osSelected = document.querySelector('input[name="os_id"]:checked');
        if (!osSelected) {
            e.preventDefault();
            alert('Vui lòng chọn hệ điều hành');
            return;
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>