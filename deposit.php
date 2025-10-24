<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = 'Vui lòng đăng nhập để tiếp tục';
    $_SESSION['flash_type'] = 'warning';
    redirect('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

$errors = [];
$success = '';
$selected_bank = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    $bank_code = $_POST['bank_code'] ?? '';
    
    // Validate
    if ($amount <= 0) {
        $errors[] = 'Vui lòng nhập số tiền hợp lệ';
    } elseif ($amount < 10000) {
        $errors[] = 'Số tiền nạp tối thiểu là 10,000đ';
    } elseif ($amount > 50000000) {
        $errors[] = 'Số tiền nạp tối đa là 50,000,000đ';
    }
    
    if (empty($bank_code) || !isset(BANK_INFO[$bank_code])) {
        $errors[] = 'Vui lòng chọn ngân hàng';
    }
    
    if (empty($errors)) {
        try {
            $deposit_code = generateDepositCode();
            
            $stmt = $pdo->prepare("
                INSERT INTO deposits (deposit_code, user_id, amount, bank_code, bank_account, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $deposit_code,
                $_SESSION['user_id'],
                $amount,
                $bank_code,
                BANK_INFO[$bank_code]['account_number']
            ]);
            
            $deposit_id = $pdo->lastInsertId();
            
            // Log hoạt động
            logActivity($_SESSION['user_id'], 'deposit_request', "Yêu cầu nạp tiền: " . formatMoney($amount));
            
            $_SESSION['flash_message'] = 'Tạo yêu cầu nạp tiền thành công! Vui lòng chuyển khoản theo thông tin bên dưới.';
            $_SESSION['flash_type'] = 'success';
            
            // Redirect để tránh refresh form
            redirect("deposit.php?deposit_id=$deposit_id");
            
        } catch(PDOException $e) {
            $errors[] = 'Lỗi hệ thống, vui lòng thử lại';
        }
    }
}

