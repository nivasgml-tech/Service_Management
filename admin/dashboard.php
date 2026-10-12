<?php

session_start();

if (!isset($_SESSION['admin_id'])) {

    header("Location: login.php");

    exit;
}

require_once "../config/database.php";


/* ACTIVE SERVICES */

$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM services
    WHERE status = 1
");

$active_services = $stmt->fetchColumn();


/* TOTAL LEADS */

$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM leads
");

$total_leads = $stmt->fetchColumn();


/* NEW LEADS */

$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM leads
    WHERE status = 'New'
");

$new_leads = $stmt->fetchColumn();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Admin Dashboard | YourDream
    </title>

    <style>

<style>
    * {
        box-sizing: border-box;
    }

    :root {
        --admin-navy: #101d35;
        --admin-navy-light: #1b2c49;
        --admin-gold: #c9a45c;
        --admin-bg: #f5f7fb;
        --admin-text: #263247;
        --admin-muted: #6b7280;
        --admin-border: #e3e8ef;
        --admin-shadow: 0 8px 28px rgba(16, 29, 53, 0.07);
    }

    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: var(--admin-bg);
        color: var(--admin-text);
    }

    .topbar {
        background: var(--admin-navy);
        color: #fff;
        padding: 20px 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        border-bottom: 3px solid var(--admin-gold);
    }

    .logo {
        font-size: 24px;
        font-weight: 800;
        letter-spacing: 0.3px;
    }

    .logo span {
        color: var(--admin-gold);
    }

    .topbar-right {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .topbar a {
        color: #fff;
        text-decoration: none;
    }

    .logout-btn {
        display: inline-block;
        padding: 10px 18px;
        border: 1px solid rgba(255,255,255,0.4);
        border-radius: 8px;
        transition: 0.2s ease;
    }

    .logout-btn:hover {
        background: var(--admin-navy-light);
        border-color: var(--admin-gold);
    }

    .container {
        width: 100%;
        max-width: 1240px;
        margin: auto;
        padding: 38px 24px;
    }

    .welcome {
        margin-bottom: 28px;
    }

    .welcome h1 {
        margin: 0 0 10px;
        color: var(--admin-navy);
        font-size: clamp(28px, 4vw, 38px);
        font-weight: 800;
    }

    .welcome p {
        margin: 0;
        color: var(--admin-muted);
        font-size: 16px;
        line-height: 1.7;
    }

    .admin-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 14px;
        margin-bottom: 30px;
        background: #fff;
        border: 1px solid var(--admin-border);
        border-radius: 12px;
        box-shadow: var(--admin-shadow);
    }

    .admin-nav a {
        display: inline-block;
        padding: 11px 15px;
        border-radius: 7px;
        text-decoration: none;
        color: var(--admin-navy);
        font-size: 14px;
        font-weight: 700;
        transition: 0.2s ease;
    }

    .admin-nav a:hover {
        background: #f3f5f8;
    }

    .admin-nav a.active {
        background: var(--admin-navy);
        color: #fff;
        box-shadow: inset 0 -2px 0 var(--admin-gold);
    }

    .cards {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 22px;
    }

    .card {
        min-width: 0;
        padding: 28px;
        background: #fff;
        border: 1px solid var(--admin-border);
        border-radius: 14px;
        box-shadow: var(--admin-shadow);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(16, 29, 53, 0.11);
    }

    .card-title {
        margin-bottom: 18px;
        color: var(--admin-muted);
        font-size: 15px;
        font-weight: 600;
    }

    .card-number {
        color: var(--admin-navy);
        font-size: clamp(25px, 3vw, 42px);
        font-weight: 800;
        line-height: 1.25;
        overflow-wrap: anywhere;
    }

    .card-link {
        display: inline-block;
        margin-top: 20px;
        color: #8a682b;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
    }

    .card-link:hover {
        color: var(--admin-navy);
        text-decoration: underline;
    }

    @media (max-width: 950px) {
        .cards {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .container {
            padding: 30px 20px;
        }
    }

    @media (max-width: 600px) {
        .topbar {
            padding: 16px 18px;
        }

        .logo {
            font-size: 20px;
        }

        .container {
            padding: 25px 15px;
        }

        .admin-nav {
            padding: 10px;
            gap: 6px;
        }

        .admin-nav a {
            padding: 10px 11px;
            font-size: 13px;
        }

        .cards {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .card {
            padding: 24px;
        }
    }
</style>
    </style>

</head>

<body>


<!-- TOP BAR -->

<div class="topbar">

    <div class="logo">

        Your<span>Dream</span> Admin

    </div>


    <div class="topbar-right">

        <a
            href="logout.php"
            class="logout-btn"
        >
            Logout
        </a>

    </div>

</div>


<div class="container">


    <!-- WELCOME -->

    <div class="welcome">

        <h1>
            Welcome, Administrator
        </h1>

        <p>
            Manage your website, services and leads.
        </p>

    </div>


    <!-- ADMIN NAVIGATION -->

    
<!-- ADMIN NAVIGATION -->

<div class="admin-nav">

    <a href="dashboard.php" class="active">
        Dashboard
    </a>

    <a href="leads.php">
        Leads
    </a>

    <a href="followup-reminders.php">
        Follow-up Reminders
    </a>

    <a href="services.php">
        Services
    </a>

    <a href="customers.php">
        Customers
    </a>

    <a href="reports.php">
        Reports
    </a>

    <a href="customer-reports.php">
        Customer Reports
    </a>

</div>

    <!-- DASHBOARD CARDS -->

    <div class="cards">


        <!-- ACTIVE SERVICES -->

        <div class="card">

            <div class="card-title">
                Active Services
            </div>

            <div class="card-number">

                <?php
                echo $active_services;
                ?>

            </div>

        </div>


        <!-- TOTAL LEADS -->

        <div class="card">

            <div class="card-title">
                Total Leads
            </div>

            <div class="card-number">

                <?php
                echo $total_leads;
                ?>

            </div>


            <a
                href="leads.php"
                class="card-link"
            >
                View Leads →
            </a>

        </div>


        <!-- NEW LEADS -->

        <div class="card">

            <div class="card-title">
                New Leads
            </div>

            <div class="card-number">

                <?php
                echo $new_leads;
                ?>

            </div>


            <a
                href="leads.php"
                class="card-link"
            >
                View New Leads →
            </a>

        </div>

        
<!-- FOLLOW-UP REMINDERS -->

<div class="card">

    <div class="card-title">
        Follow-up Reminders
    </div>

    <div class="card-number">
        Check Pending Follow-ups
    </div>

    <a
        href="followup-reminders.php"
        class="card-link"
    >
        View Follow-ups →
    </a>

</div>

<!-- CUSTOMER REPORTS -->

<div class="card">

    <div class="card-title">
        Customer Reports
    </div>

    <div class="card-number">
        View Customer Statistics
    </div>

    <a
        href="customer-reports.php"
        class="card-link"
    >
        View Customer Reports →
    </a>

</div>

    </div>


</div>

</body>

</html>