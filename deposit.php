<?php
require_once 'config.php';
require_once 'database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php?redirect=deposit.php');
}

$bank_accounts = getBankAccounts();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = (float)$_POST['amount'];
    $bank_code = $_POST['bank_code'];
    $transaction_id = cleanInput($_POST['transaction_id'] ?? '');
    $notes = cleanInput($_POST['notes'] ?? '');
    
    $errors = [];
    
    if (empty($amount) || $amount <= 0) {
        $errors[] = 'Vui lòng nhập số tiền hợp lệ';
    } elseif ($amount < 10000) {
        $errors[] = 'Số tiền nạp tối thiểu là 10,000 VNĐ';
    } elseif ($amount > 50000000) {
        $errors[] = 'Số tiền nạp tối đa là 50,000,000 VNĐ';
    }
    
    if (empty($bank_code)) {
        $errors[] = 'Vui lòng chọn ngân hàng';
    }
    
    if (empty($errors)) {
        $depositData = [
            'user_id' => $_SESSION['user_id'],
            'amount' => $amount,
            'bank_code' => $bank_code,
            'bank_name' => '',
            'transaction_id' => $transaction_id,
            'notes' => $notes
        ];
        
        foreach ($bank_accounts as $bank) {
            if ($bank['bank_code'] == $bank_code) {
                $depositData['bank_name'] = $bank['bank_name'];
                break;
            }
        }
        
        if (createDeposit($depositData)) {
            $user = getUser($_SESSION['user_id']);
            sendDepositNotification($depositData, $user, $depositData);
            
            $_SESSION['success_message'] = 'Yêu cầu nạp tiền đã được gửi! Chúng tôi sẽ xác nhận sớm.';
            redirect('deposit.php');
        } else {
            $errors[] = 'Gửi yêu cầu thất bại. Vui lòng thử lại.';
        }
    }
}

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$deposits = getUserDeposits($_SESSION['user_id']);

$page_title = 'Nạp tiền - ' . SITE_NAME;