// Lấy thông tin deposit nếu có
$deposit = null;
if (isset($_GET['deposit_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM deposits WHERE id = ? AND user_id = ?");
        $stmt->execute([$_GET['deposit_id'], $_SESSION['user_id']]);
        $deposit = $stmt->fetch();
        
        if ($deposit) {
            $selected_bank = $deposit['bank_code'];
        }
    } catch(PDOException $e) {
        // Bỏ qua
    }
}

// Lấy lịch sử nạp tiền
try {
    $stmt = $pdo->prepare("
        SELECT * FROM deposits 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $deposit_history = $stmt->fetchAll();
} catch(PDOException $e) {
    $deposit_history = [];
}

$page_title = "Nạp tiền - " . SITE_NAME;
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Nạp tiền vào tài khoản</h1>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Form nạp tiền -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">Tạo yêu cầu nạp tiền</h2>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="mb-4">
                            <?php foreach ($errors as $error): ?>
                                <?php echo showAlert($error, 'error'); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="depositForm">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Số tiền (VNĐ)</label>
                            <div class="relative">
                                <input type="number" name="amount" id="amount" required
                                       min="10000" max="50000000" step="1000"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Nhập số tiền">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <span class="text-gray-500">đ</span>
                                </div>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Tối thiểu: 10,000đ | Tối đa: 50,000,000đ</p>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Chọn ngân hàng</label>
                            <div class="space-y-3">
                                <?php foreach (BANK_INFO as $code => $bank): ?>
                                    <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                        <input type="radio" name="bank_code" value="<?php echo $code; ?>" required
                                               class="w-4 h-4 text-blue-600 focus:ring-blue-500"
                                               <?php echo $selected_bank == $code ? 'checked' : ''; ?>>
                                        <div class="ml-3">
                                            <div class="font-medium text-gray-900"><?php echo $bank['name']; ?></div>
                                            <div class="text-sm text-gray-500">
                                                STK: <?php echo $bank['account_number']; ?> - <?php echo $bank['account_name']; ?>
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <button type="submit" class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 transition">
                            <i class="fas fa-plus-circle mr-2"></i>Tạo yêu cầu nạp tiền
                        </button>
                    </form>
                </div>
                
                <!-- Thông tin chuyển khoản -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">Thông tin chuyển khoản</h2>
                    
                    <div id="bankInfo" class="hidden">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <div class="flex items-center mb-3">
                                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                                <span class="font-medium text-blue-900">Nội dung chuyển khoản:</span>
                            </div>
                            <div class="bg-white rounded p-3 text-center">
                                <code class="text-lg font-bold text-blue-600" id="depositCode"></code>
                            </div>
                            <p class="text-sm text-blue-700 mt-2">Vui lòng nhập đúng nội dung để chúng tôi xác nhận nhanh chóng!</p>
                        </div>
                        
                        <div class="space-y-4" id="bankDetails">
                            <!-- Bank details will be inserted here by JavaScript -->
                        </div>
                        
                        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-start">
                                <i class="fas fa-exclamation-triangle text-yellow-600 mr-2 mt-1"></i>
                                <div class="text-sm text-yellow-800">
                                    <p class="font-medium mb-1">Lưu ý quan trọng:</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Sau khi chuyển khoản, vui lòng chờ admin xác nhận</li>
                                        <li>Thời gian xác nhận: 5-30 phút trong giờ hành chính</li>
                                        <li>Nếu quá 24h chưa được cộng tiền, vui lòng liên hệ hỗ trợ</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="selectBankFirst" class="text-center py-8 text-gray-500">
                        <i class="fas fa-university text-4xl mb-3"></i>
                        <p>Vui lòng chọn ngân hàng và nhập số tiền để xem thông tin chuyển khoản</p>
                    </div>
                </div>
            </div>
            
            <!-- Lịch sử nạp tiền -->
            <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Lịch sử nạp tiền</h2>
                
                <?php if (empty($deposit_history)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-history text-4xl mb-3"></i>
                        <p>Bạn chưa có lịch sử nạp tiền nào</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-3 text-sm font-medium text-gray-700">Mã giao dịch</th>
                                    <th class="text-left py-3 text-sm font-medium text-gray-700">Số tiền</th>
                                    <th class="text-left py-3 text-sm font-medium text-gray-700">Ngân hàng</th>
                                    <th class="text-left py-3 text-sm font-medium text-gray-700">Trạng thái</th>
                                    <th class="text-left py-3 text-sm font-medium text-gray-700">Thời gian</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deposit_history as $item): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-3">
                                            <span class="font-mono text-sm"><?php echo $item['deposit_code']; ?></span>
                                        </td>
                                        <td class="py-3 font-medium"><?php echo formatMoney($item['amount']); ?></td>
                                        <td class="py-3">
                                            <?php 
                                            $bank = BANK_INFO[$item['bank_code']] ?? null;
                                            echo $bank ? $bank['name'] : $item['bank_code'];
                                            ?>
                                        </td>
                                        <td class="py-3">
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
                                            <span class="px-2 py-1 text-xs rounded-full <?php echo $status_colors[$item['status']]; ?>">
                                                <?php echo $status_text[$item['status']]; ?>
                                            </span>
                                        </td>
                                        <td class="py-3 text-sm text-gray-600">
                                            <?php echo formatDate($item['created_at']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('depositForm');
    const bankInputs = document.querySelectorAll('input[name="bank_code"]');
    const amountInput = document.getElementById('amount');
    const bankInfo = document.getElementById('bankInfo');
    const selectBankFirst = document.getElementById('selectBankFirst');
    const depositCode = document.getElementById('depositCode');
    const bankDetails = document.getElementById('bankDetails');
    
    // Bank info data
    const bankData = <?php echo json_encode(BANK_INFO); ?>;
    
    function updateBankInfo() {
        const selectedBank = document.querySelector('input[name="bank_code"]:checked');
        const amount = amountInput.value;
        
        if (selectedBank && amount && parseFloat(amount) > 0) {
            const bankCode = selectedBank.value;
            const bank = bankData[bankCode];
            
            // Generate deposit code
            const code = 'DEP' + new Date().toISOString().slice(0,10).replace(/-/g, '') + Math.floor(Math.random() * 100000).toString().padStart(5, '0');
            depositCode.textContent = code;
            
            // Update bank details
            bankDetails.innerHTML = `
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-1 gap-3">
                        <div>
                            <span class="text-sm text-gray-600">Ngân hàng:</span>
                            <div class="font-semibold">${bank.name}</div>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Số tài khoản:</span>
                            <div class="font-semibold font-mono">${bank.account_number}</div>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Chủ tài khoản:</span>
                            <div class="font-semibold">${bank.account_name}</div>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Chi nhánh:</span>
                            <div class="font-semibold">${bank.branch}</div>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Số tiền:</span>
                            <div class="font-semibold text-lg text-blue-600">${parseInt(amount).toLocaleString('vi-VN')}đ</div>
                        </div>
                    </div>
                </div>
            `;
            
            bankInfo.classList.remove('hidden');
            selectBankFirst.classList.add('hidden');
        } else {
            bankInfo.classList.add('hidden');
            selectBankFirst.classList.remove('hidden');
        }
    }
    
    // Event listeners
    bankInputs.forEach(input => {
        input.addEventListener('change', updateBankInfo);
    });
    
    amountInput.addEventListener('input', updateBankInfo);
    
    // Initial update if deposit exists
    <?php if ($deposit): ?>
        amountInput.value = <?php echo $deposit['amount']; ?>;
        updateBankInfo();
        depositCode.textContent = '<?php echo $deposit['deposit_code']; ?>';
    <?php endif; ?>
});
</script>

<?php include 'includes/footer.php'; ?>