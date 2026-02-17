<?php include __DIR__ . "/../includes/sidebar_styles.php"; ?>

<nav class="nexgen-sidebar" id="nexgenSidebar">
    <div class="nexgen-sidebar-header">
        <h3>NexGen Solution</h3>
        <p>Employee Portal</p>
        <div class="theme-switcher sidebar-theme neo-panel" role="group" aria-label="Theme switcher">
            <span class="theme-switcher-label">Theme</span>
            <button class="theme-chip pressable is-active" type="button" data-theme-choice="nebula"
                aria-pressed="true">Nebula</button>
            <button class="theme-chip pressable" type="button" data-theme-choice="ember"
                aria-pressed="false">Ember</button>
            <button class="theme-chip pressable" type="button" data-theme-choice="aurora"
                aria-pressed="false">Aurora</button>
        </div>
    </div>

    <div class="nexgen-sidebar-menu">
        <?php
        $current_page = basename($_SERVER['PHP_SELF']);
        ?>
        <h5>Employee</h5>
        <a href="employee.php" class="bi bi-columns-gap <?= $current_page === 'employee.php' ? 'active' : '' ?>">
            &nbsp;&nbsp; Dashboard</a>

        <a href="tasks_dashboard.php"
            class="bi bi-suitcase-lg <?= $current_page === 'tasks_dashboard.php' ? 'active' : '' ?>">
            &nbsp;&nbsp; Tasks</a>

        <a href="leave_dashboard.php"
            class="bi bi-file-text <?= $current_page === 'leave_dashboard.php' ? 'active' : '' ?>">
            &nbsp;&nbsp; Leave Requests</a>

        <a href="salary_dashboard.php"
            class="bi bi-coin <?= $current_page === 'salary_dashboard.php' ? 'active' : '' ?>">
            &nbsp;&nbsp; Salary</a>

        <a href="profile.php" class="bi bi-person <?= $current_page === 'profile.php' ? 'active' : '' ?>">
            &nbsp;&nbsp; Profile</a>

        <a href="settings.php" class="bi bi-gear <?= $current_page === 'settings.php' ? 'active' : '' ?>">
            &nbsp;&nbsp; Settings</a>
    </div>

    <div class="nexgen-sidebar-footer">
        <div class="nexgen-sidebar-footer-content">
            <?php $avatarUrl = function_exists('sidebar_avatar_url') ? sidebar_avatar_url() : ''; ?>
            <div class="nexgen-sidebar-footer-avatar">
                <?php if ($avatarUrl): ?>
                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Profile photo"
                    style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                <?php else: ?>
                <?= substr($_SESSION['name'] ?? 'User', 0, 1) ?>
                <?php endif; ?>
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
