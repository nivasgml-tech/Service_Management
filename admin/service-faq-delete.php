<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$faq_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($faq_id <= 0) {
    header("Location: services.php");
    exit;
}

/* Get FAQ */

$stmt = $pdo->prepare("
    SELECT *
    FROM service_faqs
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$faq_id]);

$faq = $stmt->fetch();

if (!$faq) {
    header("Location: services.php");
    exit;
}

$service_id = (int)$faq['service_id'];

/* Delete FAQ */

$stmt = $pdo->prepare("
    DELETE FROM service_faqs
    WHERE id = ?
");

$stmt->execute([$faq_id]);

/* Return to FAQ List */

header(
    "Location: service-faqs.php?service_id=" . $service_id
);

exit;