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

    <!-- CSS -->
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
                        <div>
                            <h6>Total Slips</h6>
                            <h3><?= $slipCount ?></h3>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div>
                            <h6>Total Net Pay</h6>
                            <h3>$<?= number_format($totalNet, 2) ?></h3>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div>
                            <h6>Average Net</h6>
                            <h3>$<?= number_format($avgNet, 2) ?></h3>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-bar-chart-line"></i>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div>
                            <h6>Latest Slip</h6>
                            <h3><?= $latestCreated ? htmlspecialchars($latestCreated) : '-' ?></h3>
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
                            <select id="yearFilter" class="form-select" style="min-width: 160px;">
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
                            echo "<tr><td colspan=\"8\" style=\"text-align: center; padding: 2rem; color: #999;\">No salary records found.</td></tr>";
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
                noResultsRow.classList.toggle('d-none', visibleCount !== 0 || query !== '' || year !== '');
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


