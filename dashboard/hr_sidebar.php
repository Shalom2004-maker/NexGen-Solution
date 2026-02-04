<?php include __DIR__ . "/../includes/sidebar_styles.php"; ?>

<nav class="nexgen-sidebar" id="nexgenSidebar">
    <div class="nexgen-sidebar-header">
        <h3>NexGen Solution</h3>
        <p>HR Portal</p>
    </div>

    <div class="nexgen-sidebar-menu">
        <?php
        $current_page = basename($_SERVER['PHP_SELF']);
        ?>
        <h5>HR</h5>
        <a href="hr.php" class="bi bi-columns-gap <?= $current_page === 'hr.php' ? 'active' : '' ?>">
            &nbsp;&nbsp; Dashboard</a>

        <a href="hr_inquiries.php" class="bi bi-chat-left <?= $current_page === 'hr_inquiries.php' ? 'active' : '' ?>">
            &nbsp;&nbsp; Inquiries</a>

        <a href="hr_leave.php" class="bi bi-file-text <?= $current_page === 'hr_leave.php' ? 'active' : '' ?>">
            &nbsp;&nbsp; Leave Requests</a>

        <a href="hr_payroll.php" class="bi bi-coin <?= $current_page === 'hr_payroll.php' ? 'active' : '' ?>">
            &nbsp;&nbsp; Payroll</a>
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

<?php include __DIR__ . "/../includes/sidebar_scripts.php"; ?>
