<?php
include "../includes/auth.php";
allow(["Employee", "ProjectLeader", "HR", "Admin"]);
include "../includes/db.php";
include "../includes/sidebar_helper.php";

$uid = (int)($_SESSION["uid"] ?? 0);

$data = [];
$profileMissing = true;
$stmt = $conn->prepare("SELECT u.full_name, u.email, u.profile_photo, u.created_at,
                        e.job_title, e.department, e.hire_date, e.salary_base
                        FROM users u
                        LEFT JOIN employees e ON u.id = e.user_id
                        WHERE u.id = ?
                        LIMIT 1");

if ($stmt) {
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $q = $stmt->get_result();
    $data = $q->fetch_assoc() ?: [];
    $stmt->close();
    $profileMissing = !$data;
}

$fullName = trim((string)($data["full_name"] ?? ""));
$nameInitial = strtoupper(substr($fullName !== "" ? $fullName : "U", 0, 1));
$photoUrl = resolve_avatar_url($data['profile_photo'] ?? '');

$memberSince = "Not available";
if (!empty($data["created_at"])) {
    $joinedTs = strtotime((string)$data["created_at"]);
    if ($joinedTs !== false) {
        $memberSince = date("Y", $joinedTs);
    }
} elseif (!empty($data["hire_date"])) {
    $hireTs = strtotime((string)$data["hire_date"]);
    if ($hireTs !== false) {
        $memberSince = date("Y", $hireTs);
    }
}

$hireDateDisplay = "Not available";
if (!empty($data["hire_date"])) {
    $hireTs = strtotime((string)$data["hire_date"]);
    $hireDateDisplay = $hireTs !== false ? date("M d, Y", $hireTs) : (string)$data["hire_date"];
}

$salaryBaseDisplay = "Not available";
if (isset($data["salary_base"]) && $data["salary_base"] !== null && $data["salary_base"] !== "") {
    $salaryBaseDisplay = "$" . number_format((float)$data["salary_base"], 2);
}

$flashSuccess = (string)($_SESSION["success"] ?? "");
$flashError = (string)($_SESSION["error"] ?? "");
unset($_SESSION["success"], $_SESSION["error"]);

$display = static function ($value): string {
    $value = trim((string)$value);
    return $value !== "" ? htmlspecialchars($value) : "Not available";
};
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - NexGen Solution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@200..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/bootstrap.css">
    <link rel="stylesheet" href="/bootstrap-icons/font/bootstrap-icons.min.css">
    <script src="/js/bootstrap.bundle.js"></script>
    <style>
    .profile-header-card {
        border: 1px solid hsl(var(--border) / 0.72);
        border-radius: 1rem;
        overflow: hidden;
        background: hsl(var(--card));
        box-shadow: var(--shadow-sm);
    }

    .profile-cover {
        min-height: 170px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, hsl(var(--primary) / 0.2), hsl(var(--secondary) / 0.18));
        border-bottom: 1px solid hsl(var(--border) / 0.72);
    }

    .profile-cover img {
        width: min(260px, 70%);
        height: auto;
        opacity: 0.88;
    }

    .profile-header-content {
        text-align: center;
        padding: 0 1.25rem 1.5rem;
    }

    .profile-avatar-wrapper {
        width: 10rem;
        height: 10rem;
        margin: -4rem auto 1rem;
        position: relative;
    }

    .profile-avatar,
    .profile-avatar-fallback {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        border: 4px solid hsl(var(--card));
        box-shadow: var(--shadow-md);
    }

    .profile-avatar {
        object-fit: cover;
        background: hsl(var(--card));
    }

    .profile-avatar-fallback {
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, hsl(var(--primary) / 0.55), hsl(var(--secondary) / 0.55));
        color: hsl(var(--primary-foreground));
        font-size: 2.5rem;
        font-weight: 700;
    }

    .btn-camera {
        position: absolute;
        right: .25rem;
        bottom: .25rem;
        width: 2.35rem;
        height: 2.35rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid hsl(var(--border) / 0.72);
        background: hsl(var(--card));
        color: var(--text);
        text-decoration: none;
        box-shadow: var(--shadow-sm);
    }

    .btn-camera:hover {
        color: var(--text);
        background: hsl(var(--primary) / 0.14);
    }

    .profile-badges {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: .55rem;
    }

    .profile-badges .badge {
        padding: .5rem .85rem;
        font-weight: 600;
    }

    .profile-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: .7rem;
    }

    .profile-actions .btn {
        min-width: 170px;
    }

    .profile-section-card {
        border: 1px solid hsl(var(--border) / 0.72);
        border-radius: 1rem;
        padding: 1.25rem;
        background: hsl(var(--card));
        box-shadow: var(--shadow-xs);
    }

    .profile-info-card {
        height: 100%;
        display: flex;
        align-items: center;
        gap: .85rem;
        padding: 1rem;
        border-radius: .9rem;
        border: 1px solid hsl(var(--border) / 0.65);
        background: hsl(var(--glass-bg));
    }

    .profile-info-icon {
        width: 2.7rem;
        height: 2.7rem;
        border-radius: .75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: hsl(var(--primary) / 0.2);
        color: var(--accent-color);
        flex-shrink: 0;
    }

    .profile-info-icon i {
        margin: 0;
        font-size: 1.15rem;
    }

    .profile-detail-label {
        margin: 0;
        color: var(--muted-text);
        font-size: .84rem;
        font-weight: 600;
    }

    .profile-detail-value {
        margin: .1rem 0 0;
        color: var(--text);
        font-weight: 700;
        word-break: break-word;
    }

    @media (max-width: 768px) {
        .profile-avatar-wrapper {
            width: 8.5rem;
            height: 8.5rem;
            margin-top: -3.4rem;
        }

        .profile-actions .btn {
            width: 100%;
            min-width: 0;
        }
    }
    </style>
