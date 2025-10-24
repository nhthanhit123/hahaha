<?php
require_once 'config.php';
require_once 'database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php?redirect=order.php?id=' . ($_GET['id'] ?? ''));
}

$package_id = $_GET['id'] ?? 0;
$package = getVpsPackage($package_id);

if (!$package) {
    redirect('packages.php');
}

$operating_systems = fetchOperatingSystems();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $os_id = $_POST['os_id'];
    $billing_cycle = $_POST['billing_cycle'];
    
    $errors = [];
    
    if (empty($os_id)) {
        $errors[] = 'Vui lòng chọn hệ điều hành';
    }
    
    if (empty($billing_cycle)) {
        $errors[] = 'Vui lòng chọn chu kỳ thanh toán';
    }
    
    $os = getOperatingSystem($os_id);
    
    if ($os && $os['min_ram_gb'] > 0) {
        $ram_gb = (int)filter_var($package['ram'], FILTER_SANITIZE_NUMBER_INT);
        if ($ram_gb < $os['min_ram_gb']) {
            $errors[] = "Hệ điều hành {$os['name']} yêu cầu tối thiểu {$os['min_ram_gb']}GB RAM";
        }
    }
    
    $total_price = calculatePrice($package['selling_price'], $billing_cycle);
    
    $user = getUser($_SESSION['user_id']);
    
    if ($user['balance'] < $total_price) {
        $errors[] = 'Số dư không đủ. Vui lòng nạp thêm tiền.';
    }
    
    if (empty($errors)) {
        $orderData = [
            'user_id' => $_SESSION['user_id'],
            'package_id' => $package_id,
            'os_id' => $os_id,
            'billing_cycle' => $billing_cycle,
            'price' => $package['selling_price'],
            'total_price' => $total_price,
            'status' => 'pending',
            'purchase_date' => date('Y-m-d'),
            'expiry_date' => date('Y-m-d', strtotime("+$billing_cycle months"))
        ];
        
        if (createVpsOrder($orderData)) {
            updateUserBalance($_SESSION['user_id'], -$total_price);
            
            $order = getOrder($orderData['user_id'], $orderData['user_id']);
            sendOrderNotification($order, $user, $package, $os);
            
            $_SESSION['success_message'] = 'Đặt hàng thành công! VPS của bạn sẽ được kích hoạt sớm.';
            redirect('services.php');
        } else {
            $errors[] = 'Đặt hàng thất bại. Vui lòng thử lại.';
        }
    }
}

$page_title = 'Đặt mua VPS - ' . SITE_NAME;

