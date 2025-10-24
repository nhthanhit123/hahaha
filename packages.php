<?php
require_once 'config.php';
require_once 'database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php?redirect=packages.php');
}

$packages = fetchVpsPackages();

$page_title = 'Gói VPS - ' . SITE_NAME;

ob_start();
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Gói VPS của chúng tôi</h1>
            <p class="text-xl text-gray-600">Chọn gói VPS phù hợp với nhu cầu của bạn</p>
        </div>
        
        <!-- Filter Bar -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sắp xếp</label>
                    <select id="sortSelect" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="price-asc">Giá tăng dần</option>
                        <option value="price-desc">Giá giảm dần</option>
                        <option value="name-asc">Tên A-Z</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">CPU tối thiểu</label>
                    <select id="cpuFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">Tất cả</option>
                        <option value="1">1 Core</option>
                        <option value="2">2 Core</option>
                        <option value="4">4 Core+</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">RAM tối thiểu</label>
                    <select id="ramFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">Tất cả</option>
                        <option value="1">1 GB</option>
                        <option value="2">2 GB</option>
                        <option value="4">4 GB+</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button onclick="resetFilters()" class="w-full bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-md transition">
                        <i class="fas fa-redo mr-2"></i>Đặt lại
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Packages Grid -->
        <div id="packagesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($packages as $package): ?>
            <div class="package-card bg-white rounded-lg shadow-lg hover-glow" 
                 data-price="<?= $package['selling_price'] ?>"
                 data-name="<?= htmlspecialchars($package['name']) ?>"
                 data-cpu="<?= htmlspecialchars($package['cpu']) ?>"
                 data-ram="<?= htmlspecialchars($package['ram']) ?>">
                
                <div class="p-6">
                    <div class="text-center mb-4">
                        <h3 class="text-xl font-bold text-gray-800 mb-2">
                            <?= htmlspecialchars($package['name']) ?>
                        </h3>
                        <div class="text-3xl font-bold text-cyan-500">
                            <?= formatPrice($package['selling_price']) ?>
                            <span class="text-sm text-gray-500">/tháng</span>
                        </div>
                        <div class="text-sm text-gray-400 line-through mt-1">
                            <?= formatPrice($package['original_price']) ?>
                        </div>
                    </div>
                    
                    <div class="space-y-3 mb-6">
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-microchip w-5 text-cyan-500"></i>
                            <span class="ml-2 text-sm"><?= htmlspecialchars($package['cpu']) ?></span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-memory w-5 text-cyan-500"></i>
                            <span class="ml-2 text-sm"><?= htmlspecialchars($package['ram']) ?></span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-hdd w-5 text-cyan-500"></i>
                            <span class="ml-2 text-sm"><?= htmlspecialchars($package['storage']) ?></span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-wifi w-5 text-cyan-500"></i>
                            <span class="ml-2 text-sm"><?= htmlspecialchars($package['bandwidth']) ?></span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-globe w-5 text-cyan-500"></i>
                            <span class="ml-2 text-sm"><?= htmlspecialchars($package['location']) ?></span>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <a href="order.php?id=<?= $package['id'] ?>" 
                           class="w-full bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 text-white py-2 px-4 rounded-lg font-semibold text-sm transition text-center block">
                            <i class="fas fa-shopping-cart mr-2"></i>Đặt mua
                        </a>
                        <button onclick="showPackageDetails(<?= htmlspecialchars(json_encode($package)) ?>)" 
                                class="w-full border border-cyan-500 hover:bg-cyan-50 text-cyan-500 py-2 px-4 rounded-lg font-semibold text-sm transition">
                            <i class="fas fa-info-circle mr-2"></i>Chi tiết
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- No Results Message -->
        <div id="noResults" class="hidden text-center py-12">
            <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">Không tìm thấy gói VPS phù hợp</h3>
            <p class="text-gray-500">Vui lòng thử lại với bộ lọc khác</p>
        </div>
    </div>
</div>

<!-- Package Details Modal -->
<div id="packageModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold text-gray-900" id="modalTitle"></h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div id="modalContent"></div>
            
            <div class="mt-6 flex space-x-4">
                <a id="modalOrderBtn" href="#" 
                   class="flex-1 bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 text-white py-3 px-6 rounded-lg font-semibold transition text-center">
                    <i class="fas fa-shopping-cart mr-2"></i>Đặt mua ngay
                </a>
                <button onclick="closeModal()" 
                        class="flex-1 border border-gray-300 hover:bg-gray-50 text-gray-700 py-3 px-6 rounded-lg font-semibold transition">
                    Đóng
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let packages = <?= json_encode($packages) ?>;

document.getElementById('sortSelect').addEventListener('change', filterPackages);
document.getElementById('cpuFilter').addEventListener('change', filterPackages);
document.getElementById('ramFilter').addEventListener('change', filterPackages);

function filterPackages() {
    const sortValue = document.getElementById('sortSelect').value;
    const cpuFilter = document.getElementById('cpuFilter').value;
    const ramFilter = document.getElementById('ramFilter').value;
    
    let filteredPackages = [...packages];
    
    // CPU Filter
    if (cpuFilter) {
        filteredPackages = filteredPackages.filter(pkg => {
            const cpu = parseInt(pkg.cpu) || 0;
            if (cpuFilter === '4') return cpu >= 4;
            return cpu == cpuFilter;
        });
    }
    
    // RAM Filter
    if (ramFilter) {
        filteredPackages = filteredPackages.filter(pkg => {
            const ram = parseInt(pkg.ram) || 0;
            if (ramFilter === '4') return ram >= 4;
            return ram == ramFilter;
        });
    }
    
    // Sort
    switch(sortValue) {
        case 'price-asc':
            filteredPackages.sort((a, b) => a.selling_price - b.selling_price);
            break;
        case 'price-desc':
            filteredPackages.sort((a, b) => b.selling_price - a.selling_price);
            break;
        case 'name-asc':
            filteredPackages.sort((a, b) => a.name.localeCompare(b.name));
            break;
    }
    
    renderPackages(filteredPackages);
}

