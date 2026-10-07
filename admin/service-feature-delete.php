<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$feature_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($feature_id <= 0) {
    header("Location: services.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Feature
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM service_features
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$feature_id]);

$feature = $stmt->fetch();

if (!$feature) {
    header("Location: services.php");
    exit;
}

$service_id = (int)$feature['service_id'];

/*
|--------------------------------------------------------------------------
| Delete Feature
|--------------------------------------------------------------------------
*/

$delete = $pdo->prepare("
    DELETE FROM service_features
    WHERE id = ?
");

$delete->execute([$feature_id]);

/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header(
    "Location: service-features.php?service_id=" .
    $service_id
);

exit;