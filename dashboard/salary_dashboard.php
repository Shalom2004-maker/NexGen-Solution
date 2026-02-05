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
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Sora", sans-serif;
    }

    html,
    body {
        background: linear-gradient(180deg, #f3f6ff 0%, #eff3f8 40%, #f7f9fc 100%);
        color: #1f2937;
        min-height: 100vh;
    }

    .main-wrapper {
        display: flex;
        min-height: 100vh;
    }

    .main-content {
        flex: 1;
        background-color: transparent;
        padding-top: 2rem;
        padding-left: 18rem;
        padding-right: 2.5rem;
        padding-bottom: 2rem;
        width: 75%;
        overflow-y: auto;
    }

    .dashboard-shell {
        position: relative;
        background: radial-gradient(1200px 400px at 20% -10%, rgba(30, 64, 175, 0.12), transparent 60%),
            radial-gradient(800px 300px at 90% 10%, rgba(14, 116, 144, 0.12), transparent 60%);
        border-radius: 20px;
        padding: 1.5rem;
        border: 1px solid rgba(148, 163, 184, 0.3);
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
    }

    .page-header {
        margin-bottom: 1.5rem;
    }

    .page-header h3 {
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.5rem;
    }

    .page-header p {
        color: #5b6777;
        margin: 0;
    }

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        border: 1px solid rgba(148, 163, 184, 0.35);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .stat-card h6 {
        color: #64748b;
        margin-bottom: 0.35rem;
        font-weight: 600;
    }

    .stat-card h3 {
        margin: 0;
        font-weight: 700;
        color: #0f172a;
    }

    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: rgba(37, 99, 235, 0.12);
        color: #2563eb;
        font-size: 1.3rem;
    }

    .table-container {
        background-color: #ffffff;
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid rgba(148, 163, 184, 0.35);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        overflow-x: auto;
    }

    .table-container table {
        width: 100%;
        border-collapse: collapse;
    }

    .table-container th {
        background-color: #f8fafc;
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #d4d4d4;
        font-weight: 600;
        color: #334155;
    }

    .table-container td {
        padding: 0.75rem;
        border-bottom: 1px solid #d4d4d4;
    }

    .table-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .table-toolbar .search-box {
        position: relative;
        max-width: 320px;
        width: 100%;
    }

    .table-toolbar .search-box input {
        padding-left: 2.25rem;
    }

    .table-toolbar .search-box i {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        pointer-events: none;
    }

    .sidebar-toggle {
        display: none;
        position: fixed;
        top: 1rem;
        left: 1rem;
        z-index: 1040;
        background-color: #337ccfe2;
        color: white;
        border: none;
        padding: 0.6rem 0.8rem;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1.25rem;
    }

    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1040;
    }

    .sidebar-overlay.show {
        display: block;
    }

    @media (max-width: 768px) {
        .main-wrapper {
            flex-direction: column;
        }

        .sidebar-toggle {
            display: block;
        }

        .main-content {
            padding: 1.25rem;
            padding-top: 3.5rem;
        }

        .table-container {
            padding: 1rem;
        }
    }

    @media (max-width: 576px) {
        .main-content {
            padding: 1rem;
            padding-top: 3rem;
        }

        .page-header h3 {
            font-size: 1.35rem;
        }

        .table-container th,
        .table-container td {
            padding: 0.5rem;
            font-size: 0.85rem;
        }
    }
    </style>
</head>

<body>
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
