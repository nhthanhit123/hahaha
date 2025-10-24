<footer class="bg-gray-800 text-white mt-16">
    <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="text-xl font-bold mb-4">
                    <i class="fas fa-server mr-2"></i><?php echo SITE_NAME; ?>
                </h3>
                <p class="text-gray-400">Cung cấp dịch vụ VPS chất lượng cao với giá cả phải chăng.</p>
                <div class="flex space-x-4 mt-4">
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-facebook text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-twitter text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-telegram text-xl"></i>
                    </a>
                </div>
            </div>
            
            <div>
                <h4 class="text-lg font-semibold mb-4">Dịch vụ</h4>
                <ul class="space-y-2">
                    <li><a href="packages.php" class="text-gray-400 hover:text-white transition">VPS giá rẻ</a></li>
                    <li><a href="packages.php" class="text-gray-400 hover:text-white transition">VPS hiệu năng cao</a></li>
                    <li><a href="packages.php" class="text-gray-400 hover:text-white transition">VPS chuyên dụng</a></li>
                    <li><a href="deposit.php" class="text-gray-400 hover:text-white transition">Nạp tiền</a></li>
                </ul>
            </div>
            
            <div>
                <h4 class="text-lg font-semibold mb-4">Hỗ trợ</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="text-gray-400 hover:text-white transition">Hướng dẫn sử dụng</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">FAQ</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">Liên hệ hỗ trợ</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">Điều khoản sử dụng</a></li>
                </ul>
            </div>
            
            <div>
                <h4 class="text-lg font-semibold mb-4">Liên hệ</h4>
                <ul class="space-y-2 text-gray-400">
                    <li><i class="fas fa-envelope mr-2"></i><?php echo ADMIN_EMAIL; ?></li>
                    <li><i class="fas fa-phone mr-2"></i>1900 0000</li>
                    <li><i class="fas fa-map-marker-alt mr-2"></i>Hà Nội, Việt Nam</li>
                    <li><i class="fas fa-clock mr-2"></i>Hỗ trợ 24/7</li>
                </ul>
            </div>
        </div>
        
        <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</footer>

<script>
    // Auto-hide flash messages after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('[role="alert"]');
        alerts.forEach(function(alert) {
            alert.style.display = 'none';
        });
    }, 5000);
</script>
</body>
</html>