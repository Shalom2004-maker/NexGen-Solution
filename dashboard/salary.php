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
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Salary - NexGen Solution</title>

    <!-- Google Fonts Link -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">

    <!-- Bootstrap CSS Link -->
    <link href=" https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- CSS -->
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Oswald", sans-serif;
    }

    html,
    body {
        background-color: #ececece8;
        min-height: 100vh;
    }

    .main-wrapper {
        display: flex;
        min-height: 100vh;
    }

    .main-content {
        flex: 1;
        background-color: #f5f5f5d2;
        padding-top: 1.7rem;
        padding-left: 18rem;
        padding-right: 2rem;
        padding-bottom: 2rem;
        width: 75%;
        overflow-y: auto;
    }

    .page-header {
        margin-bottom: 2rem;
    }

    .page-header h3 {
        font-weight: bold;
        color: #333;
        margin-bottom: 0.5rem;
    }

    .page-header p {
        color: lightslategray;
        margin: 0;
    }

    .table-container {
        background-color: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        overflow-x: auto;
    }

    .table-container table {
        width: 100%;
        border-collapse: collapse;
    }

    .table-container th {
        background-color: #f8f9fa;
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #d4d4d4;
        font-weight: 600;
    }

    .table-container td {
        padding: 0.75rem;
        border-bottom: 1px solid #d4d4d4;
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
            padding: 1.5rem;
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
            font-size: 1.25rem;
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
            <?php include "admin_siderbar.php"; ?>
        </div>

        <div class="main-content">
            <div class="page-header">
                <div>
                    <h3>My Salary Slips</h3>
                    <p>View your monthly payment history and salary information</p>
                </div>
            </div>

            <div class="table-container">
                <table>
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
                        } elseif ($res->num_rows === 0) {
                            echo "<tr><td colspan=\"8\" style=\"text-align: center; padding: 2rem; color: #999;\">No salary slips found.</td></tr>";
                        } else {
                            while ($row = $res->fetch_assoc()) {
                                $month = htmlspecialchars($row['month'] ?? '');
                                $year = htmlspecialchars($row['year'] ?? '');
                                $period = ($month !== '' && $year !== '') ? "$month/$year" : htmlspecialchars($row['created_at'] ?? '');
                        ?>
                        <tr>
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const nexgenSidebar = document.getElementById('nexgenSidebar');

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
    });
    </script>
</body>

</html>