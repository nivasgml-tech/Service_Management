<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

$customer_id = $_GET['id'] ?? '';

if (!is_numeric($customer_id) || (int)$customer_id <= 0) {
    header("Location: customers.php");
    exit;
}

$customer_id = (int)$customer_id;

$stmt = $pdo->prepare("
    SELECT *
    FROM customers
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$customer_id]);

$customer = $stmt->fetch();

if (!$customer) {
    header("Location: customers.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $stmt = $pdo->prepare("
        DELETE FROM customers
        WHERE id = ?
    ");

    $stmt->execute([$customer_id]);

    header("Location: customers.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Delete Customer | YourDream</title>

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
    color: #fff;
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
    color: #fff;
    text-decoration: none;
}

.logout-btn {
    border: 1px solid #8293a3;
    padding: 10px 20px;
    border-radius: 6px;
}

.container {
    max-width: 900px;
    margin: auto;
    padding: 50px 20px;
}

.back-link {
    display: inline-block;
    margin-bottom: 20px;
    color: #10253d;
    text-decoration: none;
    font-weight: 700;
}

.card {
    background: #fff;
    border-radius: 12px;
    padding: 35px;
    box-shadow: 0 4px 18px rgba(0,0,0,.06);
}

.warning {
    background: #fff3cd;
    border: 1px solid #ffe69c;
    color: #664d03;
    padding: 18px;
    border-radius: 8px;
    margin-bottom: 25px;
    line-height: 1.6;
}

.customer-box {
    background: #f8fafc;
    border: 1px solid #e3e8ed;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 25px;
}

.customer-box p {
    margin: 8px 0;
}

.customer-box strong {
    display: inline-block;
    width: 120px;
}

h1 {
    margin-top: 0;
    margin-bottom: 20px;
}

.actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.btn {
    display: inline-block;
    padding: 12px 22px;
    border-radius: 6px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-size: 15px;
    font-weight: 700;
}

.btn-danger {
    background: #c0392b;
    color: #fff;
}

.btn-secondary {
    background: #e9edf1;
    color: #263746;
}

@media (max-width: 600px) {

    .topbar {
        padding: 15px;
    }

    .container {
        padding: 30px 15px;
    }

    .card {
        padding: 22px;
    }

    .customer-box strong {
        width: auto;
        display: block;
    }

}

</style>

</head>

<body>

<div class="topbar">

    <div class="logo">
        Your<span>Dream</span> Admin
    </div>

    <a href="logout.php" class="logout-btn">
        Logout
    </a>

</div>

<div class="container">

    <a href="customer-details.php?id=<?php echo $customer_id; ?>" class="back-link">
        ← Back to Customer Details
    </a>

    <div class="card">

        <h1>Delete Customer</h1>

        <div class="warning">
            <strong>Warning:</strong>
            This action will permanently delete this customer.
            Please confirm before continuing.
        </div>

        <div class="customer-box">

            <p>
                <strong>Name:</strong>
                <?php echo htmlspecialchars($customer['name']); ?>
            </p>

            <p>
                <strong>Mobile:</strong>
                <?php echo htmlspecialchars($customer['mobile']); ?>
            </p>

            <?php if (!empty($customer['email'])): ?>
                <p>
                    <strong>Email:</strong>
                    <?php echo htmlspecialchars($customer['email']); ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($customer['city'])): ?>
                <p>
                    <strong>City:</strong>
                    <?php echo htmlspecialchars($customer['city']); ?>
                </p>
            <?php endif; ?>

        </div>

        <form method="POST">

            <div class="actions">

                <button
                    type="submit"
                    class="btn btn-danger"
                    onclick="return confirm('Are you sure you want to permanently delete this customer?');"
                >
                    Yes, Delete Customer
                </button>

                <a
                    href="customer-details.php?id=<?php echo $customer_id; ?>"
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