<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    // Log hoạt động
    logActivity($_SESSION['user_id'], 'logout', "Đăng xuất");
    
    // Xóa remember token
    if (isset($_COOKIE['remember_token'])) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL, token_expiry = NULL WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        } catch(PDOException $e) {
            // Bỏ qua lỗi
        }
        
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // Hủy session
    session_destroy();
}

// Chuyển về trang đăng nhập
header('Location: login.php');
exit();
?>