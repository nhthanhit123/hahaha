# HostGreen VPS管理系统 - 安装指南

## 📋 系统要求

- PHP 7.4+ 
- MySQL 5.7+ 或 MariaDB 10.2+
- Web服务器 (Apache/Nginx)
- 支持的PHP扩展: PDO, PDO MySQL, MBString, OpenSSL

## 🚀 安装步骤

### 1. 下载项目
```bash
git clone https://github.com/nhthanhit123/hahaha.git
cd hahaha
```

### 2. 配置数据库
1. 创建数据库和用户：
```sql
CREATE DATABASE hostgreen;
CREATE USER 'hostgreen_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON hostgreen.* TO 'hostgreen_user'@'localhost';
FLUSH PRIVILEGES;
```

2. 导入数据库结构：
```bash
mysql -u hostgreen_user -p hostgreen < database.sql
```

### 3. 配置应用
1. 复制配置文件模板：
```bash
cp config/config.example.php config/config.php
```

2. 编辑配置文件：
```php
<?php
// config/config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'hostgreen');
define('DB_USER', 'hostgreen_user');
define('DB_PASS', 'your_password');

define('SITE_URL', 'https://your-domain.com');
define('SITE_NAME', 'HostGreen');

// 其他配置项...
?>
```

### 4. 设置文件权限
```bash
chmod 755 -R .
chmod 644 config/config.php
```

### 5. 配置Web服务器

#### Apache配置示例
```apache
<VirtualHost *:80>
    DocumentRoot /path/to/hahaha
    ServerName your-domain.com
    
    <Directory /path/to/hahaha>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx配置示例
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/hahaha;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## 🔐 默认账户

- **管理员账户**: admin
- **密码**: password

⚠️ **重要**: 登录后请立即修改默认密码！

## 📁 数据库结构说明

### 主要数据表

1. **users** - 用户信息
   - 存储用户账户、余额、角色等信息
   - 包含管理员和普通用户

2. **packages** - VPS套餐
   - 预设了3个套餐：Starter, Business, Enterprise
   - 包含配置、价格等信息

3. **orders** - 订单记录
   - 用户购买VPS的订单信息
   - 支持月付/年付周期

4. **services** - VPS服务
   - 激活的VPS服务列表
   - 包含IP地址、操作系统、状态等

5. **deposits** - 充值记录
   - 用户账户充值历史
   - 支持多种支付方式

6. **settings** - 系统设置
   - 网站基本信息
   - 银行账户信息
   - 系统参数配置

### 初始数据

- **管理员账户**: admin / password
- **VPS套餐**: 3个预设套餐
- **系统设置**: 基本的网站和支付配置

## 🛠️ 功能特性

### 用户端功能
- ✅ 用户注册/登录
- ✅ 个人资料管理
- ✅ VPS套餐选择
- ✅ 在线下单
- ✅ 账户充值
- ✅ 服务管理
- ✅ 续费服务

### 管理端功能
- ✅ 用户管理
- ✅ 套餐管理
- ✅ 订单管理
- ✅ 服务管理
- ✅ 充值管理
- ✅ 系统设置

## 🔧 维护说明

### 备份数据库
```bash
mysqldump -u hostgreen_user -p hostgreen > backup_$(date +%Y%m%d).sql
```

### 恢复数据库
```bash
mysql -u hostgreen_user -p hostgreen < backup_20231201.sql
```

### 清理会话
```sql
DELETE FROM sessions WHERE last_activity < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY));
```

## 📞 技术支持

如果遇到问题，请检查：
1. PHP错误日志
2. Web服务器错误日志
3. 数据库连接配置
4. 文件权限设置

## 🔄 更新说明

当需要更新系统时：
1. 备份数据库
2. 下载新代码
3. 运行数据库迁移（如果有）
4. 更新配置文件
5. 测试功能

---

**注意**: 这是一个演示项目，生产环境使用前请进行安全评估和测试。