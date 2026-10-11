
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


<header class="site-header ydh-header">
    <div class="container">
        <div class="ydh-header-inner">

            <a href="/service-management/index.php" class="ydh-logo">
                <span class="ydh-logo-name">YourDreamHome</span>
                <span class="ydh-logo-tagline">PROPERTY · CONSTRUCTION · FINANCE</span>
            </a>

            <nav class="main-nav ydh-main-nav">
                <a href="/service-management/index.php">Home</a>
                <a href="/service-management/services.php">Services</a>
                <a href="/service-management/enquiry.php">Enquiry</a>
            </nav>

        </div>
    </div>
</header>

