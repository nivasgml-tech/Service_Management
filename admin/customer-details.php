<?php

session_start();

if (!isset($_SESSION['admin_id'])) {

    header("Location: login.php");

    exit;
}

require_once "../config/database.php";


/* CUSTOMER ID */

$customer_id = $_GET['id'] ?? '';

if (!is_numeric($customer_id) || (int)$customer_id <= 0) {

    header("Location: customers.php");

    exit;
}

$customer_id = (int)$customer_id;


/* GET CUSTOMER */

$stmt = $pdo->prepare("
    SELECT *
    FROM customers
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$customer_id]);

$customer = $stmt->fetch();


/* CUSTOMER NOT FOUND */

if (!$customer) {

    header("Location: customers.php");

    exit;
}

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
        Customer Details | YourDream
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


        /* TOPBAR */

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


        .topbar a {

            color: #ffffff;

            text-decoration: none;

        }


        .logout-btn {

            border: 1px solid #8293a3;

            padding: 10px 20px;

            border-radius: 6px;

        }


        /* CONTAINER */

        .container {

            max-width: 1000px;

            margin: auto;

            padding: 35px 20px;

        }


        /* BACK LINK */

        .back-link {

            display: inline-block;

            margin-bottom: 20px;

            color: #10253d;

            text-decoration: none;

            font-weight: 700;

        }


        /* PAGE HEADER */

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


        /* BUTTON */

        .btn {

            display: inline-block;

            padding: 11px 18px;

            border-radius: 6px;

            text-decoration: none;

            font-weight: 700;

            font-size: 14px;

        }


        .btn-primary {

            background: #10253d;

            color: #ffffff;

        }


        .btn-secondary {

            background: #edf0f2;

            color: #263746;

        }


        /* DETAILS CARD */

        .details-card {

            background: #ffffff;

            border-radius: 12px;

            box-shadow: 0 4px 18px rgba(0,0,0,0.05);

            overflow: hidden;

        }


        .details-header {

            background: #f0f3f5;

            padding: 20px 25px;

            border-bottom: 1px solid #e1e6ea;

        }


        .details-header h2 {

            margin: 0;

            color: #10253d;

            font-size: 20px;

        }


        .details-header p {

            margin: 6px 0 0 0;

            color: #687681;

            font-size: 13px;

        }


        /* DETAILS GRID */

        .details-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

        }


        .detail-item {

            padding: 20px 25px;

            border-bottom: 1px solid #edf0f2;

        }


        .detail-item:nth-child(odd) {

            border-right: 1px solid #edf0f2;

        }


        .detail-label {

            font-size: 12px;

            font-weight: 700;

            color: #687681;

            margin-bottom: 7px;

            text-transform: uppercase;

            letter-spacing: 0.4px;

        }


        .detail-value {

            font-size: 15px;

            color: #263746;

            line-height: 1.5;

            word-break: break-word;

        }


        .detail-value strong {

            color: #10253d;

        }


        .address-item {

            grid-column: 1 / -1;

        }


        .address-item:nth-child(odd) {

            border-right: 0;

        }


        /* ACTIONS */

        .actions {

            padding: 20px 25px;

            display: flex;

            gap: 10px;

            border-top: 1px solid #edf0f2;

        }


        /* MOBILE */

        @media (max-width: 700px) {

            .topbar {

                padding: 15px 18px;

            }


            .logo {

                font-size: 20px;

            }


            .container {

                padding: 25px 15px;

            }


            .page-header {

                display: block;

            }


            .page-header .btn {

                margin-top: 15px;

            }


            .details-grid {

                grid-template-columns: 1fr;

            }


            .detail-item:nth-child(odd) {

                border-right: 0;

            }


            .address-item {

                grid-column: auto;

            }


            .actions {

                flex-direction: column;

            }


            .actions .btn {

                width: 100%;

                text-align: center;

            }

        }

    </style>

</head>


<body>


<!-- TOPBAR -->

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


<!-- MAIN -->

