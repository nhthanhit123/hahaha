<?php
// Hàm gửi tin nhắn Telegram
function sendTelegram($message) {
    $bot_token = TELEGRAM_BOT_TOKEN;
    $chat_id = TELEGRAM_CHAT_ID;
    
    if ($bot_token == 'YOUR_BOT_TOKEN' || $chat_id == 'YOUR_CHAT_ID') {
        return false;
    }
    
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}

// Hàm kiểm tra đăng nhập
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Hàm kiểm tra admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

// Hàm redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Hàm tạo slug
function createSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

// Hàm format tiền
function formatMoney($amount) {
    return number_format($amount, 0, ',', '.') . 'đ';
}

// Hàm format ngày
function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

// Hàm tạo mã đơn hàng
function generateOrderCode() {
    return 'VPS' . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 6));
}

// Hàm tạo mã nạp tiền
function generateDepositCode() {
    return 'DEP' . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 6));
}

// Hàm validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Hàm validate số điện thoại
function isValidPhone($phone) {
    return preg_match('/^(0[3-9][0-9]{8})$/', $phone);
}

// Hàm hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Hàm verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Hàm lấy VPS từ API thuevpsgiare
function getVPSPackages() {
    $url = 'https://thuevpsgiare.com.vn/vps/packages?category=vps-cheap-ip-nat';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $html = curl_exec($ch);
    curl_close($ch);
    
    if (!$html) {
        return [];
    }
    
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    
    $packages = [];
    
    // Lấy các gói VPS từ trang
    $packageNodes = $xpath->query("//div[contains(@class, 'package') or contains(@class, 'pricing-card') or contains(@class, 'plan')]");
    
    foreach ($packageNodes as $node) {
        $package = [];
        
        // Lấy tên gói
        $titleNode = $xpath->query(".//h3[contains(@class, 'title')] | .//h2[contains(@class, 'title')] | .//div[contains(@class, 'name')]", $node);
        if ($titleNode->length > 0) {
            $package['name'] = trim($titleNode->item(0)->textContent);
        }
        
        // Lấy giá
        $priceNode = $xpath->query(".//span[contains(@class, 'price')] | .//div[contains(@class, 'price')]", $node);
        if ($priceNode->length > 0) {
            $priceText = trim($priceNode->item(0)->textContent);
            $price = preg_replace('/[^0-9]/', '', $priceText);
            $package['price'] = (int)$price;
        }
        
        // Lấy thông số kỹ thuật
        $specs = [];
        $specNodes = $xpath->query(".//li | .//div[contains(@class, 'feature')]", $node);
        foreach ($specNodes as $specNode) {
            $specText = trim($specNode->textContent);
            if (!empty($specText)) {
                $specs[] = $specText;
            }
        }
        $package['specs'] = $specs;
        
        if (!empty($package['name']) && !empty($package['price'])) {
            // Cộng thêm 5% vào giá
            $package['price'] = round($package['price'] * (1 + VPS_PRICE_MARGIN / 100));
            $packages[] = $package;
        }
    }
    
    return $packages;
}

// Hàm hiển thị alert
function showAlert($message, $type = 'info') {
    $alertClass = [
        'success' => 'bg-green-100 border-green-400 text-green-700',
        'error' => 'bg-red-100 border-red-400 text-red-700',
        'warning' => 'bg-yellow-100 border-yellow-400 text-yellow-700',
        'info' => 'bg-blue-100 border-blue-400 text-blue-700'
    ];
    
    return "<div class='border px-4 py-3 rounded relative {$alertClass[$type]}' role='alert'>
        <span class='block sm:inline'>$message</span>
    </div>";
}

// Hàm pagination
function paginate($total, $page, $limit, $url) {
    $total_pages = ceil($total / $limit);
    $current_page = max(1, min($page, $total_pages));
    
    $pagination = '<div class="flex justify-center items-center space-x-2">';
    
    // Previous
    if ($current_page > 1) {
        $pagination .= "<a href='{$url}?page=" . ($current_page - 1) . "' class='px-3 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50'>←</a>";
    }
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i == $current_page ? 'bg-blue-500 text-white' : 'bg-white border border-gray-300 hover:bg-gray-50';
        $pagination .= "<a href='{$url}?page=$i' class='px-3 py-2 rounded-md $active'>$i</a>";
    }
    
    // Next
    if ($current_page < $total_pages) {
        $pagination .= "<a href='{$url}?page=" . ($current_page + 1) . "' class='px-3 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50'>→</a>";
    }
    
    $pagination .= '</div>';
    
    return $pagination;
}

// Hàm log hoạt động
function logActivity($user_id, $action, $details = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $user_id,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch(PDOException $e) {
        error_log("Log error: " . $e->getMessage());
    }
}
?>