</head>

<body class="future-page future-dashboard" data-theme="dark">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <button class="sidebar-toggle" id="sidebarToggleBtn" type="button" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>

    <div class="main-wrapper">
        <div id="sidebarContainer">
            <?php render_sidebar(); ?>
        </div>

        <div class="main-content">
            <div class="dashboard-shell">
                <div class="page-header">
                    <div>
                        <h3>Profile</h3>
                        <p>View your account details and identity information.</p>
                    </div>
                    <a href="settings.php" class="btn btn-primary-custom rounded-pill px-4">
                        <i class="bi bi-gear me-2"></i>Manage Settings
                    </a>
                </div>

                <?php if ($flashSuccess !== "") : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Success!</strong> <?= htmlspecialchars($flashSuccess) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if ($flashError !== "") : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> <?= htmlspecialchars($flashError) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if ($profileMissing) : ?>
                <div class="alert alert-warning" role="alert">
                    Profile details are incomplete for this account. Basic account info is shown where available.
                </div>
                <?php endif; ?>

                <div class="profile-header-card mb-4">
                    <div class="profile-cover">
                        <img src="../assets/logos/nexgen-brand-logo.png" alt="NexGen brand logo">
                    </div>

                    <div class="profile-header-content">
                        <div class="profile-avatar-wrapper">
                            <?php if ($photoUrl): ?>
                            <img src="<?= htmlspecialchars($photoUrl) ?>" class="profile-avatar" alt="Profile photo">
                            <?php else: ?>
                            <div class="profile-avatar-fallback"><?= htmlspecialchars($nameInitial) ?></div>
                            <?php endif; ?>

                            <a href="settings.php" class="btn-camera rounded-circle" title="Change Profile Picture"
                                aria-label="Change profile picture">
                                <i class="bi bi-camera"></i>
                            </a>
                        </div>

                        <h3 class="fw-bold mb-1"><?= $display($fullName) ?></h3>
                        <p class="text-muted mb-3 d-flex justify-content-center align-items-center gap-2">
                            <i class="bi bi-envelope-fill"></i>
                            <span class="text-break"><?= $display($data["email"] ?? "") ?></span>
                        </p>

                        <div class="profile-badges mb-3">
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">
                                <i class="bi bi-calendar-event me-1"></i> Member since
                                <?= htmlspecialchars($memberSince) ?>
                            </span>
                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">
                                <i class="bi bi-patch-check me-1"></i> Verified Account
                            </span>
                        </div>

                        <div class="profile-actions mt-3">
                            <a href="settings.php" class="btn btn-dark rounded-pill px-4 shadow-sm">
                                <i class="bi bi-pencil me-2"></i>Edit Profile
                            </a>
                            <a href="settings.php" class="btn btn-outline-dark rounded-pill px-4">
                                <i class="bi bi-key me-2"></i>Change Password
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="profile-section-card">
                            <h4 class="section-title mb-3">Personal Information</h4>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="profile-info-card">
                                        <div class="profile-info-icon">
                                            <i class="bi bi-person-fill"></i>
                                        </div>
                                        <div>
                                            <p class="profile-detail-label">Full Name</p>
                                            <p class="profile-detail-value"><?= $display($fullName) ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="profile-info-card">
                                        <div class="profile-info-icon">
                                            <i class="bi bi-envelope-fill"></i>
                                        </div>
                                        <div>
                                            <p class="profile-detail-label">Email Address</p>
                                            <p class="profile-detail-value"><?= $display($data["email"] ?? "") ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="profile-info-card">
                                        <div class="profile-info-icon">
                                            <i class="bi bi-person-workspace"></i>
                                        </div>
                                        <div>
                                            <p class="profile-detail-label">Job Title</p>
                                            <p class="profile-detail-value"><?= $display($data["job_title"] ?? "") ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="profile-info-card">
                                        <div class="profile-info-icon">
                                            <i class="bi bi-building"></i>
                                        </div>
                                        <div>
                                            <p class="profile-detail-label">Department</p>
                                            <p class="profile-detail-value"><?= $display($data["department"] ?? "") ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="profile-info-card">
                                        <div class="profile-info-icon">
                                            <i class="bi bi-calendar3"></i>
                                        </div>
                                        <div>
                                            <p class="profile-detail-label">Hire Date</p>
                                            <p class="profile-detail-value"><?= htmlspecialchars($hireDateDisplay) ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="profile-info-card">
                                        <div class="profile-info-icon">
                                            <i class="bi bi-currency-dollar"></i>
                                        </div>
                                        <div>
                                            <p class="profile-detail-label">Salary Base</p>
                                            <p class="profile-detail-value"><?= htmlspecialchars($salaryBaseDisplay) ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>