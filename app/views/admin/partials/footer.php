            </div><!-- /.container-fluid -->
        </div><!-- /#content -->
    </div><!-- /.wrapper -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery (necessário para alguns plugins) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="<?= BASE_URL ?>assets/js/admin.js"></script>
    
    <!-- Scripts específicos de páginas -->
    <?php if (isset($page_scripts)): ?>
    <?php foreach ($page_scripts as $script): ?>
    <script src="<?= BASE_URL ?>assets/js/<?= $script ?>"></script>
    <?php endforeach; ?>
    <?php endif; ?>

    <script>
        // Script para toggle do sidebar
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('sidebarCollapse').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('active');
                document.getElementById('content').classList.toggle('active');
            });

            // Auto-dismiss para alertas
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>
