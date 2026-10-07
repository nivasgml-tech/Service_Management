<?php

session_start();

if (!isset($_SESSION['admin_id'])) {

    header("Location: login.php");

    exit;
}

require_once "../config/database.php";


/* GET ALL SERVICES */

$stmt = $pdo->query("
    SELECT *
    FROM services
    ORDER BY sort_order ASC, id ASC
");

$services = $stmt->fetchAll();


/* COUNTS */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM services
");

$total_services = $stmt->fetchColumn();


$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM services
    WHERE status = 1
");

$active_services = $stmt->fetchColumn();


$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM services
    WHERE status = 0
");

$inactive_services = $stmt->fetchColumn();

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
        Services Management | YourDream
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

        .logout-btn {

            color: #ffffff;

            text-decoration: none;

            border: 1px solid #8293a3;

            padding: 10px 20px;

            border-radius: 6px;

        }

        .container {

            max-width: 1300px;

            margin: auto;

            padding: 35px 20px;

        }

        .back-link {

            display: inline-block;

            margin-bottom: 20px;

            color: #10253d;

            text-decoration: none;

            font-weight: 700;

        }

        .page-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;

        }

        .page-header h1 {

            margin: 0 0 8px 0;

        }

        .page-header p {

            margin: 0;

            color: #687681;

        }

        .add-btn {

            background: #10253d;

            color: #ffffff;

            padding: 12px 20px;

            border-radius: 7px;

            text-decoration: none;

            font-weight: 700;

        }

        .add-btn:hover {

            background: #173653;

        }

        /* SUMMARY */

        .summary {

            display: grid;

            grid-template-columns: repeat(3, 1fr);

            gap: 20px;

            margin-bottom: 25px;

        }

        .summary-card {

            background: #ffffff;

            padding: 25px;

            border-radius: 12px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,0.05);

        }

        .summary-label {

            color: #687681;

            margin-bottom: 10px;

        }

        .summary-number {

            font-size: 34px;

            font-weight: 700;

            color: #10253d;

        }

        /* TABLE */

        .table-card {

            background: #ffffff;

            border-radius: 12px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,0.05);

            overflow-x: auto;

        }

        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 900px;

        }

        th {

            background: #f0f3f5;

            padding: 15px;

            text-align: left;

            font-size: 13px;

            color: #52616d;

        }

        td {

            padding: 15px;

            border-top: 1px solid #edf0f2;

            vertical-align: middle;

        }

        tr:hover td {

            background: #fafbfc;

        }

        .service-name {

            font-weight: 700;

            color: #10253d;

        }

        .slug {

            color: #7a8791;

            font-size: 13px;

            margin-top: 4px;

        }

        .description {

            max-width: 350px;

            line-height: 1.5;

        }

        .status {

            display: inline-block;

            padding: 6px 10px;

            border-radius: 5px;

            font-size: 12px;

            font-weight: 700;

        }

        .active {

            background: #e8f5e9;

            color: #2e7d32;

        }

        .inactive {

            background: #ffebee;

            color: #c62828;

        }

        .action-btn {

            display: inline-block;

            padding: 8px 13px;

            border-radius: 5px;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

            margin-right: 5px;

        }

        .edit-btn {

            background: #10253d;

            color: #ffffff;

        }

        .empty {

            text-align: center;

            padding: 50px;

            color: #7a8791;

        }

        @media (max-width: 800px) {

            .summary {

                grid-template-columns: 1fr;

            }

            .page-header {

                display: block;

            }

            .add-btn {

                display: inline-block;

                margin-top: 20px;

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

    <a
        href="logout.php"
        class="logout-btn"
    >
        Logout
    </a>

</div>


<div class="container">


    <!-- BACK -->

    <a
        href="dashboard.php"
        class="back-link"
    >
        ← Dashboard
    </a>


    <!-- HEADER -->

    <div class="page-header">

        <div>

            <h1>
                Services Management
            </h1>

            <p>
                Manage website services and their content.
            </p>

        </div>


        <a
            href="service-add.php"
            class="add-btn"
        >
            + Add Service
        </a>

    </div>


    <!-- SUMMARY -->

    <div class="summary">


        <div class="summary-card">

            <div class="summary-label">
                Total Services
            </div>

            <div class="summary-number">

                <?php
                echo $total_services;
                ?>

            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                Active Services
            </div>

            <div class="summary-number">

                <?php
                echo $active_services;
                ?>

            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                Inactive Services
            </div>

            <div class="summary-number">

                <?php
                echo $inactive_services;
                ?>

            </div>

        </div>


    </div>


    <!-- SERVICES TABLE -->

    <div class="table-card">


        <?php if (count($services) > 0): ?>


            <table>

                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Service
                        </th>

                        <th>
                            Short Description
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Sort Order
                        </th>

                        <th>
                            Created
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php foreach ($services as $service): ?>


                        <tr>


                            <!-- ID -->

                            <td>

                                <?php
                                echo $service['id'];
                                ?>

                            </td>


                            <!-- SERVICE -->

                            <td>

                                <div class="service-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $service['service_name']
                                    );
                                    ?>

                                </div>


                                <div class="slug">

                                    /
                                    <?php
                                    echo htmlspecialchars(
                                        $service['slug']
                                    );
                                    ?>

                                </div>

                            </td>


                            <!-- DESCRIPTION -->

                            <td>

                                <div class="description">

                                    <?php

                                    $description =
                                        trim(
                                            $service['short_description']
                                            ?? ''
                                        );


                                    if ($description === '') {

                                        echo '-';

                                    } elseif (
                                        strlen($description) > 120
                                    ) {

                                        echo htmlspecialchars(
                                            substr(
                                                $description,
                                                0,
                                                120
                                            )
                                            . '...'
                                        );

                                    } else {

                                        echo htmlspecialchars(
                                            $description
                                        );

                                    }

                                    ?>

                                </div>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <?php if (
                                    $service['status'] == 1
                                ): ?>

                                    <span
                                        class="status active"
                                    >
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span
                                        class="status inactive"
                                    >
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- SORT -->

                            <td>

                                <?php
                                echo $service['sort_order'];
                                ?>

                            </td>


                            <!-- CREATED -->

                            <td>

                                <?php

                                echo date(
                                    'd-m-Y',
                                    strtotime(
                                        $service['created_at']
                                    )
                                );

                                ?>

                            </td>


                            <!-- ACTION -->

                            <td>

                                <a
                                    href="service-edit.php?id=<?php echo $service['id']; ?>"
                                    class="action-btn edit-btn"
                                >
                                    Edit
                                </a>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                </tbody>

            </table>


        <?php else: ?>


            <div class="empty">

                No services found.

            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>