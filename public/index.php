<?php
include_once "../includes/db.php";

function home_fetch_all($conn, $query)
{
    $rows = [];
    $result = $conn->query($query);

    if (!$result) {
        return $rows;
    }

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $result->free();

    return $rows;
}

function home_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function home_service_icon($categoryName)
{
    $map = [
        "cloud services" => "bi-cloud-arrow-up",
        "cybersecurity" => "bi-shield-lock",
        "data analytics" => "bi-bar-chart-line",
    ];

    $key = strtolower(trim((string) $categoryName));

    return $map[$key] ?? "bi-kanban";
}

function home_solution_icon($categoryName)
{
    $map = [
        "cloud services" => "bi-cloud-check",
        "cybersecurity" => "bi-shield-check",
        "data analytics" => "bi-graph-up-arrow",
    ];

    $key = strtolower(trim((string) $categoryName));

    return $map[$key] ?? "bi-diagram-3";
}

function home_support_icon($status)
{
    $map = [
        "open" => "bi-life-preserver",
        "in progress" => "bi-arrow-repeat",
        "resolved" => "bi-patch-check",
    ];

    $key = strtolower(trim((string) $status));

    return $map[$key] ?? "bi-headset";
}

function home_service_description($service)
{
    $categoryDescription = trim((string) ($service["CategoryDescription"] ?? ""));
    $tier = trim((string) ($service["ServiceTier"] ?? ""));
    $category = trim((string) ($service["CategoryName"] ?? ""));

    if ($categoryDescription !== "") {
        $baseDescription = rtrim($categoryDescription, ". \t\n\r\0\x0B");

        return $tier !== ""
            ? $baseDescription . " through our " . $tier . " service tier."
            : $baseDescription . ".";
    }

    if ($tier !== "" && $category !== "") {
        return $tier . " delivery for teams investing in " . strtolower($category) . ".";
    }

    return "Expert implementation and consulting tailored to your team.";
}

function home_support_description($status)
{
    $map = [
        "open" => "Fresh requests are visible immediately so your team can move from issue to action without delay.",
        "in progress" => "Active support work stays transparent, coordinated, and easy for clients to follow.",
        "resolved" => "Completed support workflows feed back into a stronger delivery process and knowledge base.",
    ];

    $key = strtolower(trim((string) $status));

    return $map[$key] ?? "Responsive support coverage that keeps projects moving confidently.";
}

function home_rate_label($rate)
{
    if ($rate === null || $rate === "") {
        return "Custom pricing";
    }

    return "$" . number_format((float) $rate, 2) . " / hr";
}

function home_date_label($dateValue)
{
    if (!$dateValue) {
        return "Recently added";
    }

    $timestamp = strtotime($dateValue);

    if ($timestamp === false) {
        return "Recently added";
    }

    return date("M j, Y", $timestamp);
}

$homeSettingKeys = [
    "home_hero_eyebrow",
    "home_hero_title",
    "home_hero_summary",
    "home_services_intro",
    "home_solutions_intro",
    "home_support_intro",
];

$homeSettingDefaults = [
    "home_hero_eyebrow" => "Intelligent Workforce Platform",
    "home_hero_title" => "Manage your team with precision",
    "home_hero_summary" => "Browse a live catalog of services, solutions, and support coverage powered by your latest published data.",
    "home_services_intro" => "Browse the current service catalog, grouped by tier and category so visitors can quickly understand what you offer.",
    "home_solutions_intro" => "Highlighted active solutions are now pulled directly from your latest published entries.",
    "home_support_intro" => "A live operational summary generated from support activity without exposing private ticket details.",
];

$homeSettings = [];
$settingsRows = home_fetch_all(
    $conn,
    "SELECT setting_key, setting_value
    FROM site_settings
    WHERE setting_key IN ('home_hero_eyebrow', 'home_hero_title', 'home_hero_summary', 'home_services_intro', 'home_solutions_intro', 'home_support_intro')"
);

foreach ($settingsRows as $row) {
    $key = (string) ($row["setting_key"] ?? "");
    if ($key !== "") {
        $homeSettings[$key] = (string) ($row["setting_value"] ?? "");
    }
}

