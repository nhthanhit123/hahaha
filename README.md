# Hệ thống bán VPS Hosting

Hệ thống bán VPS thủ công bằng PHP với đầy đủ chức năng quản lý, thanh toán và tích hợp Telegram.

## Tính năng

### 🎯 Tính năng chính
- **Quản lý gói VPS**: Lấy gói VPS từ thuevpsgiare.com.vn và cộng thêm 5% vào giá
- **Chọn hệ điều hành**: Hỗ trợ nhiều OS (Windows, Linux các phiên bản)
- **Thanh toán tự động**: Thanh toán qua số dư tài khoản
- **Quản lý dịch vụ**: Hiển thị thông tin VPS (IP, user, pass, ngày hết hạn)
- **Gia hạn tự động**: Gia hạn VPS 1-6-12-24 tháng
- **Nạp tiền**: Hỗ trợ nạp tiền qua nhiều ngân hàng
- **Thông báo Telegram**: Tự động gửi thông báo khi có đơn hàng mới, gia hạn, nạp tiền

### 👥 Quản lý người dùng
- Đăng ký, đăng nhập, đăng xuất
- Quản lý hồ sơ cá nhân
- Lịch sử đơn hàng và giao dịch

### 🛠️ Admin Panel
- Dashboard với thống kê chi tiết
- Quản lý người dùng
- Quản lý dịch vụ VPS
- Quản lý đơn hàng
- Duyệt nạp tiền
- Quản lý gói dịch vụ
- Cài đặt hệ thống

## Cài đặt

### Yêu cầu
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.2+
- Webserver (Apache/Nginx)
- Extension: PDO, PDO_MySQL, cURL, JSON

### Các bước cài đặt

1. **Clone/Copy source code**
   ```bash
   # Copy toàn bộ source code vào thư mục web
   ```

2. **Cấu hình database**
   - Tạo database mới: `vps_hosting`
   - Import file `database.sql` vào database
   - Chỉnh sửa file `config/config.php` với thông tin database của bạn

3. **Cấu hình Telegram (tùy chọn)**
   - Tạo Bot Telegram qua @BotFather
   - Lấy Bot Token và Chat ID
   - Cập nhật trong `config/config.php`

4. **Phân quyền thư mục**
   ```bash
   chmod 755 -R .
   chmod 777 -R uploads/ (nếu có)
   ```

5. **Truy cập hệ thống**
   - Trang chủ: `http://yourdomain.com`
   - Admin Panel: `http://yourdomain.com/admin`
   - Tài khoản admin mặc định: `admin` / `password`

## Cấu trúc thư mục

```
├── admin/                  # Admin Panel
│   ├── index.php          # Dashboard admin
│   ├── orders.php         # Quản lý đơn hàng
│   ├── deposits.php       # Quản lý nạp tiền
│   └── includes/          # Includes cho admin
├── api/                   # API endpoints
│   ├── order-details.php  # API chi tiết đơn hàng
│   └── service-details.php # API chi tiết dịch vụ
├── config/                # Cấu hình
│   └── config.php         # File cấu hình chính
├── includes/              # Functions và components
│   ├── header.php         # Header template
│   ├── footer.php         # Footer template
│   └── functions.php      # Functions chính
├── assets/                # Static files
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   └── images/           # Images
├── database.sql           # Database schema
├── index.php              # Trang chủ
├── login.php              # Đăng nhập
├── register.php           # Đăng ký
├── packages.php           # Danh sách gói VPS
├── order.php              # Xử lý đặt hàng
├── services.php           # Quản lý dịch vụ
├── deposit.php            # Nạp tiền
├── renew.php              # Gia hạn VPS
└── orders.php             # Lịch sử đơn hàng
```

## Hướng dẫn sử dụng

### Đối với khách hàng
1. **Đăng ký tài khoản**: Tạo tài khoản mới
2. **Nạp tiền**: Nạp tiền vào tài khoản qua ngân hàng
3. **Chọn gói VPS**: Xem danh sách gói và chọn gói phù hợp
4. **Chọn OS**: Chọn hệ điều hành mong muốn
5. **Thanh toán**: Thanh toán bằng số dư tài khoản
6. **Quản lý dịch vụ**: Xem thông tin VPS, gia hạn khi cần

### Đối với Admin
1. **Đăng nhập admin**: Truy cập `/admin` với tài khoản admin
2. **Duyệt đơn hàng**: Xử lý các đơn hàng chờ
3. **Kích hoạt VPS**: Cấp IP và thông tin đăng nhập
4. **Duyệt nạp tiền**: Xác nhận và cộng tiền cho user
5. **Quản lý dịch vụ**: Theo dõi và quản lý tất cả VPS
6. **Thống kê**: Xem báo cáo doanh thu và hoạt động

## Tùy chỉnh

### Thêm ngân hàng mới
Trong `config/config.php`, thêm vào mảng `BANK_INFO`:

```php
'new_bank' => [
    'name' => 'Tên ngân hàng',
    'account_number' => 'Số tài khoản',
    'account_name' => 'Tên chủ tài khoản',
    'branch' => 'Chi nhánh'
]
```

### Thay đổi tỷ lệ cộng thêm giá
Sửa giá trị `VPS_PRICE_MARGIN` trong `config/config.php`:

```php
define('VPS_PRICE_MARGIN', 5); // 5%
```

### Tùy chỉnh Telegram
Cập nhật `TELEGRAM_BOT_TOKEN` và `TELEGRAM_CHAT_ID` trong `config/config.php`

## Bảo mật

- Mật khẩu được hash bằng `password_hash()`
- Session timeout 30 phút
- Validate input trên cả client và server
- SQL Injection protection với prepared statements
- CSRF protection (nên thêm)

## Hỗ trợ

Nếu gặp vấn đề trong quá trình sử dụng, vui lòng:
1. Kiểm tra log error của PHP
2. Kiểm tra cấu hình database
3. Đảm bảo các extension PHP đã được bật
4. Kiểm tra quyền thư mục

## Lưu ý

- Hệ thống được thiết kế để bán VPS thủ công
- Admin cần kích hoạt VPS thủ công sau khi khách hàng đặt hàng
- Có thể tích hợp thêm API tự động nếu cần
- Nên backup database thường xuyên

## License

MIT License