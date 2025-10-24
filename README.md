# VPS Store - Hệ thống bán VPS thủ công

Một hệ thống bán VPS thủ công đầy đủ tính năng được xây dựng bằng PHP với giao diện chuyên nghiệp theo phong cách công ty trung tâm dữ liệu xanh 2077.

## 🚀 Tính năng chính

### 🎯 Phía khách hàng
- **Đăng ký/Đăng nhập**: Hệ thống xác thực người dùng an toàn
- **Trang chủ**: Hiển thị các gói VPS được lấy tự động từ thuevpsgiare.com.vn
- **Danh sách VPS**: Lọc, sắp xếp, tìm kiếm gói VPS theo nhu cầu
- **Đặt mua VPS**: Chọn hệ điều hành, chu kỳ thanh toán, tự động cộng 5% giá
- **Quản lý dịch vụ**: Xem thông tin VPS, IP, tài khoản, mật khẩu
- **Gia hạn VPS**: Chọn chu kỳ 1-6-12-24 tháng với chiết khấu
- **Nạp tiền**: Hỗ trợ nhiều ngân hàng, xác nhận thủ công
- **Hồ sơ cá nhân**: Quản lý thông tin, đổi mật khẩu

### 🛠️ Phía quản trị
- **Dashboard**: Thống kê tổng quan hệ thống
- **Quản lý người dùng**: Thêm, sửa, xóa, khóa/mở tài khoản
- **Quản lý đơn hàng**: Kích hoạt VPS, quản lý trạng thái
- **Quản lý nạp tiền**: Duyệt/từ chối yêu cầu nạp tiền
- **Quản lý gói VPS**: Đồng bộ tự động từ nguồn
- **Lịch sử gia hạn**: Xem tất cả giao dịch gia hạn
- **Cài đặt Telegram**: Cấu hình thông báo tự động

### 📱 Thông báo tự động
- **Telegram Bot**: Thông báo ngay lập tức khi có:
  - Đơn hàng VPS mới
  - Yêu cầu gia hạn VPS
  - Yêu cầu nạp tiền

## 🛠️ Công nghệ sử dụng

- **Backend**: PHP 8+ (Pure PHP, không dùng framework)
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript
- **UI Framework**: Tailwind CSS
- **Icons**: Font Awesome 6
- **API**: Telegram Bot API

## 📋 Yêu cầu hệ thống

- PHP 7.4+ (khuyến nghị PHP 8+)
- MySQL/MariaDB 5.7+
- Webserver (Apache/Nginx)
- Extension: `mysqli`, `curl`, `json`

## 🚀 Cài đặt

### 1. Clone repository
```bash
git clone https://github.com/nhthanhit123/hahaha.git
cd hahaha
```

### 2. Cấu hình database
```bash
# Import database
mysql -u root -p < database.sql
```

### 3. Cấu hình kết nối
Mở file `database.php` và chỉnh sửa thông tin:
```php
$servername = "localhost";
$username = "root";
$password = "your_password";
$dbname = "vps_store";
```

### 4. Cấu hình webserver

#### Apache
```apache
<VirtualHost *:80>
    DocumentRoot /path/to/hahaha
    ServerName your-domain.com
    AllowOverride All
</VirtualHost>
```

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/hahaha;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### 5. Phân quyền
```bash
chmod -R 755 .
chmod -R 777 database/
```

## 🔧 Cấu hình Telegram Bot

### 1. Tạo Bot
1. Mở Telegram và tìm @BotFather
2. Gửi lệnh `/newbot`
3. Tên bot: `VPS Store Bot`
4. Username: `vpsstore_bot` (phải kết thúc bằng `_bot`)
5. Sao chép Bot Token

### 2. Lấy Chat ID
1. Thêm bot vào nhóm nhận thông báo
2. Gửi một tin nhắn bất kỳ vào nhóm
3. Truy cập: `https://api.telegram.org/bot[TOKEN]/getUpdates`
4. Tìm `chat.id` trong kết quả