$heroEyebrow = $homeSettings["home_hero_eyebrow"] ?? $homeSettingDefaults["home_hero_eyebrow"];
$heroTitle = $homeSettings["home_hero_title"] ?? $homeSettingDefaults["home_hero_title"];
$heroSummary = $homeSettings["home_hero_summary"] ?? $homeSettingDefaults["home_hero_summary"];
$servicesIntro = $homeSettings["home_services_intro"] ?? $homeSettingDefaults["home_services_intro"];
$solutionsIntro = $homeSettings["home_solutions_intro"] ?? $homeSettingDefaults["home_solutions_intro"];
$supportIntro = $homeSettings["home_support_intro"] ?? $homeSettingDefaults["home_support_intro"];

$services = home_fetch_all(
    $conn,
    "SELECT
        s.ServiceID,
        s.ServiceName,
        s.ServiceTier,
        s.HourlyRate,
        c.CategoryName,
        c.Description AS CategoryDescription
    FROM services s
    LEFT JOIN categories c ON c.CategoryID = s.CategoryID
    ORDER BY c.CategoryName ASC, s.ServiceName ASC
    LIMIT 6"
);

$solutions = home_fetch_all(
    $conn,
    "SELECT
        sol.SolutionID,
        sol.Title,
        sol.Description,
        sol.DateCreated,
        c.CategoryName
    FROM solutions sol
    LEFT JOIN categories c ON c.CategoryID = sol.CategoryID
    WHERE sol.IsActive = b'1'
    ORDER BY sol.DateCreated DESC, sol.Title ASC
    LIMIT 6"
);

$supportSummary = home_fetch_all(
    $conn,
    "SELECT Status, COUNT(*) AS Total
    FROM support
    GROUP BY Status
    ORDER BY FIELD(Status, 'Open', 'In Progress', 'Resolved'), Status
    LIMIT 3"
);

$summaryRows = home_fetch_all(
    $conn,
    "SELECT
        (SELECT COUNT(*) FROM services) AS service_total,
        (SELECT COUNT(*) FROM solutions WHERE IsActive = b'1') AS solution_total,
        (SELECT COUNT(*) FROM categories) AS category_total,
        (SELECT COUNT(*) FROM support) AS support_total"
);

$summary = $summaryRows[0] ?? [];
$serviceTotal = isset($summary["service_total"]) ? (int) $summary["service_total"] : count($services);
$solutionTotal = isset($summary["solution_total"]) ? (int) $summary["solution_total"] : count($solutions);
$categoryTotal = isset($summary["category_total"]) ? (int) $summary["category_total"] : 0;
$supportTotal = isset($summary["support_total"]) ? (int) $summary["support_total"] : 0;

