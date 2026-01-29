<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
    href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
    rel="stylesheet">

<!-- Bootstrap CSS Link -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

<!-- Responsive Sidebar Component -->
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Inter", sans-serif;

}

.sidebar-toggle-btn {
    display: none;
    position: fixed;
    top: 1rem;
    left: 1rem;
    z-index: 1050;
    background-color: #337ccfe2;
    color: white;
    border: none;
    padding: 0.6rem 0.8rem;
    border-radius: 5px;
    cursor: pointer;
    font-size: 1.25rem;
}

.nexgen-sidebar {
    min-height: 100vh;
    background-color: #ffffff;
    color: black;
    border-right: 1px solid #d4d4d4;
    position: fixed;
    padding-bottom: 200px;
}

.nexgen-sidebar-header {
    border-bottom: 1px solid #d4d4d4;
    padding: 1.5rem;
    position: sticky;
    top: 0;
    background-color: #ffffff;
    z-index: 100;
}

.nexgen-sidebar-header h3 {
    margin: 0.5rem 0 0 0;
    font-weight: bold;
    font-size: 1.5rem;
}

.nexgen-sidebar-header p {
    margin: 0.5rem 0 0 0;
    color: lightslategray;
    font-size: 0.9rem;
}

.nexgen-sidebar-menu {
    overflow-y: auto;
    padding: 1rem 0;
    height: calc(100vh - 250px);
}

.nexgen-sidebar-menu h5 {
    text-decoration: none;
    color: lightslategray;
    padding-top: 0.5rem;
    padding-left: 1.5rem;
    padding-bottom: 0.5rem;
    margin: 0;
    font-size: 17px;
    font-weight: bold;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.nexgen-sidebar-menu a {
    text-decoration: none;
    color: lightslategray;
    padding-top: 0.5rem;
    padding-left: 1.5rem;
    padding-bottom: 0.5rem;
    display: block;
    margin-bottom: 0.2rem;
    transition: all 0.3s ease;
}

.nexgen-sidebar-menu a:hover {
    color: white;
    background-color: #337ccfe2;
    border-radius: 5px;
}

.nexgen-sidebar-menu a.active {
    color: white;
    background-color: #337ccfe2;
    border-radius: 5px;
    font-weight: 600;
}

.nexgen-sidebar-footer {
    position: relative;
    bottom: 0;
    left: 0;
    width: 100%;
    border-top: 1px solid #d4d4d4;
    background-color: #ffffff;
    padding: 1rem;
    z-index: 100;
}

.nexgen-sidebar-footer-content {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
}

.nexgen-sidebar-footer-avatar {
    width: 50px;
    height: 50px;
    background-color: #337ccfe2;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 24px;
    color: white;
    font-weight: bold;
}

.nexgen-sidebar-footer-info b {
    display: block;
    margin-bottom: 0.25rem;
}

.nexgen-sidebar-footer-info small {
    color: lightslategray;
    display: block;
}

.nexgen-logout-btn {
    width: 75%;
    margin-top: 0.5rem;
}

@media (max-width: 768px) {
    .sidebar-toggle-btn {
        display: block;
    }

    .nexgen-sidebar {
        position: fixed;
        left: -100%;
        top: 0;
        height: 100vh;
        width: 70%;
        max-width: 300px;
        z-index: 1050;
        transition: left 0.3s ease;
        padding-bottom: 200px;
    }

    .nexgen-sidebar.show {
        left: 0;
    }

    .nexgen-sidebar-footer {
        position: relative;
        width: 70%;
        max-width: 300px;
    }

    .nexgen-sidebar-menu {
        height: calc(100vh - 240px);
    }
}

@media (max-width: 576px) {
    .nexgen-sidebar {
        width: 80%;
    }

    .nexgen-sidebar-footer {
        width: 80%;
    }

    .nexgen-sidebar-footer-content {
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .nexgen-sidebar-footer-info {
        flex: 1;
        min-width: 150px;
    }
}
</style>

<nav class="nexgen-sidebar" id="nexgenSidebar">
    <div class="nexgen-sidebar-header">
        <h3>NexGen Solution</h3>
        <p>Employee Management</p>
    </div>

    <div class="nexgen-sidebar-menu">
        <?php
        $current_page = basename($_SERVER['PHP_SELF']);
        ?>
        <h5>Employee</h5>
        <a href="admin_dashboard.php"
            class="bi bi-columns-gap <?= $current_page === 'admin_dashboard.php' ? 'active' : '' ?>">
            &nbsp;&nbsp; Dashboard</a>
        <a href="tasks.php" class="bi bi-suitcase-lg <?= $current_page === 'tasks.php' ? 'active' : '' ?>"> &nbsp;&nbsp;
            My Tasks</a>
        <a href="leave.php" class="bi bi-file-text <?= $current_page === 'leave.php' ? 'active' : '' ?>"> &nbsp;&nbsp;
            Request Leave</a>
        <a href="salary.php" class="bi bi-coin <?= $current_page === 'salary.php' ? 'active' : '' ?>"> &nbsp;&nbsp; My
            Salary</a>

        <h5 style="margin-top: 1rem;">Project Leader</h5>
        <a href="leader.php" class="bi bi-columns-gap <?= $current_page === 'leader.php' ? 'active' : '' ?>">
            &nbsp;&nbsp; Overview</a>
        <a href="tasks.php" class="bi bi-suitcase-lg <?= $current_page === 'tasks.php' ? 'active' : '' ?>"> &nbsp;&nbsp;
            Tasks Assignment</a>
        <a href="leave_view.php" class="bi bi-file-text <?= $current_page === 'leave_view.php' ? 'active' : '' ?>">
            &nbsp;&nbsp; Leave Review</a>

        <h5 style="margin-top: 1rem;">HR</h5>
        <a href="hr.php" class="bi bi-people <?= $current_page === 'hr.php' ? 'active' : '' ?>"> &nbsp;&nbsp;
            Employees</a>
        <a href="leave_view.php" class="bi bi-file-text <?= $current_page === 'leave_view.php' ? 'active' : '' ?>">
            &nbsp;&nbsp; Leave Approvals</a>
        <a href="leader_payroll.php"
            class="bi bi-currency-dollar <?= $current_page === 'leader_payroll.php' ? 'active' : '' ?>"> &nbsp;&nbsp;
            Process Payroll</a>
        <a href="inquiries_view.php"
            class="bi bi-person-circle <?= $current_page === 'inquiries_view.php' ? 'active' : '' ?>"> &nbsp;&nbsp;
            Inquiries</a>

        <h5 style="margin-top: 1rem;">Admin</h5>
        <a href="admin_user.php" class="bi bi-people <?= $current_page === 'admin_user.php' ? 'active' : '' ?>">
            &nbsp;&nbsp; System Users</a>
    </div>

    <div class="nexgen-sidebar-footer">
        <div class="nexgen-sidebar-footer-content">
            <div class="nexgen-sidebar-footer-avatar">
                <?= substr($_SESSION['name'] ?? 'User', 0, 1) ?>
            </div>
            <div class="nexgen-sidebar-footer-info">
                <b><?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></b>
                <small><?= htmlspecialchars($_SESSION['role'] ?? '') ?></small>
            </div>
        </div>
        <center>
            <a href="../public/logout.php"
                class="btn btn-outline-danger btn-sm nexgen-logout-btn bi bi-box-arrow-right">
                &nbsp; Logout
            </a>
        </center>
    </div>
</nav>

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