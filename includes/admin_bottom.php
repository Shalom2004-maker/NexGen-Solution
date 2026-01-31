<?php
// admin_bottom.php - closes the shared admin layout
?>
<!-- page content ends -->
</div> <!-- .main-content -->
</div> <!-- .main-wrapper -->

<?php include __DIR__ . '/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const sidebar = document.getElementById('sidebarContainer');

        if (sidebarToggleBtn && sidebar) {
            sidebarToggleBtn.addEventListener('click', function(e) {
                sidebar.classList.toggle('show');
                if (sidebarOverlay) sidebarOverlay.classList.toggle('show');
            });
        }
        if (sidebarOverlay && sidebar) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
        }
    });
</script>
</body>

</html>