$heroMetrics = [
    ["value" => $serviceTotal, "label" => "Service Lines"],
    ["value" => $solutionTotal, "label" => "Active Solutions"],
    ["value" => $categoryTotal, "label" => "Categories"],
    ["value" => $supportTotal, "label" => "Support Records"],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexGen Solution - Employee Management</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@400;600;700&display=swap"
        rel="stylesheet">

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/colors.css" rel="stylesheet">
    <link href="../css/theme.css" rel="stylesheet">
    <link href="../css/components.css" rel="stylesheet">
    <link href="../css/ui-universal.css" rel="stylesheet">
    <script src="../js/future-ui.js" defer></script>
</head>

<body class="future-page future-home dark" data-theme="dark">
    <div class="future-grid" aria-hidden="true"></div>
    <div class="future-orb future-orb-a" aria-hidden="true"></div>
    <div class="future-orb future-orb-b" aria-hidden="true"></div>
    <div class="future-orb future-orb-c" aria-hidden="true"></div>

    <nav class="navbar navbar-section">
        <a href="#" class="navbar-brand">
            <img src="../assets/logos/nexgen-brand-logo.png" alt="NexGen Logo">
            <span>NexGen Solution</span>
        </a>

        <div class="navbar-actions">
            <a href="#services" class="nav-link">Services</a>
            <a href="#solutions" class="nav-link">Solutions</a>
            <a href="#support" class="nav-link">Support</a>
            <a href="contact.php" class="nav-link">Contact Sales</a>

            <details class="home-nav-menu">
                <summary class="nav-menu-toggle pressable">
                    <i class="bi bi-list" aria-hidden="true"></i>
                    <span>Menu</span>
                </summary>
                <div class="home-nav-drawer">
                    <a href="#services" class="mobile-nav-link">Services</a>
                    <a href="#solutions" class="mobile-nav-link">Solutions</a>
                    <a href="#support" class="mobile-nav-link">Support</a>
                    <a href="contact.php" class="mobile-nav-link">Contact Sales</a>
                </div>
            </details>

            <div class="theme-switcher mx-2 ms-2 me-2" role="group" aria-label="Theme toggle">
                <button class="theme-chip pressable" type="button" data-theme-toggle data-icon-light="bi-sun-fill"
                    data-icon-dark="bi-moon-fill" aria-label="Toggle theme" aria-pressed="true">
                    <i class="bi bi-moon-fill" aria-hidden="true"></i>
                </button>
            </div>

            <a href="login.php" class="btn-nav pressable" data-tilt="8">Login</a>
        </div>
    </nav>

    <section class="hero-section">
        <div class="hero-shell glass-panel tilt-surface" data-tilt="8">
            <video class="hero-background-video" autoplay muted loop playsinline preload="metadata"
                poster="../assets/svgs/teamwork1.avif" aria-hidden="true" disablepictureinpicture>
                <source src="../assets/logos/3D_abstract_human.mp4" type="video/mp4">
            </video>

            <div class="hero-content">
                <div class="hero-copy">
                    <span class="hero-eyebrow">
                        <i class="bi bi-stars" aria-hidden="true"></i>
                        <?= home_escape($heroEyebrow) ?>
                    </span>
                    <h1><?= home_escape($heroTitle) ?></h1>
                    <p><?= home_escape($heroSummary) ?></p>
                </div>

                <div class="hero-actions d-flex gap-3 mx-auto justify-content-center flex-wrap">
                    <a href="login.php" class="btn-hero pressable" data-tilt="8">
                        <i class="bi bi-box-arrow-in-right"></i> Get Started
                    </a>
                    <a href="contact.php" class="btn-hero ghost pressable" data-tilt="8">
                        <i class="bi bi-chat-dots"></i> Talk to Sales
                    </a>
                </div>

                <div class="hero-metrics" role="list" aria-label="Homepage content snapshot">
                    <?php foreach ($heroMetrics as $metric): ?>
                    <div class="hero-metric glass-panel" role="listitem">
                        <strong><?= home_escape($metric["value"]) ?></strong>
                        <span><?= home_escape($metric["label"]) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="features-section">
        <div class="section-title-block">
            <h3>Futuristic Control Center Features</h3>
            <p>Glass-structured layouts for clarity and neumorphic controls for tactile interaction.</p>
        </div>

        <div class="features-grid">
            <article class="feature-card tilt-surface" data-tilt="8">
                <div class="feature-icon">
                    <i class="bi bi-list-check"></i>
                </div>
                <h4>Task Management</h4>
                <p>Assign, track, and complete tasks with real-time updates and seamless collaboration tools.</p>
            </article>

            <article class="feature-card tilt-surface" data-tilt="8">
                <div class="feature-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h4>Secure Payroll</h4>
                <p>Automated payroll processing with deduction management and salary slip generation.</p>
            </article>

            <article class="feature-card tilt-surface" data-tilt="8">
                <div class="feature-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <h4>Leave Tracking</h4>
                <p>Streamlined leave request and approval workflows for HR and team leaders.</p>
            </article>
        </div>
    </section>

    <section id="services" class="features-section">
        <div class="section-title-block">
            <h3>Services</h3>
            <p><?= home_escape($servicesIntro) ?></p>
        </div>

        <div class="features-grid">
            <?php if (!empty($services)): ?>
            <?php foreach ($services as $service): ?>
            <article class="feature-card tilt-surface" data-tilt="8">
                <div class="feature-icon">
                    <i class="bi <?= home_escape(home_service_icon($service["CategoryName"] ?? "")) ?>"></i>
                </div>
                <h4><?= home_escape($service["ServiceName"] ?? "Service") ?></h4>
                <p><?= home_escape(home_service_description($service)) ?></p>
                <div class="feature-meta">
                    <?php if (!empty($service["CategoryName"])): ?>
                    <span class="data-chip"><?= home_escape($service["CategoryName"]) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($service["ServiceTier"])): ?>
                    <span class="data-chip"><?= home_escape($service["ServiceTier"]) ?></span>
                    <?php endif; ?>
                    <span class="data-chip"><?= home_escape(home_rate_label($service["HourlyRate"] ?? null)) ?></span>
                </div>
            </article>
            <?php endforeach; ?>
            <?php else: ?>
            <article class="feature-card feature-card-empty tilt-surface" data-tilt="8">
                <div>
                    <div class="feature-icon">
                        <i class="bi bi-stars"></i>
                    </div>
                    <h4>Services Coming Soon</h4>
                    <p>Your service catalog will appear here automatically as soon as records are available.</p>
                </div>
                <div class="feature-meta">
                    <span class="data-chip">Dynamic-ready</span>
                </div>
            </article>
            <?php endif; ?>
        </div>
    </section>

    <section id="solutions" class="features-section">
        <div class="section-title-block">
            <h3>Solutions</h3>
            <p><?= home_escape($solutionsIntro) ?></p>
        </div>

        <div class="features-grid">
            <?php if (!empty($solutions)): ?>
            <?php foreach ($solutions as $solution): ?>
            <article class="feature-card tilt-surface" data-tilt="8">
                <div class="feature-icon">
                    <i class="bi <?= home_escape(home_solution_icon($solution["CategoryName"] ?? "")) ?>"></i>
                </div>
                <h4><?= home_escape($solution["Title"] ?? "Solution") ?></h4>
                <p><?= home_escape($solution["Description"] ?? "A published solution summary will appear here.") ?></p>
                <div class="feature-meta">
                    <?php if (!empty($solution["CategoryName"])): ?>
                    <span class="data-chip"><?= home_escape($solution["CategoryName"]) ?></span>
                    <?php endif; ?>
                    <span class="data-chip"><?= home_escape(home_date_label($solution["DateCreated"] ?? "")) ?></span>
                    <span class="data-chip">Active</span>
                </div>
            </article>
            <?php endforeach; ?>
            <?php else: ?>
            <article class="feature-card feature-card-empty tilt-surface" data-tilt="8">
                <div>
                    <div class="feature-icon">
                        <i class="bi bi-cloud-check"></i>
                    </div>
                    <h4>Solutions Will Publish Here</h4>
                    <p>Once active solution entries are available, this section will keep itself updated.</p>
                </div>
                <div class="feature-meta">
                    <span class="data-chip">Auto-updating</span>
                </div>
            </article>
            <?php endif; ?>
        </div>
    </section>

    <section id="support" class="features-section">
        <div class="section-title-block">
            <h3>Support Snapshot</h3>
            <p><?= home_escape($supportIntro) ?></p>
        </div>

        <div class="features-grid">
            <?php if (!empty($supportSummary)): ?>
            <?php foreach ($supportSummary as $supportItem): ?>
            <article class="feature-card tilt-surface" data-tilt="8">
                <div class="feature-icon">
                    <i class="bi <?= home_escape(home_support_icon($supportItem["Status"] ?? "")) ?>"></i>
                </div>
                <h4><?= home_escape($supportItem["Status"] ?? "Support") ?></h4>
                <p><?= home_escape(home_support_description($supportItem["Status"] ?? "")) ?></p>
                <div class="feature-meta">
                    <span class="data-chip"><?= home_escape($supportItem["Total"] ?? 0) ?> tracked items</span>
                    <span class="data-chip">Live summary</span>
                </div>
            </article>
            <?php endforeach; ?>
            <?php else: ?>
            <article class="feature-card feature-card-empty tilt-surface" data-tilt="8">
                <div>
                    <div class="feature-icon">
                        <i class="bi bi-headset"></i>
                    </div>
                    <h4>Support Visibility</h4>
                    <p>As your support workflow grows, this area can surface live operational highlights for visitors.
                    </p>
                </div>
                <div class="feature-meta">
                    <span class="data-chip">Privacy-safe</span>
                </div>
            </article>
            <?php endif; ?>
        </div>
    </section>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>

</html>