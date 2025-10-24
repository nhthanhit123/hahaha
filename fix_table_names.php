<?php
// 修复所有文件中的表名问题
$files = [
    'services.php',
    'admin/services.php', 
    'admin/orders.php',
    'admin/users.php',
    'admin/deposits.php',
    'admin/packages.php',
    'admin/settings.php',
    'order.php',
    'renew.php',
    'register.php',
    'deposit.php',
    'orders.php'
];

$tableMappings = [
    'vps_services' => 'services',
    'vps_packages' => 'packages',
    'operating_systems' => 'packages' // 临时映射，因为OS数据在packages中
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $original = $content;
        
        // 替换表名
        foreach ($tableMappings as $oldTable => $newTable) {
            $content = str_replace($oldTable, $newTable, $content);
        }
        
        // 替换数据库连接
        $content = str_replace("require_once 'config/config.php';", "require_once 'includes/db.php';", $content);
        $content = str_replace('require_once "config/config.php";', 'require_once "includes/db.php";', $content);
        $content = str_replace("require_once '../config/config.php';", "require_once '../includes/db.php';", $content);
        $content = str_replace('require_once "../config/config.php";', 'require_once "../includes/db.php";', $content);
        
        if ($content !== $original) {
            file_put_contents($file, $content);
            echo "Fixed: $file\n";
        } else {
            echo "No changes needed: $file\n";
        }
    } else {
        echo "Not found: $file\n";
    }
}

echo "Table name fixes completed!\n";
?>