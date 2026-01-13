<!DOCTYPE html>
<html>

<head>
    <title>NexGen Solution</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Osward", sans-serif;
    }

    html,
    body {
        background-color: #ececece8;
    }

    .container {
        width: 100%;
        height: fit-content;
    }

    .navbar-panel {
        width: 87%;
        height: 12vh;
        position: fixed;
        display: flex;
        margin-top: 0;
        top: 0;
        justify-content: space-between;
        backdrop-filter: blur(12px) saturate(120%);
        -webkit-backdrop-filter: blur(12px) saturate(120%);
        background-color: rgba(229, 230, 231, 0.5);
        border-bottom: 1px solid rgba(255, 255, 255, 0.125);
    }

    .nav-container {
        border-bottom: 2px lightslategray;
    }

    span.navbar-panel-brand {
        font-size: 1.5rem;
        margin-left: 4.5rem;
        margin-top: 0.8rem;
    }

    span.navbar-panel-brand,
    a.btn-l {
        text-decoration: none;
        font-weight: bold;
    }

    .hero {
        color: #111;
        margin-top: 3rem;
        text-align: center;
    }

    h1 {
        font-weight: 800;
        font-size: 60px;
    }

    h4 {
        font-weight: bold;
        font-size: large;
        margin-left: 1.5rem;
        margin-right: 1.5rem;
    }

    /* Section Container */
    a,
    a.button {
        text-decoration: none;
        font-weight: bold;
        font-size: 17px;
    }

    button[type="button"] {
        width: 5.5rem;
        height: 2.5rem;
        border-radius: 10px;
        background-color: #337ccfe2;
        border: 1px white;
        margin-right: 3rem;
        box-shadow: 0 0 10px;
    }

    button[type="simple"] {
        width: 11rem;
        height: 3.2rem;
        display: inline-flex;
        justify-content: center;
        margin-top: 2rem;
        align-items: center;
        box-shadow: 2px 2px 15px solid #337ccfe2;
        border: none;
        border-radius: 45px;
    }

    button:hover {
        background-color: #3793dfe2;
        transform: scale(1.03, 1.03);
        transition: .3s;
    }

    .parent {
        display: grid;
        height: 67vh;
        grid-template-columns: repeat(5, 1fr);
        grid-template-rows: repeat(5, 1fr);
        gap: 4.5rem;
    }

    .div1,
    .div2,
    .div3 {
        width: 25vw;
        margin-top: 5rem;
        backdrop-filter: blur(12px) saturate(120%);
        -webkit-backdrop-filter: blur(12px) saturate(120%);
        background-color: rgba(255, 255, 255, 0.8);
        border: 3px lightslategray;
        border-radius: 17px;
        box-shadow: 0 0 10px lightslategray;
        margin-bottom: 3rem;
    }

    .div1:hover {
        transform: scale(1.03, 1.09);
        transition: .4s;
    }

    .div2:hover {
        transform: scale(1.03, 1.09);
        transition: .4s;
    }

    .div3:hover {
        transform: scale(1.03, 1.09);
        transition: .4s;
    }

    .section-paragraph {
        color: lightslategray;
        text-align: start;
        margin-left: 1.5rem;
        margin-right: 1.5rem;
        margin-top: 1rem;
        margin-bottom: 1.5rem;
    }
    </style>
</head>

<body>

    <div class="container">
        <nav class="navbar-panel">
            <span class="navbar-panel-brand">NexGen Solution</span>
            <div class="nav-container">
                <a href="contact.php" class="btn-l"
                    style="margin-right: 1.5rem; color: black; margin-top: .8rem;">Contact
                    Sales</a>
                <button type="button" style="color: #337ccfe2; margin-top: .8rem;">
                    <a href=" login.php" class="btn-l" style="color: white;">Login</a>
                </button>
            </div>
        </nav>

        <div class="line">
            <br><br><br><br><br>
        </div>
        <div class="hero">
            <h1>Manage your team with <font style="color: #337ccfe2;"><br>precision</font>
            </h1>
            <p style="font-size: 21px; margin-top: 1rem; color: lightslategray;">The all-in-one platform for task
                management,
                payroll
                processing, and leave <br>tracking. Built for modern enterprises that value efficiency.</p>
            <button type="simple" style="background-color: #337ccfe2; margin-right: 1rem;">
                <a href="login.php" class="button" style="color: white;">Get Started </a>&nbsp;&nbsp;<svg
                    xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                    fill="#e3e3e3">
                    <path d="M647-440H160v-80h487L423-744l57-56 320 320-320 320-57-56 224-224Z" />
                </svg>
            </button>
        </div>

        <div class="section">
            <div class="parent">
                <div class="div1">
                    <svg xmlns="http://www.w3.org/2000/svg" height="50px" viewBox="0 -960 960 960" width="50px"
                        fill="#337ccfe2"
                        style="background-color: #e0eeffcb; border-radius: 10px; margin-left: 1.5rem; margin-top: 1.5rem;">
                        <path
                            d="m422-232 207-248H469l29-227-185 267h139l-30 208ZM320-80l40-280H160l360-520h80l-40 320h240L400-80h-80Zm151-390Z" />
                    </svg> <br><br>
                    <h4>Task Management</h4>
                    <p class="section-paragraph">Assign, track, and complete tasks with real-time updates and seamless
                        collaboration tools.
                    </p>
                </div>
                <div class="div2">
                    <svg xmlns="http://www.w3.org/2000/svg" height="50px" viewBox="0 -960 960 960" width="50px"
                        fill="#337ccfe2"
                        style="background-color: #e0eeffcb; border-radius: 10px; margin-left: 1.5rem; margin-top: 1.5rem;">
                        <path
                            d="M480-80q-139-35-229.5-159.5T160-516v-244l320-120 320 120v244q0 152-90.5 276.5T480-80Zm0-84q104-33 172-132t68-220v-189l-240-90-240 90v189q0 121 68 220t172 132Zm0-316Z" />
                    </svg> <br><br>
                    <h4>Secure Payroll</h4>
                    <p class="section-paragraph">Automated payroll processing with deduction management and salary slip
                        generation.</p>
                </div>
                <div class="div3">
                    <svg xmlns="http://www.w3.org/2000/svg" height="50px" viewBox="0 -960 960 960" width="50px"
                        fill="#337ccfe2"
                        style="background-color: #e0eeffcb; border-radius: 10px; margin-left: 1.5rem; margin-top: 1.5rem;">
                        <path
                            d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q65 0 123 19t107 53l-58 59q-38-24-81-37.5T480-800q-133 0-226.5 93.5T160-480q0 133 93.5 226.5T480-160q133 0 226.5-93.5T800-480q0-18-2-36t-6-35l65-65q11 32 17 66t6 70q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm-56-216L254-466l56-56 114 114 400-401 56 56-456 457Z" />
                    </svg> <br><br>
                    <h4>Leave Tracking</h4>
                    <p class="section-paragraph">Streamlined leave request and approval workflows for HR and team
                        leaders.</p>
                </div>
            </div>

        </div>
    </div>

</body>

</html>