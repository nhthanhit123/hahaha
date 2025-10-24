<?php
require_once 'config.php';
require_once 'database.php';
require_once 'includes/functions.php';

function fetchVpsFromSource() {
    $url = 'https://thuevpsgiare.com.vn/vps/packages?category=vps-cheap-ip-nat';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode != 200 || !$html) {
        return false;
    }
    
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    
    $packages = [];
    
    $packageNodes = $xpath->query("//div[contains(@class, 'package') or contains(@class, 'pricing') or contains(@class, 'plan')]");
    
    if ($packageNodes->length == 0) {
        $packageNodes = $xpath->query("//div[contains(@class, 'col') or contains(@class, 'item') or contains(@class, 'card')]");
    }
    
    foreach ($packageNodes as $node) {
        $nameNode = $xpath->query(".//h3 | .//h4 | .//h5 | .//h2 | .//*[contains(@class, 'title') or contains(@class, 'name')]", $node);
        $priceNode = $xpath->query(".//*[contains(@class, 'price') or contains(@class, 'cost')]", $node);
        $cpuNode = $xpath->query(".//*[contains(text(), 'CPU') or contains(text(), 'Core')]", $node);
        $ramNode = $xpath->query(".//*[contains(text(), 'RAM') or contains(text(), 'GB')]", $node);
        $storageNode = $xpath->query(".//*[contains(text(), 'GB') or contains(text(), 'SSD') or contains(text(), 'HDD')]", $node);
        $locationNode = $xpath->query(".//*[contains(text(), 'Việt Nam') or contains(text(), 'VN') or contains(text(), 'Location')]", $node);
        
        if ($nameNode->length > 0 && $priceNode->length > 0) {
            $name = trim($nameNode->item(0)->textContent);
            $priceText = trim($priceNode->item(0)->textContent);
            
            if (preg_match('/(\d+(?:,\d+)*)/', $priceText, $matches)) {
                $originalPrice = (int)str_replace(',', '', $matches[1]);
                $sellingPrice = $originalPrice * 1.05;
                
                $cpu = $cpuNode->length > 0 ? trim($cpuNode->item(0)->textContent) : '1 Core';
                $ram = $ramNode->length > 0 ? trim($ramNode->item(0)->textContent) : '1 GB';
                $storage = $storageNode->length > 0 ? trim($storageNode->item(0)->textContent) : '20 GB';
                $location = $locationNode->length > 0 ? trim($locationNode->item(0)->textContent) : 'Việt Nam';
                
                if (empty($cpu) || $cpu == $name) $cpu = '1 Core';
                if (empty($ram) || $ram == $name) $ram = '1 GB';
                if (empty($storage) || $storage == $name) $storage = '20 GB';
                if (empty($location) || $location == $name) $location = 'Việt Nam';
                
                $packages[] = [
                    'name' => $name,
                    'cpu' => $cpu,
                    'ram' => $ram,
                    'storage' => $storage,
                    'bandwidth' => 'Unlimited',
                    'location' => $location,
                    'original_price' => $originalPrice,
                    'selling_price' => $sellingPrice
                ];
            }
        }
    }
    
    if (empty($packages)) {
        $packages = [
            [
                'name' => 'VPS NAT Basic',
                'cpu' => '1 Core',
                'ram' => '1 GB',
                'storage' => '20 GB SSD',
                'bandwidth' => 'Unlimited',
                'location' => 'Việt Nam',
                'original_price' => 50000,
                'selling_price' => 52500
            ],
            [
                'name' => 'VPS NAT Standard',
                'cpu' => '2 Core',
                'ram' => '2 GB',
                'storage' => '40 GB SSD',
                'bandwidth' => 'Unlimited',
                'location' => 'Việt Nam',
                'original_price' => 80000,
                'selling_price' => 84000
            ],
            [
                'name' => 'VPS NAT Premium',
                'cpu' => '4 Core',
                'ram' => '4 GB',
                'storage' => '80 GB SSD',
                'bandwidth' => 'Unlimited',
                'location' => 'Việt Nam',
                'original_price' => 120000,
                'selling_price' => 126000
            ],
            [
                'name' => 'VPS NAT Business',
                'cpu' => '6 Core',
                'ram' => '8 GB',
                'storage' => '160 GB SSD',
                'bandwidth' => 'Unlimited',
                'location' => 'Việt Nam',
                'original_price' => 200000,
                'selling_price' => 210000
            ]
        ];
    }
    
    return $packages;
}

function updateVpsPackages() {
    $packages = fetchVpsFromSource();
    
    if (!$packages) {
        return false;
    }
    
    executeQuery("DELETE FROM vps_packages");
    
    foreach ($packages as $package) {
        insertData('vps_packages', $package);
    }
    
    return true;
}

if (isset($_GET['update']) && $_GET['update'] == '1') {
    updateVpsPackages();
    redirect('index.php?updated=1');
}

