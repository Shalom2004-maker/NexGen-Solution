<?php
include "../includes/auth.php";
allow("Employee");
include "../includes/db.php";

$uid = intval($_SESSION["uid"]);

$stmt = $conn->prepare("SELECT s.* FROM salary_slips s 
                   JOIN employees e ON s.employee_id=e.id 
                   WHERE e.user_id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
}

$slipCount = count($rows);
$totalNet = 0.0;
$latestCreated = null;
$years = [];
foreach ($rows as $r) {
    $totalNet += (float)($r['net_salary'] ?? 0);
    if (!empty($r['created_at'])) {
        if ($latestCreated === null || strtotime($r['created_at']) > strtotime($latestCreated)) {
            $latestCreated = $r['created_at'];
        }
    }
    if (!empty($r['year'])) {
        $years[(string)$r['year']] = true;
    }
}
ksort($years);
$yearList = array_keys($years);
$avgNet = $slipCount > 0 ? $totalNet / $slipCount : 0;
$latestCreatedDisplay = $latestCreated ? date('M d, Y', strtotime($latestCreated)) : 'No slips yet';
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Dashboard - NexGen Solution</title>

    <!-- Google Fonts Link -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@200..800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS Link -->
    <link href=" https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link href="../css/colors.css" rel="stylesheet">
    <link href="../css/theme.css" rel="stylesheet">
    <link href="../css/components.css" rel="stylesheet">
    <link href="../css/ui-universal.css" rel="stylesheet">

    <style>
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-card {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: flex-start;
        padding: 1.25rem;
        border: 1px solid hsl(var(--border) / 0.72);
        border-radius: 1rem;
        background: hsl(var(--card));
        box-shadow: var(--shadow-sm);
    }

    .stat-copy {
        min-width: 0;
    }

    .stat-label {
        margin: 0 0 0.35rem;
        color: var(--muted-text);
        font-size: 0.9rem;
        font-weight: 600;
    }

    .stat-value {
        margin: 0;
        font-size: clamp(1.4rem, 2vw, 1.8rem);
        font-weight: 700;
        color: var(--text);
        word-break: break-word;
    }

    .stat-note {
        display: block;
        margin-top: 0.45rem;
        color: var(--muted-text);
        font-size: 0.82rem;
    }

    .stat-icon {
        width: 3rem;
        height: 3rem;
        border-radius: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: hsl(var(--primary) / 0.14);
        color: var(--accent-color);
        flex-shrink: 0;
    }
    </style>
</head>

