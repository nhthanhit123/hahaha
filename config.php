<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Ho_Chi_Minh');

define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/vps-store');
define('ASSETS_URL', BASE_URL . '/assets');
define('SITE_NAME', 'VPS Hosting 2077');
define('SITE_DESCRIPTION', 'Dịch vụ VPS chất lượng cao với giá cả phải chăng');

$telegram_bot_token = 'YOUR_TELEGRAM_BOT_TOKEN';
$telegram_chat_id = 'YOUR_TELEGRAM_CHAT_ID';

function getTelegramBotToken() {
    global $telegram_bot_token;
    return $telegram_bot_token;
}

function getTelegramChatId() {
    global $telegram_chat_id;
    return $telegram_chat_id;
}

function formatPrice($price) {
    return number_format($price, 0, ',', '.') . ' VNĐ';
}

function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function sendTelegramNotification($message) {
    $bot_token = getTelegramBotToken();
    $chat_id = getTelegramChatId();
    
    if ($bot_token == 'YOUR_TELEGRAM_BOT_TOKEN' || $chat_id == 'YOUR_TELEGRAM_CHAT_ID') {
        return false;
    }
    
    $url = "https://api.telegram.org/bot$bot_token/sendMessage";
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
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}
?>