$page_title = 'Trang chủ - ' . SITE_NAME;
$page_description = 'Dịch vụ VPS chất lượng cao với giá cả phải chăng';

$packages = fetchVpsPackages();
$updated = isset($_GET['updated']) ? true : false;

ob_start();
?>

<!-- Hero Section -->
<section class="gradient-bg text-white py-20">
    <div class="container mx-auto px-4">
        <div class="text-center">
            <h1 class="text-5xl font-bold mb-6 cyber-text">VPS Hosting 2077</h1>
            <p class="text-xl mb-8 text-gray-200">Dịch vụ VPS chất lượng cao với công nghệ thế hệ mới</p>
            <div class="flex justify-center space-x-4">
                <a href="packages.php" class="bg-cyan-500 hover:bg-cyan-600 text-white px-8 py-3 rounded-lg font-semibold transition hover-glow">
                    <i class="fas fa-rocket mr-2"></i>Xem gói VPS
                </a>
                <a href="register.php" class="border-2 border-cyan-400 hover:bg-cyan-400 text-white px-8 py-3 rounded-lg font-semibold transition">
                    <i class="fas fa-user-plus mr-2"></i>Đăng ký ngay
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Tại sao chọn chúng tôi?</h2>
            <p class="text-gray-600">Đội ngũ chuyên nghiệp với nhiều năm kinh nghiệm</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center p-6 hover-glow bg-gray-50 rounded-lg">
                <div class="text-4xl mb-4 text-cyan-500">
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Tốc độ cao</h3>
                <p class="text-gray-600">Hệ thống SSD NVMe với tốc độ đọc ghi vượt trội</p>
            </div>
            
            <div class="text-center p-6 hover-glow bg-gray-50 rounded-lg">
                <div class="text-4xl mb-4 text-cyan-500">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Bảo mật</h3>
                <p class="text-gray-600">Firewall mạnh mẽ và bảo mật đa lớp</p>
            </div>
            
            <div class="text-center p-6 hover-glow bg-gray-50 rounded-lg">
                <div class="text-4xl mb-4 text-cyan-500">
                    <i class="fas fa-headset"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Hỗ trợ 24/7</h3>
                <p class="text-gray-600">Đội ngũ hỗ trợ chuyên nghiệp luôn sẵn sàng</p>
            </div>
        </div>
    </div>
</section>

<!-- VPS Packages Section -->
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Gói VPS phổ biến</h2>
            <p class="text-gray-600">Giá đã bao gồm 5% chi phí dịch vụ</p>
            <?php if ($updated): ?>
                <div class="mt-4 p-4 bg-green-100 text-green-700 rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i>Cập nhật gói VPS thành công!
                </div>
            <?php endif; ?>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach (array_slice($packages, 0, 8) as $package): ?>
            <div class="bg-white rounded-lg shadow-lg hover-glow p-6">
                <div class="text-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($package['name']) ?></h3>
                    <div class="text-3xl font-bold text-cyan-500 mt-2">
                        <?= formatPrice($package['selling_price']) ?>
                        <span class="text-sm text-gray-500">/tháng</span>
                    </div>
                </div>
                
                <div class="space-y-2 mb-6">
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-microchip w-5 text-cyan-500"></i>
                        <span class="ml-2"><?= htmlspecialchars($package['cpu']) ?></span>
                    </div>
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-memory w-5 text-cyan-500"></i>
                        <span class="ml-2"><?= htmlspecialchars($package['ram']) ?></span>
                    </div>
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-hdd w-5 text-cyan-500"></i>
                        <span class="ml-2"><?= htmlspecialchars($package['storage']) ?></span>
                    </div>
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-globe w-5 text-cyan-500"></i>
                        <span class="ml-2"><?= htmlspecialchars($package['location']) ?></span>
                    </div>
                </div>
                
                <div class="text-center">
                    <a href="order.php?id=<?= $package['id'] ?>" class="w-full bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 text-white py-2 px-4 rounded-lg font-semibold transition">
                        <i class="fas fa-shopping-cart mr-2"></i>Đặt mua
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-8">
            <a href="packages.php" class="inline-flex items-center text-cyan-500 hover:text-cyan-600 font-semibold">
                <i class="fas fa-th mr-2"></i>Xem tất cả gói VPS
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-16 gradient-bg text-white">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div>
                <div class="text-4xl font-bold cyber-text mb-2">500+</div>
                <div class="text-gray-200">Khách hàng</div>
            </div>
            <div>
                <div class="text-4xl font-bold cyber-text mb-2">1000+</div>
                <div class="text-gray-200">VPS hoạt động</div>
            </div>
            <div>
                <div class="text-4xl font-bold cyber-text mb-2">99.9%</div>
                <div class="text-gray-200">Uptime</div>
            </div>
            <div>
                <div class="text-4xl font-bold cyber-text mb-2">24/7</div>
                <div class="text-gray-200">Hỗ trợ</div>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
include 'includes/header.php';
?>