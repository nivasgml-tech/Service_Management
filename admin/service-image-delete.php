<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$image_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($image_id <= 0) {
    header("Location: services.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Image Details
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM service_images
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$image_id]);

$image = $stmt->fetch();

if (!$image) {
    header("Location: services.php");
    exit;
}

$service_id = (int)$image['service_id'];

/*
|--------------------------------------------------------------------------
| Delete Physical Image File
|--------------------------------------------------------------------------
*/

$image_path = "../" . $image['image_path'];

if (
    !empty($image['image_path']) &&
    file_exists($image_path) &&
    is_file($image_path)
) {
    unlink($image_path);
}

/*
|--------------------------------------------------------------------------
| Delete Database Record
|--------------------------------------------------------------------------
*/

$delete = $pdo->prepare("
    DELETE FROM service_images
    WHERE id = ?
");

$delete->execute([$image_id]);

/*
|--------------------------------------------------------------------------
| Redirect Back
|--------------------------------------------------------------------------
*/

header(
    "Location: service-images.php?service_id=" .
    $service_id
);

exit;