function renderPackages(packagesToRender) {
    const grid = document.getElementById('packagesGrid');
    const noResults = document.getElementById('noResults');
    
    if (packagesToRender.length === 0) {
        grid.innerHTML = '';
        noResults.classList.remove('hidden');
        return;
    }
    
    noResults.classList.add('hidden');
    
    grid.innerHTML = packagesToRender.map(pkg => `
        <div class="package-card bg-white rounded-lg shadow-lg hover-glow" 
             data-price="${pkg.selling_price}"
             data-name="${pkg.name}"
             data-cpu="${pkg.cpu}"
             data-ram="${pkg.ram}">
            
            <div class="p-6">
                <div class="text-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">
                        ${pkg.name}
                    </h3>
                    <div class="text-3xl font-bold text-cyan-500">
                        ${formatPrice(pkg.selling_price)}
                        <span class="text-sm text-gray-500">/tháng</span>
                    </div>
                    <div class="text-sm text-gray-400 line-through mt-1">
                        ${formatPrice(pkg.original_price)}
                    </div>
                </div>
                
                <div class="space-y-3 mb-6">
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-microchip w-5 text-cyan-500"></i>
                        <span class="ml-2 text-sm">${pkg.cpu}</span>
                    </div>
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-memory w-5 text-cyan-500"></i>
                        <span class="ml-2 text-sm">${pkg.ram}</span>
                    </div>
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-hdd w-5 text-cyan-500"></i>
                        <span class="ml-2 text-sm">${pkg.storage}</span>
                    </div>
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-wifi w-5 text-cyan-500"></i>
                        <span class="ml-2 text-sm">${pkg.bandwidth}</span>
                    </div>
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-globe w-5 text-cyan-500"></i>
                        <span class="ml-2 text-sm">${pkg.location}</span>
                    </div>
                </div>
                
                <div class="space-y-2">
                    <a href="order.php?id=${pkg.id}" 
                       class="w-full bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 text-white py-2 px-4 rounded-lg font-semibold text-sm transition text-center block">
                        <i class="fas fa-shopping-cart mr-2"></i>Đặt mua
                    </a>
                    <button onclick="showPackageDetails(${JSON.stringify(pkg).replace(/"/g, '&quot;')})" 
                            class="w-full border border-cyan-500 hover:bg-cyan-50 text-cyan-500 py-2 px-4 rounded-lg font-semibold text-sm transition">
                        <i class="fas fa-info-circle mr-2"></i>Chi tiết
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function resetFilters() {
    document.getElementById('sortSelect').value = 'price-asc';
    document.getElementById('cpuFilter').value = '';
    document.getElementById('ramFilter').value = '';
    filterPackages();
}

function showPackageDetails(pkg) {
    document.getElementById('modalTitle').textContent = pkg.name;
    document.getElementById('modalOrderBtn').href = `order.php?id=${pkg.id}`;
    
    document.getElementById('modalContent').innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-semibold text-gray-800 mb-3">Thông số kỹ thuật</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">CPU:</span>
                        <span class="font-medium">${pkg.cpu}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">RAM:</span>
                        <span class="font-medium">${pkg.ram}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Ổ cứng:</span>
                        <span class="font-medium">${pkg.storage}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Băng thông:</span>
                        <span class="font-medium">${pkg.bandwidth}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Vị trí:</span>
                        <span class="font-medium">${pkg.location}</span>
                    </div>
                </div>
            </div>
            
            <div>
                <h4 class="font-semibold text-gray-800 mb-3">Thông tin giá</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Giá gốc:</span>
                        <span class="font-medium line-through text-gray-400">${formatPrice(pkg.original_price)}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Giá bán:</span>
                        <span class="font-medium text-cyan-600">${formatPrice(pkg.selling_price)}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Phí dịch vụ:</span>
                        <span class="font-medium">5%</span>
                    </div>
                    <div class="border-t pt-2 mt-2">
                        <div class="flex justify-between">
                            <span class="font-semibold">Tổng/tháng:</span>
                            <span class="font-bold text-cyan-600">${formatPrice(pkg.selling_price)}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
            <h4 class="font-semibold text-blue-800 mb-2">Tính năng nổi bật</h4>
            <ul class="text-sm text-blue-700 space-y-1">
                <li><i class="fas fa-check mr-2"></i>Full quyền quản trị (Root/Admin)</li>
                <li><i class="fas fa-check mr-2"></i>Hệ điều hành tùy chọn</li>
                <li><i class="fas fa-check mr-2"></i>Kích hoạt tức thì</li>
                <li><i class="fas fa-check mr-2"></i>Sao lưu tự động hàng tuần</li>
                <li><i class="fas fa-check mr-2"></i>Hỗ trợ kỹ thuật 24/7</li>
            </ul>
        </div>
    `;
    
    document.getElementById('packageModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('packageModal').classList.add('hidden');
}

function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price) + ' VNĐ';
}

// Close modal when clicking outside
document.getElementById('packageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php
$content = ob_get_clean();
include 'includes/header.php';
?>