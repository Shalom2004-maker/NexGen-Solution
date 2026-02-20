<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexGen Solution - Employee Management</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&family=Orbitron:wght@500;700&display=swap"
        rel="stylesheet">

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/ui-universal.css" rel="stylesheet">
    <script src="../js/future-ui.js" defer></script>
</head>

<body class="future-page future-home" data-theme="nebula">
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

            <div class="theme-switcher neo-panel" role="group" aria-label="Theme switcher">
                <span class="theme-switcher-label">Theme</span>
                <button class="theme-chip pressable is-active" type="button" data-theme-choice="nebula"
                    aria-pressed="true">Nebula</button>
                <button class="theme-chip pressable" type="button" data-theme-choice="ember"
                    aria-pressed="false">Ember</button>
                <button class="theme-chip pressable" type="button" data-theme-choice="aurora"
                    aria-pressed="false">Aurora</button>
            </div>

            <a href="login.php" class="btn-nav pressable" data-tilt="8">Login</a>
        </div>
    </nav>

    <section class="hero-section">
        <div class="hero-shell glass-panel tilt-surface" data-tilt="8">
            <p class="hero-eyebrow">Intelligent Workforce Platform</p>
            <h1>Manage your team with <span class="highlight">precision</span></h1>
            <p>The all-in-one platform for task management, payroll processing, and leave tracking. Built for modern
                enterprises that value efficiency.</p>

            <div class="d-flex gap-3 mx-auto justify-content-center flex-wrap">
                <a href="login.php" class="btn-hero pressable" data-tilt="8">
                    <i class="bi bi-box-arrow-in-right"></i> Get Started
                </a>
                <a href="contact.php" class="btn-hero ghost pressable" data-tilt="8">
                    <i class="bi bi-chat-dots"></i> Talk to Sales
                </a>
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
            <p>Core services that keep your workforce operations efficient, transparent, and secure.</p>
        </div>

        <div class="features-grid">
            <article class="feature-card tilt-surface" data-tilt="8">
                <div class="feature-icon">
                    <i class="bi bi-kanban"></i>
                </div>
                <h4>Workflow Automation</h4>
                <p>Automate recurring HR and operations tasks with configurable workflows and approvals.</p>
            </article>

            <article class="feature-card tilt-surface" data-tilt="8">
                <div class="feature-icon">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <h4>Performance Insights</h4>
                <p>Turn daily activity into actionable dashboards for productivity and team performance tracking.</p>
            </article>

            <article class="feature-card tilt-surface" data-tilt="8">
                <div class="feature-icon">
                    <i class="bi bi-fingerprint"></i>
                </div>
                <h4>Access & Security</h4>
                <p>Protect sensitive records with role-based access control, secure sessions, and audit logs.</p>
            </article>
        </div>
    </section>

    <section id="solutions" class="features-section">
        <div class="section-title-block">
            <h3>Solutions</h3>
            <p>Flexible deployment options designed for teams of different sizes and operational complexity.</p>
        </div>

        <div class="features-grid">
            <article class="feature-card tilt-surface" data-tilt="8">
                <div class="feature-icon">
                    <i class="bi bi-building"></i>
                </div>
                <h4>Enterprise Suite</h4>
                <p>Centralized control for multi-department organizations with advanced approvals and governance.</p>
            </article>

            <article class="feature-card tilt-surface" data-tilt="8">
                <div class="feature-icon">
                    <i class="bi bi-diagram-3"></i>
                </div>
                <h4>Team Operations Hub</h4>
                <p>Coordinate projects, staffing, and schedules in one platform built for growing businesses.</p>
            </article>

            <article class="feature-card tilt-surface" data-tilt="8">
                <div class="feature-icon">
                    <i class="bi bi-cloud-check"></i>
                </div>
                <h4>Cloud-Ready Platform</h4>
                <p>Run securely from anywhere with browser-based access and scalable infrastructure.</p>
            </article>
        </div>
    </section>

    <section id="support" class="features-section">
        <div class="section-title-block">
            <h3>Support</h3>
            <p>Reliable assistance and resources to keep your team productive and your platform running smoothly.</p>
        </div>

        <div class="features-grid">
            <article class="feature-card tilt-surface" data-tilt="8">
                <div class="feature-icon">
                    <i class="bi bi-headset"></i>
                </div>
                <h4>Dedicated Help Desk</h4>
                <p>Get prompt assistance for technical issues, user onboarding, and platform guidance.</p>
            </article>

            <article class="feature-card tilt-surface" data-tilt="8">
                <div class="feature-icon">
                    <i class="bi bi-journal-bookmark"></i>
                </div>
                <h4>Knowledge Base</h4>
                <p>Access tutorials, troubleshooting guides, and best-practice documentation anytime.</p>
            </article>

            <article class="feature-card tilt-surface" data-tilt="8">
                <div class="feature-icon">
                    <i class="bi bi-people"></i>
                </div>
                <h4>Customer Success</h4>
                <p>Work with specialists to optimize adoption, streamline processes, and achieve business goals.</p>
            </article>
        </div>
    </section>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>

</html>