ob_start();
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <ol class="flex items-center space-x-2 text-sm">
                    <li><a href="index.php" class="text-gray-500 hover:text-gray-700">Trang chủ</a></li>
                    <li><span class="text-gray-400">/</span></li>
                    <li><a href="packages.php" class="text-gray-500 hover:text-gray-700">Gói VPS</a></li>
                    <li><span class="text-gray-400">/</span></li>
                    <li class="text-gray-900 font-medium">Đặt mua</li>
                </ol>
            </nav>
            
            <!-- Package Info -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Đặt mua VPS</h1>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">
                            <?= htmlspecialchars($package['name']) ?>
                        </h2>
                        
                        <div class="space-y-3">
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-microchip w-6 text-cyan-500"></i>
                                <span class="ml-3">CPU: <?= htmlspecialchars($package['cpu']) ?></span>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-memory w-6 text-cyan-500"></i>
                                <span class="ml-3">RAM: <?= htmlspecialchars($package['ram']) ?></span>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-hdd w-6 text-cyan-500"></i>
                                <span class="ml-3">Ổ cứng: <?= htmlspecialchars($package['storage']) ?></span>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-globe w-6 text-cyan-500"></i>
                                <span class="ml-3">Vị trí: <?= htmlspecialchars($package['location']) ?></span>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-wifi w-6 text-cyan-500"></i>
                                <span class="ml-3">Băng thông: <?= htmlspecialchars($package['bandwidth']) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-cyan-50 to-blue-50 rounded-lg p-6">
                        <div class="text-center">
                            <div class="text-sm text-gray-600 mb-2">Giá gốc</div>
                            <div class="text-2xl text-gray-400 line-through mb-4">
                                <?= formatPrice($package['original_price']) ?>
                            </div>
                            
                            <div class="text-sm text-gray-600 mb-2">Giá bán (đã +5%)</div>
                            <div class="text-4xl font-bold text-cyan-600 mb-4">
                                <?= formatPrice($package['selling_price']) ?>
                                <span class="text-lg text-gray-500">/tháng</span>
                            </div>
                            
                            <div class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm inline-block">
                                <i class="fas fa-check-circle mr-1"></i>Còn hàng
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Form -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Thông tin đặt hàng</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Lỗi đặt hàng</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc pl-5 space-y-1">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="orderForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Hệ điều hành *
                            </label>
                            <select name="os_id" id="os_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500">
                                <option value="">-- Chọn hệ điều hành --</option>
                                <?php foreach ($operating_systems as $os): ?>
                                    <option value="<?= $os['id'] ?>" 
                                            data-min-ram="<?= $os['min_ram_gb'] ?>">
                                        <?= htmlspecialchars($os['name']) ?>
                                        <?php if ($os['min_ram_gb'] > 1): ?>
                                            (Yêu cầu tối thiểu <?= $os['min_ram_gb'] ?>GB RAM)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Chu kỳ thanh toán *
                            </label>
                            <select name="billing_cycle" id="billing_cycle" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500">
                                <option value="">-- Chọn chu kỳ --</option>
                                <option value="1">1 tháng - <?= formatPrice($package['selling_price']) ?></option>
                                <option value="6">6 tháng - <?= formatPrice(calculatePrice($package['selling_price'], 6)) ?> (giảm 5%)</option>
                                <option value="12">12 tháng - <?= formatPrice(calculatePrice($package['selling_price'], 12)) ?> (giảm 17%)</option>
                                <option value="24">24 tháng - <?= formatPrice(calculatePrice($package['selling_price'], 24)) ?> (giảm 25%)</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Price Calculation -->
                    <div class="mt-8 p-6 bg-gradient-to-r from-cyan-50 to-blue-50 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Tổng tiền thanh toán</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between text-gray-600">
                                <span>Giá gói VPS:</span>
                                <span id="package_price"><?= formatPrice($package['selling_price']) ?>/tháng</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Chu kỳ:</span>
                                <span id="cycle_text">-</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Chiết khấu:</span>
                                <span id="discount" class="text-green-600">-</span>
                            </div>
                            <div class="border-t pt-2 mt-2">
                                <div class="flex justify-between text-lg font-bold text-gray-900">
                                    <span>Tổng cộng:</span>
                                    <span id="total_price" class="text-cyan-600">0 VNĐ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Balance -->
                    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-wallet text-yellow-600 mr-2"></i>
                                <span class="text-sm font-medium text-yellow-800">
                                    Số dư tài khoản:
                                </span>
                            </div>
                            <span class="text-lg font-bold text-yellow-800">
                                <?= formatPrice($_SESSION['balance']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mt-8">
                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 text-white py-3 px-6 rounded-lg font-semibold transition">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            Xác nhận đặt hàng
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const packagePrice = <?= $package['selling_price'] ?>;
const packageRam = <?= (int)filter_var($package['ram'], FILTER_SANITIZE_NUMBER_INT) ?>;

document.getElementById('billing_cycle').addEventListener('change', updatePrice);
document.getElementById('os_id').addEventListener('change', checkRamRequirement);

function updatePrice() {
    const cycle = document.getElementById('billing_cycle').value;
    const totalPriceEl = document.getElementById('total_price');
    const cycleTextEl = document.getElementById('cycle_text');
    const discountEl = document.getElementById('discount');
    
    if (!cycle) {
        totalPriceEl.textContent = '0 VNĐ';
        cycleTextEl.textContent = '-';
        discountEl.textContent = '-';
        return;
    }
    
    let totalPrice = packagePrice;
    let cycleText = '';
    let discount = 0;
    
    switch(cycle) {
        case '1':
            cycleText = '1 tháng';
            break;
        case '6':
            totalPrice = packagePrice * 5.5;
            cycleText = '6 tháng';
            discount = '5%';
            break;
        case '12':
            totalPrice = packagePrice * 10;
            cycleText = '12 tháng';
            discount = '17%';
            break;
        case '24':
            totalPrice = packagePrice * 18;
            cycleText = '24 tháng';
            discount = '25%';
            break;
    }
    
    totalPriceEl.textContent = formatPrice(totalPrice);
    cycleTextEl.textContent = cycleText;
    discountEl.textContent = discount || '0%';
}

function checkRamRequirement() {
    const osSelect = document.getElementById('os_id');
    const selectedOption = osSelect.options[osSelect.selectedIndex];
    const minRam = parseInt(selectedOption.dataset.minRam) || 0;
    
    if (minRam > 0 && packageRam < minRam) {
        osSelect.setCustomValidity(`Hệ điều hành này yêu cầu tối thiểu ${minRam}GB RAM`);
    } else {
        osSelect.setCustomValidity('');
    }
}

function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price) + ' VNĐ';
}

updatePrice();
</script>

<?php
$content = ob_get_clean();
include 'includes/header.php';
?>