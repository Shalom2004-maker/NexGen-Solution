<script>
function toggleSidebar() {
    const sidebar = document.getElementById('nexgenSidebar');
    if (sidebar) {
        sidebar.classList.toggle('show');
    }
}

document.querySelectorAll('.nexgen-sidebar-menu a').forEach(link => {
    link.addEventListener('click', function() {
        const sidebar = document.getElementById('nexgenSidebar');
        if (sidebar && window.innerWidth <= 768) {
            sidebar.classList.remove('show');
        }
    });
});

document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('nexgenSidebar');
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    if (sidebar && toggleBtn && !sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
        if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
        }
    }
});
</script>