ob_start();
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Nạp tiền tài khoản</h1>
            <p class="text-gray-600">Nạp tiền để sử dụng các dịch vụ VPS của chúng tôi</p>
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
        
        <!-- Current Balance -->
        <div class="bg-gradient-to-r from-cyan-500 to-blue-500 rounded-lg shadow-lg p-6 mb-8 text-white">
            <div class="text-center">
                <h3 class="text-lg font-semibold mb-2">Số dư hiện tại</h3>
                <p class="text-4xl font-bold"><?= formatPrice($_SESSION['balance']) ?></p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Deposit Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Tạo yêu cầu nạp tiền</h2>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-400"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Lỗi</h3>
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
                    
                    <form method="POST" id="depositForm">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Số tiền nạp *
                            </label>
                            <div class="relative">
                                <input type="number" name="amount" id="amount" required
                                       min="10000" max="50000000" step="1000"
                                       class="w-full px-3 py-2 pr-12 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500"
                                       placeholder="Nhập số tiền">
                                <span class="absolute right-3 top-2 text-gray-500">VNĐ</span>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Tối thiểu: 10,000 VNĐ | Tối đa: 50,000,000 VNĐ</p>
                        </div>
                        
                        <!-- Quick Amount Buttons -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Chọn nhanh
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <button type="button" onclick="setAmount(50000)" 
                                        class="border border-gray-300 hover:bg-cyan-50 hover:border-cyan-500 text-gray-700 py-2 px-3 rounded-md text-sm font-medium transition">
                                    50,000
                                </button>
                                <button type="button" onclick="setAmount(100000)" 
                                        class="border border-gray-300 hover:bg-cyan-50 hover:border-cyan-500 text-gray-700 py-2 px-3 rounded-md text-sm font-medium transition">
                                    100,000
                                </button>
                                <button type="button" onclick="setAmount(500000)" 
                                        class="border border-gray-300 hover:bg-cyan-50 hover:border-cyan-500 text-gray-700 py-2 px-3 rounded-md text-sm font-medium transition">
                                    500,000
                                </button>
                                <button type="button" onclick="setAmount(1000000)" 
                                        class="border border-gray-300 hover:bg-cyan-50 hover:border-cyan-500 text-gray-700 py-2 px-3 rounded-md text-sm font-medium transition">
                                    1,000,000
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Chọn ngân hàng *
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <?php foreach ($bank_accounts as $bank): ?>
                                    <label class="bank-card border border-gray-300 rounded-lg p-4 cursor-pointer hover:border-cyan-500 transition">
                                        <input type="radio" name="bank_code" value="<?= $bank['bank_code'] ?>" 
                                               class="sr-only bank-radio" required>
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center text-white font-bold mr-3">
                                                <?= strtoupper(substr($bank['bank_code'], 0, 2)) ?>
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900"><?= htmlspecialchars($bank['bank_name']) ?></div>
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($bank['account_number']) ?></div>
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Selected Bank Info -->
                        <div id="selectedBankInfo" class="hidden mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <h4 class="font-semibold text-blue-800 mb-2">Thông tin chuyển khoản</h4>
                            <div id="bankDetails"></div>
                        </div>
                        
                        <div class="mb-6">
                            <label for="transaction_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Mã giao dịch (nếu có)
                            </label>
                            <input type="text" name="transaction_id" id="transaction_id"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500"
                                   placeholder="Nhập mã giao dịch của bạn">
                        </div>
                        
                        <div class="mb-6">
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                Ghi chú
                            </label>
                            <textarea name="notes" id="notes" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-cyan-500 focus:border-cyan-500"
                                      placeholder="Nhập ghi chú (không bắt buộc)"></textarea>
                        </div>
                        
                        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <h4 class="font-semibold text-yellow-800 mb-2">
                                <i class="fas fa-info-circle mr-2"></i>Lưu ý quan trọng
                            </h4>
                            <ul class="text-sm text-yellow-700 space-y-1">
                                <li>• Sau khi chuyển khoản, vui lòng điền thông tin và gửi yêu cầu</li>
                                <li>• Chúng tôi sẽ xác nhận và cộng tiền vào tài khoản trong vòng 5-30 phút</li>
                                <li>• Số tiền nạp phải khớp với số tiền chuyển khoản</li>
                                <li>• Nội dung chuyển khoản: VPS <?= $_SESSION['username'] ?></li>
                            </ul>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 text-white py-3 px-6 rounded-lg font-semibold transition">
                            <i class="fas fa-paper-plane mr-2"></i>Gửi yêu cầu nạp tiền
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Deposit History -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Lịch sử nạp tiền</h3>
                    
                    <?php if (empty($deposits)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-history text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Chưa có lịch sử nạp tiền</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            <?php foreach (array_slice($deposits, 0, 10) as $deposit): ?>
                                <div class="border border-gray-200 rounded-lg p-3">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <div class="font-medium text-gray-900">
                                                <?= formatPrice($deposit['amount']) ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars($deposit['bank_name']) ?>
                                            </div>
                                        </div>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold
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
                                                    echo 'Hoàn thành';
                                                    break;
                                                case 'pending':
                                                    echo 'Chờ xử lý';
                                                    break;
                                                case 'failed':
                                                    echo 'Thất bại';
                                                    break;
                                                default:
                                                    echo $deposit['status'];
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        <?= formatDate($deposit['created_at']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($deposits) > 10): ?>
                            <div class="mt-4 text-center">
                                <button onclick="showAllHistory()" class="text-cyan-500 hover:text-cyan-600 text-sm font-medium">
                                    Xem tất cả lịch sử
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Support Info -->
                <div class="bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg shadow-lg p-6 mt-6 text-white">
                    <h3 class="text-lg font-bold mb-3">
                        <i class="fas fa-headset mr-2"></i>Hỗ trợ
                    </h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center">
                            <i class="fas fa-phone mr-2"></i>
                            <span>Hotline: 1900 1234</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-envelope mr-2"></i>
                            <span>support@vpsstore.com</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fab fa-telegram mr-2"></i>
                            <span>Telegram: @vpsstore_support</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const bankAccounts = <?= json_encode($bank_accounts) ?>;

function setAmount(amount) {
    document.getElementById('amount').value = amount;
}

// Bank selection handling
document.querySelectorAll('.bank-radio').forEach(radio => {
    radio.addEventListener('change', function() {
        // Remove previous selection
        document.querySelectorAll('.bank-card').forEach(card => {
            card.classList.remove('border-cyan-500', 'bg-cyan-50');
            card.classList.add('border-gray-300');
        });
        
        // Add selection to current
        const selectedCard = this.closest('.bank-card');
        selectedCard.classList.remove('border-gray-300');
        selectedCard.classList.add('border-cyan-500', 'bg-cyan-50');
        
        // Show bank details
        const bankCode = this.value;
        const bank = bankAccounts.find(b => b.bank_code === bankCode);
        
        if (bank) {
            document.getElementById('selectedBankInfo').classList.remove('hidden');
            document.getElementById('bankDetails').innerHTML = `
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-blue-700">Ngân hàng:</span>
                        <span class="font-medium">${bank.bank_name}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-blue-700">Số tài khoản:</span>
                        <span class="font-medium">${bank.account_number}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-blue-700">Chủ tài khoản:</span>
                        <span class="font-medium">${bank.account_name}</span>
                    </div>
                    <div class="mt-3 p-2 bg-white rounded border border-blue-200">
                        <p class="text-xs text-blue-800 font-medium">Nội dung chuyển khoản:</p>
                        <p class="text-sm font-mono text-blue-900">VPS <?= document.getElementById('username')?.value || 'username' ?></p>
                                            </div>
                                        </div>
                                    `;
                                }
                            });
                        });

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'});
}

function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price) + ' VNĐ';
}

function showAllHistory() {
    // This would typically show a modal or redirect to a full history page
    alert('Tính năng đang được phát triển!');
}

// Form validation
document.getElementById('depositForm').addEventListener('submit', function(e) {
    const amount = parseFloat(document.getElementById('amount').value);
    const selectedBank = document.querySelector('input[name="bank_code"]:checked');
    
    if (!amount || amount < 10000) {
        e.preventDefault();
        showToast('Số tiền nạp tối thiểu là 10,000 VNĐ', 'error');
        return;
    }
    
    if (!selectedBank) {
        e.preventDefault();
        showToast('Vui lòng chọn ngân hàng', 'error');
        return;
    }
    
    showLoading();
});

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
    toast.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function showLoading() {
    const loader = document.createElement('div');
    loader.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    loader.innerHTML = '<div class="loading-spinner"></div>';
    loader.id = 'loadingOverlay';
    document.body.appendChild(loader);
}

function hideLoading() {
    const loader = document.getElementById('loadingOverlay');
    if (loader) {
        loader.remove();
    }
}
</script>

<?php
$content = ob_get_clean();
include 'includes/header.php';
?>