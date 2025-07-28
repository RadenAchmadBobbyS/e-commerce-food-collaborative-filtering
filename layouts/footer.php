        </div>
    </main>
    
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <p>&copy; <?= date('Y') ?> FoodRec - Sistem Rekomendasi Makanan. Dibuat dengan ❤️</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="../admin/login.php" class="text-muted" style="font-size: 0.85rem;">
                        <i class="fas fa-shield-alt"></i> Admin Login
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Simple JavaScript for enhanced UX
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading animation to forms
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<span class="loading"></span> Memproses...';
                        submitBtn.disabled = true;
                    }
                });
            });

            // Auto-hide messages after 5 seconds
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                setTimeout(() => {
                    message.style.opacity = '0';
                    setTimeout(() => message.remove(), 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>
