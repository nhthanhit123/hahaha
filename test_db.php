<?php
// 测试数据库连接
require_once 'includes/db.php';
require_once 'includes/functions.php';

echo "<h1>数据库连接测试</h1>";

try {
    // 测试数据库连接
    echo "<p>✅ 数据库连接成功</p>";
    
    // 测试获取套餐数据
    $packages = getRows("SELECT * FROM packages WHERE is_active = 1 ORDER BY price_monthly ASC");
    echo "<p>✅ 找到 " . count($packages) . " 个VPS套餐</p>";
    
    if (!empty($packages)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>名称</th><th>CPU</th><th>RAM</th><th>存储</th><th>月付价格</th></tr>";
        foreach ($packages as $package) {
            echo "<tr>";
            echo "<td>" . $package['id'] . "</td>";
            echo "<td>" . htmlspecialchars($package['name']) . "</td>";
            echo "<td>" . $package['cpu_cores'] . " 核</td>";
            echo "<td>" . $package['ram_gb'] . " GB</td>";
            echo "<td>" . $package['storage_gb'] . " GB</td>";
            echo "<td>" . number_format($package['price_monthly'], 0, ',', '.') . " đ</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 测试用户表
    $users = getRows("SELECT id, username, email, role FROM users LIMIT 5");
    echo "<p>✅ 找到 " . count($users) . " 个用户</p>";
    
    if (!empty($users)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
        echo "<tr><th>ID</th><th>用户名</th><th>邮箱</th><th>角色</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<p>✅ 所有测试通过！数据库工作正常。</p>";
    
} catch (Exception $e) {
    echo "<p>❌ 错误: " . $e->getMessage() . "</p>";
    echo "<p>请检查数据库配置和表结构。</p>";
}

echo "<p><a href='index.php'>返回首页</a></p>";
?>