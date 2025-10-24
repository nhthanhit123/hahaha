<?php
// Script to fix database connections in all PHP files
$files = [
    'login.php',
    'register.php', 
    'dashboard.php',
    'profile.php',
    'services.php',
    'deposit.php',
    'orders.php',
    'order.php',
    'renew.php',
    'admin/index.php',
    'admin/users.php',
    'admin/services.php',
    'admin/orders.php',
    'admin/deposits.php',
    'admin/packages.php',
    'admin/settings.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Replace old require statements
        $content = str_replace(
            "require_once 'config/config.php';",
            "require_once 'includes/db.php';",
            $content
        );
        
        $content = str_replace(
            'require_once "config/config.php";',
            'require_once "includes/db.php";',
            $content
        );
        
        $content = str_replace(
            "require_once '../config/config.php';",
            "require_once '../includes/db.php';",
            $content
        );
        
        $content = str_replace(
            'require_once "../config/config.php";',
            'require_once "../includes/db.php";',
            $content
        );
        
        file_put_contents($file, $content);
        echo "Fixed: $file\n";
    } else {
        echo "Not found: $file\n";
    }
}

echo "Database connection fixes completed!\n";
?>