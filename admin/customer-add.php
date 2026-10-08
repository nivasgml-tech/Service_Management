<?php

session_start();

if (!isset($_SESSION['admin_id'])) {

    header("Location: login.php");

    exit;
}

require_once "../config/database.php";


/* FORM VALUES */

$name = '';
$mobile = '';
$email = '';
$address = '';
$city = '';
$state = '';
$pincode = '';

$error = '';


/* SAVE CUSTOMER */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');


    /* VALIDATION */

    if ($name === '') {

        $error = "Customer name is required.";

    } elseif ($mobile === '') {

        $error = "Mobile number is required.";

    } else {

        try {

            /* INSERT CUSTOMER */

            $stmt = $pdo->prepare("
                INSERT INTO customers
                (
                    name,
                    mobile,
                    email,
                    address,
                    city,
                    state,
                    pincode
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            $stmt->execute([
                $name,
                $mobile,
                $email !== '' ? $email : null,
                $address !== '' ? $address : null,
                $city !== '' ? $city : null,
                $state !== '' ? $state : null,
                $pincode !== '' ? $pincode : null
            ]);


            /* SUCCESS */

            header("Location: customers.php");

            exit;


        } catch (PDOException $e) {

            $error = "Unable to save customer. Please try again.";

        }

    }

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
        Add Customer | YourDream
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


        /* PAGE HEADER */

        .page-header {

            margin-bottom: 25px;

        }


        .page-header h1 {

            margin: 0 0 8px 0;

        }


        .page-header p {

            margin: 0;

            color: #687681;

        }


        .back-link {

            display: inline-block;

            margin-bottom: 20px;

            color: #10253d;

            text-decoration: none;

            font-weight: 700;

        }


        /* FORM CARD */

        .form-card {

            background: #ffffff;

            padding: 30px;

            border-radius: 12px;

            box-shadow: 0 4px 18px rgba(0,0,0,0.05);

        }


        .form-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 20px;

        }


        .form-group {

            margin-bottom: 5px;

        }


        .form-group.full {

            grid-column: 1 / -1;

        }


        .form-group label {

            display: block;

            margin-bottom: 7px;

            font-size: 13px;

            font-weight: 700;

            color: #52616d;

        }


        .required {

            color: #c0392b;

        }


        .form-group input,

        .form-group textarea {

            width: 100%;

            padding: 12px;

            border: 1px solid #d9e0e6;

            border-radius: 6px;

            font-family: inherit;

            font-size: 14px;

            background: #ffffff;

        }


        .form-group textarea {

            min-height: 110px;

            resize: vertical;

        }


        .form-group input:focus,

        .form-group textarea:focus {

            outline: none;

            border-color: #10253d;

        }


        /* ERROR */

        .error-message {

            background: #fff0f0;

            border: 1px solid #f0c5c5;

            color: #a33a3a;

            padding: 12px 15px;

            border-radius: 6px;

            margin-bottom: 20px;

        }


        /* BUTTONS */

        .form-actions {

            margin-top: 25px;

            padding-top: 20px;

            border-top: 1px solid #edf0f2;

            display: flex;

            gap: 10px;

        }


        .btn {

            display: inline-block;

            padding: 11px 20px;

            border-radius: 6px;

            border: 0;

            text-decoration: none;

            font-weight: 700;

            cursor: pointer;

            font-size: 14px;

        }


        .btn-primary {

            background: #10253d;

            color: #ffffff;

        }


        .btn-primary:hover {

            background: #071a2d;

        }


        .btn-secondary {

            background: #edf0f2;

            color: #263746;

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


            .form-card {

                padding: 20px;

            }


            .form-grid {

                grid-template-columns: 1fr;

            }


            .form-group.full {

                grid-column: auto;

            }


            .form-actions {

                flex-direction: column;

            }


            .form-actions .btn {

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


    <div class="page-header">

        <h1>Add Customer</h1>

        <p>
            Create a new customer record.
        </p>

    </div>


    <?php if ($error !== ''): ?>

        <div class="error-message">

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <div class="form-card">


        <form method="POST">


            <div class="form-grid">


                <!-- NAME -->

                <div class="form-group">

                    <label>

                        Customer Name

                        <span class="required">*</span>

                    </label>

                    <input
                        type="text"
                        name="name"
                        value="<?php echo htmlspecialchars($name); ?>"
                        placeholder="Enter customer name"
                        required
                    >

                </div>


                <!-- MOBILE -->

                <div class="form-group">

                    <label>

                        Mobile Number

                        <span class="required">*</span>

                    </label>

                    <input
                        type="text"
                        name="mobile"
                        value="<?php echo htmlspecialchars($mobile); ?>"
                        placeholder="Enter mobile number"
                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="form-group">

                    <label>
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email); ?>"
                        placeholder="Enter email address"
                    >

                </div>


                <!-- CITY -->

                <div class="form-group">

                    <label>
                        City
                    </label>

                    <input
                        type="text"
                        name="city"
                        value="<?php echo htmlspecialchars($city); ?>"
                        placeholder="Enter city"
                    >

                </div>


                <!-- STATE -->

                <div class="form-group">

                    <label>
                        State
                    </label>

                    <input
                        type="text"
                        name="state"
                        value="<?php echo htmlspecialchars($state); ?>"
                        placeholder="Enter state"
                    >

                </div>


                <!-- PINCODE -->

                <div class="form-group">

                    <label>
                        Pincode
                    </label>

                    <input
                        type="text"
                        name="pincode"
                        value="<?php echo htmlspecialchars($pincode); ?>"
                        placeholder="Enter pincode"
                    >

                </div>


                <!-- ADDRESS -->

                <div class="form-group full">

                    <label>
                        Address
                    </label>

                    <textarea
                        name="address"
                        placeholder="Enter customer address"
                    ><?php echo htmlspecialchars($address); ?></textarea>

                </div>


            </div>


            <!-- ACTIONS -->

            <div class="form-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Save Customer
                </button>


                <a
                    href="customers.php"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>

            </div>


        </form>


    </div>


</div>


</body>

</html>