### 3. Cấu hình trong admin
1. Đăng nhập vào trang admin: `http://your-domain.com/admin/`
2. Mở mục "Cài đặt"
3. Nhập Bot Token và Chat ID
4. Lưu và test kết nối

## 📁 Cấu trúc thư mục

```
hahaha/
├── admin/                  # Trang quản trị
│   ├── index.php          # Dashboard
│   ├── users.php          # Quản lý người dùng
│   ├── orders.php         # Quản lý đơn hàng
│   ├── deposits.php       # Quản lý nạp tiền
│   ├── packages.php       # Quản lý gói VPS
│   ├── renewals.php       # Lịch sử gia hạn
│   └── settings.php       # Cài đặt hệ thống
├── assets/                # File tĩnh
│   ├── css/
│   ├── js/
│   └── images/
├── database/              # File database
├── includes/              # File include
│   ├── functions.php      # Hàm xử lý
│   └── header.php         # Header template
├── config.php             # Cấu hình hệ thống
├── database.php           # Kết nối database
├── index.php              # Trang chủ
├── login.php              # Đăng nhập
├── register.php           # Đăng ký
├── packages.php           # Danh sách VPS
├── order.php              # Đặt mua VPS
├── services.php           # Quản lý dịch vụ
├── deposit.php            # Nạp tiền
├── profile.php            # Hồ sơ cá nhân
├── logout.php             # Đăng xuất
└── database.sql           # Database schema
```

## 🎨 Giao diện

- **Thiết kế**: Phong cách công ty trung tâm dữ liệu xanh 2077
- **Responsive**: Tương thích mọi thiết bị
- **Modern**: UI/UX hiện đại, trực quan
- **Dark Mode**: Hỗ trợ giao diện tối (tùy chọn)

## 🔐 Bảo mật

- Password hashing với `password_hash()`
- SQL Injection prevention với prepared statements
- XSS protection với `htmlspecialchars()`
- CSRF protection (có thể triển khai thêm)
- Session security

## 📊 Tính năng đặc biệt

### Auto-sync VPS Packages
- Tự động lấy gói VPS từ thuevpsgiare.com.vn
- Tự động tăng giá 5%
- Cập nhật theo lịch hoặc thủ công

### Smart Pricing
- Chiết khấu theo chu kỳ:
  - 1 tháng: Giá gốc + 5%
  - 6 tháng: Giảm 5%
  - 12 tháng: Giảm 17%
  - 24 tháng: Giảm 25%

### Multi-bank Support
- Hỗ trợ nhiều ngân hàng Việt Nam
- Hiển thị thông tin chuyển khoản
- QR code (có thể tích hợp)

## 🔄 Auto-update

System có thể tự động cập nhật gói VPS:
- Thủ công qua admin panel
- Tự động theo cron job (cần cấu hình)

## 🐛 Lỗi thường gặp

### 1. Không kết nối được database
- Kiểm tra thông tin trong `database.php`
- Đảm bảo database đã được tạo
- Kiểm tra quyền user database

### 2. Không gửi được Telegram
- Kiểm tra Bot Token và Chat ID
- Đảm bảo bot đã được thêm vào nhóm
- Kiểm tra kết nối internet

### 3. Không lấy được gói VPS
- Kiểm tra kết nối đến thuevpsgiare.com.vn
- Kiểm tra extension curl của PHP
- Thử cập nhật thủ công

## 📞 Hỗ trợ

- **Email**: support@vpsstore.com
- **Telegram**: @vpsstore_support
- **Documentation**: [Wiki](https://github.com/nhthanhit123/hahaha/wiki)

## 📝 License

MIT License - xem file [LICENSE](LICENSE) để biết chi tiết

## 🤝 Đóng góp

1. Fork project
2. Tạo feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

---

**Made with ❤️ by VPS Store Team**