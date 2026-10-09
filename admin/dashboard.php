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

        * {
            box-sizing: border-box;
        }

        body {

            margin: 0;

            font-family: Arial, sans-serif;

            background: #f5f7f9;

            color: #263746;

        }

        .topbar {

            background: #071a2d;

            color: #ffffff;

            padding: 18px 30px;

            display: flex;

            justify-content: space-between;

            align-items: center;

        }

        .logo {

            font-size: 24px;

            font-weight: 700;

        }

        .logo span {

            color: #c6a15b;

        }

        .topbar-right {

            display: flex;

            align-items: center;

            gap: 15px;

        }

        .topbar a {

            color: #ffffff;

            text-decoration: none;

        }

        .logout-btn {

            border: 1px solid #8293a3;

            padding: 10px 20px;

            border-radius: 6px;

        }

        .logout-btn:hover {

            background: #173653;

        }

        .container {

            max-width: 1200px;

            margin: auto;

            padding: 40px 20px;

        }

        .welcome {

            margin-bottom: 30px;

        }

        .welcome h1 {

            margin-bottom: 8px;

            font-size: 38px;

        }

        .welcome p {

            color: #687681;

            font-size: 18px;

        }

        /* NAVIGATION */

        .admin-nav {

            background: #ffffff;

            padding: 15px 20px;

            border-radius: 10px;

            margin-bottom: 30px;

            box-shadow: 0 5px 20px rgba(0,0,0,0.05);

            display: flex;

            gap: 10px;

            flex-wrap: wrap;

        }

        .admin-nav a {

            display: inline-block;

            padding: 11px 18px;

            border-radius: 6px;

            text-decoration: none;

            color: #10253d;

            font-weight: 700;

        }

        .admin-nav a:hover {

            background: #eef2f5;

        }

        .admin-nav a.active {

            background: #10253d;

            color: #ffffff;

        }

        /* DASHBOARD CARDS */

        .cards {

            display: grid;

            grid-template-columns: repeat(3, 1fr);

            gap: 30px;

        }

        .card {

            background: #ffffff;

            padding: 40px;

            border-radius: 14px;

            box-shadow:

                0 5px 25px

                rgba(0,0,0,0.05);

        }

        .card-title {

            color: #687681;

            font-size: 17px;

            margin-bottom: 20px;

        }

        .card-number {

            font-size: 48px;

            font-weight: 700;

            color: #10253d;

        }

        .card-link {

            display: inline-block;

            margin-top: 20px;

            color: #10253d;

            font-weight: 700;

            text-decoration: none;

        }

        .card-link:hover {

            text-decoration: underline;

        }

        @media (max-width: 800px) {

            .cards {

                grid-template-columns: 1fr;

            }

            .welcome h1 {

                font-size: 30px;

            }

            .topbar {

                padding: 15px 20px;

            }

        }

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

    <div class="admin-nav">

        <a
            href="dashboard.php"
            class="active"
        >
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


        <a href="#">
            Customers
        </a>


       <a href="reports.php">
    Reports
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

    </div>


</div>

</body>

</html>