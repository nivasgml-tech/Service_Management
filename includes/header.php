
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>YourDreamHome | Property, Construction & Finance</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link rel="stylesheet" href="/service-management/assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container">
        <a href="index.php" class="logo">YourDreamHome</a>

        <nav class="main-nav">
            <a href="index.php">Home</a>
            <a href="services.php">Services</a>
            <a href="enquiry.php">Enquiry</a>
        </nav>
    </div>
</header>
