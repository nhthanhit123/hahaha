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