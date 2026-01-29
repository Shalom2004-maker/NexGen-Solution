<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexGen Solution - Employee Management</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Oswald", sans-serif;
    }

    html,
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        color: #333;
    }

    /* Navbar */
    .navbar-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .5rem 2rem;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(12px);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .navbar-brand {
        font-size: 1.5rem;
        font-weight: bold;
        color: #337ccfe2;
        text-decoration: none;
    }

    .navbar-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .nav-link {
        color: #333;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    a.navbar-brand,
    img {
        width: 70px;
        height: 70px;
    }

    .nav-link:hover {
        color: #337ccfe2;
    }

    .btn-nav {
        background-color: #337ccfe2;
        color: white;
        padding: 0.6rem 1.5rem;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .btn-nav:hover {
        background-color: #2563a8;
        color: white;
        transform: translateY(-2px);
    }

    /* Hero Section */
    .hero-section {
        padding: 4rem 2rem;
        text-align: center;
        color: white;
        min-height: 60vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .hero-section h1 {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 1.5rem;
        line-height: 1.2;
    }

    .hero-section .highlight {
        color: #ffd700;
    }

    .hero-section p {
        font-size: 1.2rem;
        margin-bottom: 2rem;
        max-width: 600px;
        color: rgba(255, 255, 255, 0.9);
    }

    .btn-primary-large {
        background-color: #ffd700;
        color: #333;
        padding: 1rem 2.5rem;
        border-radius: 50px;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .btn-primary-large:hover {
        background-color: #ffed4e;
        transform: scale(1.05);
        color: #333;
    }

    /* Features Section */
    .features-section {
        padding: 4rem 2rem;
        background-color: white;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .feature-card {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        border: 2px solid rgba(51, 124, 207, 0.2);
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s ease;
    }

    .feature-card:hover {
        transform: translateY(-8px);
        border-color: #337ccfe2;
        box-shadow: 0 10px 30px rgba(51, 124, 207, 0.2);
    }

    .feature-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        color: white;
        font-size: 2rem;
    }

    .feature-card h4 {
        font-size: 1.3rem;
        font-weight: bold;
        margin-bottom: 1rem;
        color: #333;
    }

    .feature-card p {
        color: lightslategray;
        line-height: 1.6;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .navbar-section {
            padding: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .navbar-brand {
            font-size: 1.2rem;
            flex: 1;
            min-width: 200px;
        }

        .navbar-actions {
            gap: 0.5rem;
            width: 100%;
            justify-content: flex-end;
        }

        .nav-link {
            font-size: 0.9rem;
        }

        .btn-nav {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .hero-section {
            padding: 2.5rem 1.5rem;
        }

        .hero-section h1 {
            font-size: 2rem;
        }

        .hero-section p {
            font-size: 1rem;
        }

        .features-section {
            padding: 2.5rem 1.5rem;
        }

        .features-grid {
            gap: 1.5rem;
        }
    }

    @media (max-width: 576px) {
        .navbar-section {
            flex-direction: column;
            align-items: center;
            padding: 1rem 0.5rem;
        }

        .navbar-brand {
            font-size: 1rem;
        }

        .navbar-actions {
            width: 100%;
            justify-content: center;
        }

        .nav-link {
            display: none;
        }

        .hero-section {
            padding: 2rem 1rem;
            min-height: 50vh;
        }

        .hero-section h1 {
            font-size: 1.5rem;
        }

        .hero-section p {
            font-size: 0.95rem;
        }

        .btn-primary-large {
            padding: 0.8rem 1.8rem;
            font-size: 0.95rem;
        }

        .features-section {
            padding: 2rem 1rem;
        }

        .feature-card {
            padding: 1.5rem;
        }

        .feature-card h4 {
            font-size: 1.1rem;
        }

        .feature-card p {
            font-size: 0.9rem;
        }
    }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar-section">
        <a href="#" class="navbar-brand">
            <img src="../assets/logos/nexgen-brand-logo.png" alt="NexGen Logo"> &nbsp; NexGen Solution
        </a>
        <div class="navbar-actions">
            <a href="contact.php" class="nav-link">Contact Sales</a>
            <a href="login.php" class="btn-nav">Login</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <h1>Manage your team with <span class="highlight">precision</span></h1>
        <p>The all-in-one platform for task management, payroll processing, and leave tracking. Built for modern
            enterprises that value efficiency.</p>
        <a href="login.php" class="btn-primary-large">
            Get Started
            <i class="bi bi-arrow-right"></i>
        </a>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-list-check"></i>
                </div>
                <h4>Task Management</h4>
                <p>Assign, track, and complete tasks with real-time updates and seamless collaboration tools.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h4>Secure Payroll</h4>
                <p>Automated payroll processing with deduction management and salary slip generation.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <h4>Leave Tracking</h4>
                <p>Streamlined leave request and approval workflows for HR and team leaders.</p>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>