<div class="container">


    <a
        href="customers.php"
        class="back-link"
    >
        ← Back to Customers
    </a>


    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>

            <h1>Customer Details</h1>

            <p>
                View complete customer information.
            </p>

        </div>


        <div>

            <a
                href="customer-edit.php?id=<?php echo (int)$customer['id']; ?>"
                class="btn btn-primary"
            >
                Edit Customer
            </a>

            <a
    href="customer-delete.php?id=<?php echo (int)$customer['id']; ?>"
    class="btn btn-danger"
>
    Delete Customer
</a>

        </div>

    </div>


    <!-- DETAILS -->

    <div class="details-card">


        <div class="details-header">

            <h2>

                <?php
                echo htmlspecialchars($customer['name']);
                ?>

            </h2>

            <p>

                Customer ID:
                #<?php echo (int)$customer['id']; ?>

            </p>

        </div>


        <div class="details-grid">


            <!-- NAME -->

            <div class="detail-item">

                <div class="detail-label">
                    Customer Name
                </div>

                <div class="detail-value">

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $customer['name']
                        );
                        ?>
                    </strong>

                </div>

            </div>


            <!-- MOBILE -->

            <div class="detail-item">

                <div class="detail-label">
                    Mobile Number
                </div>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $customer['mobile']
                    );
                    ?>

                </div>

            </div>


            <!-- EMAIL -->

            <div class="detail-item">

                <div class="detail-label">
                    Email
                </div>

                <div class="detail-value">

                    <?php

                    echo !empty($customer['email'])

                        ? htmlspecialchars(
                            $customer['email']
                        )

                        : '-';

                    ?>

                </div>

            </div>


            <!-- CITY -->

            <div class="detail-item">

                <div class="detail-label">
                    City
                </div>

                <div class="detail-value">

                    <?php

                    echo !empty($customer['city'])

                        ? htmlspecialchars(
                            $customer['city']
                        )

                        : '-';

                    ?>

                </div>

            </div>


            <!-- STATE -->

            <div class="detail-item">

                <div class="detail-label">
                    State
                </div>

                <div class="detail-value">

                    <?php

                    echo !empty($customer['state'])

                        ? htmlspecialchars(
                            $customer['state']
                        )

                        : '-';

                    ?>

                </div>

            </div>


            <!-- PINCODE -->

            <div class="detail-item">

                <div class="detail-label">
                    Pincode
                </div>

                <div class="detail-value">

                    <?php

                    echo !empty($customer['pincode'])

                        ? htmlspecialchars(
                            $customer['pincode']
                        )

                        : '-';

                    ?>

                </div>

            </div>


            <!-- ADDRESS -->

            <div class="detail-item address-item">

                <div class="detail-label">
                    Address
                </div>

                <div class="detail-value">

                    <?php

                    echo !empty($customer['address'])

                        ? nl2br(
                            htmlspecialchars(
                                $customer['address']
                            )
                        )

                        : '-';

                    ?>

                </div>

            </div>


            <!-- CREATED -->

            <div class="detail-item">

                <div class="detail-label">
                    Created At
                </div>

                <div class="detail-value">

                    <?php

                    echo date(
                        'd-m-Y h:i A',
                        strtotime(
                            $customer['created_at']
                        )
                    );

                    ?>

                </div>

            </div>


            <!-- UPDATED -->

            <div class="detail-item">

                <div class="detail-label">
                    Last Updated
                </div>

                <div class="detail-value">

                    <?php

                    echo date(
                        'd-m-Y h:i A',
                        strtotime(
                            $customer['updated_at']
                        )
                    );

                    ?>

                </div>

            </div>


        </div>


        <!-- ACTIONS -->

        <div class="actions">

            <a
                href="customer-edit.php?id=<?php echo (int)$customer['id']; ?>"
                class="btn btn-primary"
            >
                Edit Customer
            </a>


            <a
                href="customers.php"
                class="btn btn-secondary"
            >
                Back to Customers
            </a>

        </div>


    </div>


</div>


</body>

</html>