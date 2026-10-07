<?php

session_start();

if (!isset($_SESSION['admin_id'])) {

    header("Location: login.php");

    exit;
}

require_once "../config/database.php";


/* GET FORM DATA */

$lead_id = $_POST['lead_id'] ?? '';
$followup_date = trim($_POST['followup_date'] ?? '');
$followup_type = trim($_POST['followup_type'] ?? '');
$notes = trim($_POST['notes'] ?? '');


/* VALIDATE LEAD ID */

if (!is_numeric($lead_id)) {

    die("Invalid lead.");

}


/* VALIDATE DATE */

if ($followup_date === '') {

    die("Follow-up date and time is required.");

}


/* ALLOWED FOLLOW-UP TYPES */

$allowed_types = [

    'Call',
    'WhatsApp',
    'Meeting',
    'Email',
    'Other'

];


if (!in_array($followup_type, $allowed_types, true)) {

    $followup_type = 'Other';

}


/* VERIFY LEAD EXISTS */

$stmt = $pdo->prepare("
    SELECT id
    FROM leads
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$lead_id]);

$lead = $stmt->fetch();


if (!$lead) {

    die("Lead not found.");

}


/* CONVERT DATETIME */

$timestamp = strtotime($followup_date);

if ($timestamp === false) {

    die("Invalid follow-up date.");

}

$followup_date_mysql = date(
    'Y-m-d H:i:s',
    $timestamp
);


/* INSERT FOLLOW-UP */

$stmt = $pdo->prepare("
    INSERT INTO lead_followups
    (
        lead_id,
        followup_date,
        followup_type,
        notes,
        created_by
    )
    VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([

    $lead_id,
    $followup_date_mysql,
    $followup_type,
    $notes,
    $_SESSION['admin_id']

]);


/* REDIRECT */

header(
    "Location: lead-details.php?id="
    . urlencode($lead_id)
);

exit;

?>