<body class="future-page future-dashboard" data-theme="dark">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <button class="sidebar-toggle" id="sidebarToggleBtn" type="button">
        <i class="bi bi-list"></i>
    </button>

    <div class="main-wrapper">
        <div id="sidebarContainer">
            <?php include "../includes/sidebar_helper.php"; render_sidebar(); ?>
        </div>

        <div class="main-content">
            <div class="dashboard-shell">
                <div class="page-header">
                    <div>
                        <h3>Salary Management</h3>
                        <p>View your salary records and slips</p>
                    </div>
                </div>

                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-copy">
                            <p class="stat-label">Total Slips</p>
                            <p class="stat-value"><?= $slipCount ?></p>
                            <small class="stat-note">Salary records available in your account</small>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-copy">
                            <p class="stat-label">Total Net Pay</p>
                            <p class="stat-value">$<?= number_format($totalNet, 2) ?></p>
                            <small class="stat-note">Combined net salary across all slips</small>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-copy">
                            <p class="stat-label">Average Net</p>
                            <p class="stat-value">$<?= number_format($avgNet, 2) ?></p>
                            <small class="stat-note">Average take-home pay per salary slip</small>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-bar-chart-line"></i>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-copy">
                            <p class="stat-label">Latest Slip</p>
                            <p class="stat-value"><?= htmlspecialchars($latestCreatedDisplay) ?></p>
                            <small class="stat-note">Most recent generated salary slip date</small>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-toolbar">
                        <div>
                            <h6 class="mb-0">Salary Slips</h6>
                            <small class="text-muted">Track net salary, bonus and deductions</small>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <div class="search-box">
                                <i class="bi bi-search"></i>
                                <input id="salarySearch" type="text" class="form-control"
                                    placeholder="Search by period or amount">
                            </div>
                            <select id="yearFilter" class="form-select year-filter">
                                <option value="">All Years</option>
                                <?php foreach ($yearList as $y): ?>
                                <option value="<?= htmlspecialchars($y) ?>"><?= htmlspecialchars($y) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Period</th>
                                <th>Base Salary</th>
                                <th>Overtime Pay</th>
                                <th>Bonus</th>
                                <th>Deductions</th>
                                <th>Net Salary</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                        if ($res === false) {
                            echo "<tr><td colspan=\"8\">Query error: " . htmlspecialchars($stmt->error) . "</td></tr>";
                        } elseif ($slipCount === 0) {
                            echo "<tr><td colspan=\"8\" class=\"text-center empty-table-message\">No salary records found.</td></tr>";
                        } else {
                            foreach ($rows as $row) {
                                $month = htmlspecialchars($row['month'] ?? '');
                                $year = htmlspecialchars($row['year'] ?? '');
                                $period = ($month !== '' && $year !== '') ? "$month/$year" : htmlspecialchars($row['created_at'] ?? '');
                        ?>
                            <tr data-salary-row="1" data-year="<?= htmlspecialchars($row['year'] ?? '') ?>">
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= $period ?></td>
                                <td><?= htmlspecialchars($row['base_salary'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['overtime_pay'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['bonus'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['deductions'] ?? '-') ?></td>
                                <td><strong><?= htmlspecialchars($row['net_salary'] ?? '-') ?></strong></td>
                                <td><?= htmlspecialchars($row['created_at'] ?? '-') ?></td>
                            </tr>
                            <?php }
                        }
                        ?>
                            <tr id="salaryNoResultsRow" class="d-none">
                                <td colspan="8" class="text-center text-muted py-4">No matching salary records found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const nexgenSidebar = document.getElementById('nexgenSidebar');
        const salarySearch = document.getElementById('salarySearch');
        const yearFilter = document.getElementById('yearFilter');
        const salaryRows = document.querySelectorAll('tr[data-salary-row="1"]');
        const noResultsRow = document.getElementById('salaryNoResultsRow');

        if (sidebarToggleBtn && nexgenSidebar) {
            sidebarToggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                nexgenSidebar.classList.toggle('show');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.toggle('show');
                }
            });
        }

        if (sidebarOverlay && nexgenSidebar) {
            sidebarOverlay.addEventListener('click', function() {
                nexgenSidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
        }

        if (nexgenSidebar) {
            document.querySelectorAll('.nexgen-sidebar-menu a').forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        nexgenSidebar.classList.remove('show');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.remove('show');
                        }
                    }
                });
            });
        }

        function filterSalaryRows() {
            const query = salarySearch ? salarySearch.value.toLowerCase().trim() : '';
            const year = yearFilter ? yearFilter.value : '';
            let visibleCount = 0;
            salaryRows.forEach(row => {
                const matchesQuery = row.textContent.toLowerCase().includes(query);
                const matchesYear = !year || row.getAttribute('data-year') === year;
                const show = matchesQuery && matchesYear;
                row.classList.toggle('d-none', !show);
                if (show) visibleCount += 1;
            });
            if (noResultsRow) {
                noResultsRow.classList.toggle('d-none', visibleCount !== 0);
            }
        }

        if (salarySearch) {
            salarySearch.addEventListener('input', filterSalaryRows);
        }

        if (yearFilter) {
            yearFilter.addEventListener('change', filterSalaryRows);
        }
    });
    </script>
</body>

</html>


