
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

/* DASHBOARD COUNTS */
$stmt = $pdo->query("
    SELECT COUNT(*) FROM services WHERE status = 1
");
$active_services = (int)$stmt->fetchColumn();

$stmt = $pdo->query("
    SELECT COUNT(*) FROM leads
");
$total_leads = (int)$stmt->fetchColumn();

$stmt = $pdo->query("
    SELECT COUNT(*) FROM leads WHERE status = 'New'
");
$new_leads = (int)$stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | YourDream</title>

    <style>
        :root {
            --navy: #101d35;
            --navy-light: #1b2c49;
            --gold: #c9a45c;
            --bg: #f4f6fb;
            --text: #172641;
            --muted: #718096;
            --border: #e5eaf2;
            --white: #ffffff;
            --shadow: 0 8px 28px rgba(16, 29, 53, .06);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Inter, "Segoe UI", Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        a {
            text-decoration: none;
        }

        .topbar {
            min-height: 76px;
            padding: 16px 30px;
            background: var(--navy);
            color: white;
            border-bottom: 3px solid var(--gold);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .logo {
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -.5px;
        }

        .logo span {
            color: var(--gold);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .admin-label {
            color: #e3e9f4;
            font-size: 14px;
        }

        .logout-btn {
            padding: 10px 17px;
            color: white;
            border: 1px solid #52627b;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 700;
            transition: .2s;
        }

        .logout-btn:hover {
            background: var(--navy-light);
            border-color: var(--gold);
        }

        .layout {
            display: grid;
            grid-template-columns: 245px minmax(0, 1fr);
            min-height: calc(100vh - 76px);
        }

        .sidebar {
            background: var(--navy);
            color: white;
            padding: 25px 15px;
            display: flex;
            flex-direction: column;
        }

        .sidebar-heading {
            margin: 0 12px 18px;
            color: #91a1ba;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .side-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 14px;
            margin-bottom: 6px;
            color: #e4eaf4;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 600;
            transition: .2s;
        }

        .side-link:hover {
            background: #1d304f;
            color: white;
        }

        .side-link.active {
            background: var(--gold);
            color: var(--navy);
            font-weight: 800;
        }

        .side-icon {
            width: 22px;
            text-align: center;
            font-size: 17px;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 30px 12px 10px;
            color: #b8c4d6;
            font-size: 13px;
            line-height: 1.8;
        }

        .sidebar-footer strong {
            display: block;
            color: var(--gold);
            font-size: 17px;
        }

        .main {
            min-width: 0;
            padding: 32px;
        }

        .welcome-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 27px;
        }

        .eyebrow {
            width: 43px;
            height: 4px;
            margin-bottom: 17px;
            background: var(--gold);
            border-radius: 5px;
        }

        .welcome h1 {
            margin: 0 0 9px;
            color: var(--navy);
            font-size: clamp(27px, 3vw, 36px);
            letter-spacing: -1px;
        }

        .welcome p {
            margin: 0;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.7;
        }

        .welcome-tag {
            color: var(--muted);
            font-size: 13px;
            text-align: right;
            line-height: 1.8;
        }

        .quick-nav {
            display: flex;
            gap: 6px;
            align-items: center;
            overflow-x: auto;
            padding: 10px;
            margin-bottom: 27px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 13px;
            box-shadow: var(--shadow);
        }

        .quick-nav a {
            flex: 0 0 auto;
            padding: 12px 14px;
            color: var(--navy);
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            transition: .2s;
        }

        .quick-nav a:hover {
            background: #f1f4f9;
        }

        .quick-nav a.active {
            color: white;
            background: var(--navy);
            box-shadow: inset 0 -3px 0 var(--gold);
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin: 0 0 16px;
        }

        .section-heading h2 {
            margin: 0;
            color: var(--navy);
            font-size: 18px;
        }

        .section-heading span {
            color: var(--muted);
            font-size: 12px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .card {
            --accent: #2563eb;
            --accent-bg: #eaf1ff;
            position: relative;
            min-width: 0;
            padding: 24px;
            background: white;
            border: 1px solid var(--border);
            border-left: 4px solid var(--accent);
            border-radius: 14px;
            box-shadow: var(--shadow);
            transition: transform .2s, box-shadow .2s;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 32px rgba(16, 29, 53, .10);
        }

        .card.blue {
            --accent: #2563eb;
            --accent-bg: #eaf1ff;
        }

        .card.green {
            --accent: #12a66a;
            --accent-bg: #e4f8ee;
        }

        .card.orange {
            --accent: #e88719;
            --accent-bg: #fff0df;
        }

        .card.purple {
            --accent: #7850e8;
            --accent-bg: #f0eaff;
        }

        .card.teal {
            --accent: #079ca5;
            --accent-bg: #e0f7f7;
        }

        .card-top {
            display: flex;
            align-items: center;
            gap: 13px;
            margin-bottom: 21px;
        }

        .card-icon {
            display: grid;
            place-items: center;
            flex: 0 0 48px;
            width: 48px;
            height: 48px;
            background: var(--accent-bg);
            color: var(--accent);
            border-radius: 12px;
            font-size: 23px;
        }

        .card-title {
            color: #34435d;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.5;
        }

        .card-number {
            margin-bottom: 8px;
            color: var(--navy);
            font-size: 38px;
            font-weight: 800;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .card-description {
            min-height: 20px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.6;
        }

        .card-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 18px;
            color: var(--accent);
            font-size: 13px;
            font-weight: 800;
        }

        .card-link:hover {
            text-decoration: underline;
        }

        .card-link .arrow {
            font-size: 18px;
            transition: transform .2s;
        }

        .card-link:hover .arrow {
            transform: translateX(4px);
        }

        .bottom-panel {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 20px;
            margin-top: 24px;
        }

        .info-panel {
            padding: 24px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow);
        }

        .info-panel h3 {
            margin: 0 0 8px;
            color: var(--navy);
            font-size: 16px;
        }

        .info-panel p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.8;
        }

        .growth-panel {
            display: flex;
            align-items: center;
            gap: 17px;
            background: #eaf0ff;
            border-color: #dce5fb;
        }

        .growth-symbol {
            color: #b48b36;
            font-size: 42px;
        }

        .growth-panel h3 {
            margin-bottom: 7px;
        }

        .gold-line {
            width: 35px;
            height: 3px;
            margin-top: 12px;
            background: var(--gold);
        }

        @media (max-width: 1150px) {
            .layout {
                grid-template-columns: 205px minmax(0, 1fr);
            }

            .main {
                padding: 25px;
            }

            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .topbar {
                padding: 15px 18px;
            }

            .logo {
                font-size: 20px;
            }

            .admin-label {
                display: none;
            }

            .layout {
                display: block;
            }

            .sidebar {
                padding: 12px;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 5px;
            }

            .sidebar-heading,
            .sidebar-footer {
                display: none;
            }

            .side-link {
                margin: 0;
                padding: 11px 9px;
                font-size: 12px;
            }

            .side-icon {
                font-size: 15px;
            }

            .main {
                padding: 22px 16px;
            }

            .welcome-row {
                align-items: flex-start;
            }

            .welcome-tag {
                display: none;
            }

            .quick-nav {
                margin-bottom: 22px;
            }

            .cards {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .card {
                padding: 20px;
            }

            .bottom-panel {
                grid-template-columns: 1fr;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                transition: none !important;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="logo">Your<span>Dream</span> Admin</div>

    <div class="topbar-right">
        <span class="admin-label">Administrator</span>
        <a href="logout.php" class="logout-btn">↪ Logout</a>
    </div>
</header>

<div class="layout">

    <aside class="sidebar">
        <div class="sidebar-heading">Main Menu</div>

        <a href="dashboard.php" class="side-link active">
            <span class="side-icon">⌂</span> Dashboard
        </a>

        <a href="leads.php" class="side-link">
            <span class="side-icon">♙</span> Leads
        </a>

        <a href="followup-reminders.php" class="side-link">
            <span class="side-icon">◷</span> Follow-up Reminders
        </a>

        <a href="services.php" class="side-link">
            <span class="side-icon">⚙</span> Services
        </a>

        <a href="customers.php" class="side-link">
            <span class="side-icon">♧</span> Customers
        </a>

        <a href="reports.php" class="side-link">
            <span class="side-icon">▥</span> Reports
        </a>

        <a href="customer-reports.php" class="side-link">
            <span class="side-icon">▤</span> Customer Reports
        </a>

        <div class="sidebar-footer">
            <strong>Build and Boost</strong>
            Your Business with Us
        </div>
    </aside>

    <main class="main">

        <section class="welcome-row">
            <div class="welcome">
                <div class="eyebrow"></div>
                <h1>Welcome, Administrator</h1>
                <p>Manage your website, services and leads.</p>
            </div>

            <div class="welcome-tag">
                YourDream Admin<br>
                Business Management
            </div>
        </section>

        <nav class="quick-nav" aria-label="Admin navigation">
            <a href="dashboard.php" class="active">⌂ Dashboard</a>
            <a href="leads.php">♙ Leads</a>
            <a href="followup-reminders.php">◷ Follow-up Reminders</a>
            <a href="services.php">⚙ Services</a>
            <a href="customers.php">♧ Customers</a>
            <a href="reports.php">▥ Reports</a>
            <a href="customer-reports.php">▤ Customer Reports</a>
        </nav>

        <div class="section-heading">
            <h2>Overview</h2>
            <span>Quick access to your business</span>
        </div>

        <section class="cards">

            <article class="card blue">
                <div class="card-top">
                    <div class="card-icon">⚙</div>
                    <div class="card-title">Active Services</div>
                </div>
                <div class="card-number">
                    <?= $active_services ?>
                </div>
                <div class="card-description">
                    Services currently active
                </div>
                <a href="services.php" class="card-link">
                    View Services <span class="arrow">→</span>
                </a>
            </article>

            <article class="card green">
                <div class="card-top">
                    <div class="card-icon">♧</div>
                    <div class="card-title">Total Leads</div>
                </div>
                <div class="card-number">
                    <?= $total_leads ?>
                </div>
                <div class="card-description">
                    All leads received
                </div>
                <a href="leads.php" class="card-link">
                    View Leads <span class="arrow">→</span>
                </a>
            </article>

            <article class="card orange">
                <div class="card-top">
                    <div class="card-icon">＋</div>
                    <div class="card-title">New Leads</div>
                </div>
                <div class="card-number">
                    <?= $new_leads ?>
                </div>
                <div class="card-description">
                    Leads awaiting initial action
                </div>
                <a href="leads.php" class="card-link">
                    View New Leads <span class="arrow">→</span>
                </a>
            </article>

            <article class="card purple">
                <div class="card-top">
                    <div class="card-icon">◷</div>
                    <div class="card-title">Follow-up Reminders</div>
                </div>
                <div class="card-number" style="font-size:23px">
                    Pending Follow-ups
                </div>
                <div class="card-description">
                    Keep track of upcoming lead follow-ups
                </div>
                <a href="followup-reminders.php" class="card-link">
                    View Follow-ups <span class="arrow">→</span>
                </a>
            </article>

            <article class="card teal">
                <div class="card-top">
                    <div class="card-icon">▤</div>
                    <div class="card-title">Customer Reports</div>
                </div>
                <div class="card-number" style="font-size:23px">
                    Customer Statistics
                </div>
                <div class="card-description">
                    Review customer information and reports
                </div>
                <a href="customer-reports.php" class="card-link">
                    View Customer Reports <span class="arrow">→</span>
                </a>
            </article>

            <article class="info-panel growth-panel">
                <div class="growth-symbol">↗</div>
                <div>
                    <h3>Better Leads, Bigger Growth</h3>
                    <p>Manage your services and customer enquiries in one place.</p>
                    <div class="gold-line"></div>
                </div>
            </article>

        </section>

        <section class="bottom-panel">
            <div class="info-panel">
                <h3>Manage Your Business</h3>
                <p>
                    Use the navigation menu to manage leads, follow-up reminders,
                    services, customers and reports.
                </p>
            </div>

            <div class="info-panel">
                <h3>YourDream Admin</h3>
                <p>
                    A centralized workspace for your business enquiries
                    and service management.
                </p>
            </div>
        </section>

    </main>
</div>

</body>
</html>
