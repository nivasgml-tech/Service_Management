<?php

session_start();

if (!isset($_SESSION['admin_id'])) {

    header("Location: login.php");

    exit;
}

require_once "../config/database.php";


/* GET FORM DATA */

$lead_id = $_POST['lead_id'] ?? '';
$status = trim($_POST['status'] ?? '');
$assigned_to = $_POST['assigned_to'] ?? '';
$remarks = trim($_POST['remarks'] ?? '');


/* VALIDATE LEAD ID */

if (!is_numeric($lead_id)) {

    die("Invalid lead.");

}


/* ALLOWED STATUSES */

$allowed_statuses = [

    'New',
    'Contacted',
    'Follow-up',
    'Qualified',
    'Quotation Sent',
    'Negotiation',
    'Won',
    'Lost',
    'Closed'

];


if (!in_array($status, $allowed_statuses, true)) {

    die("Invalid lead status.");

}


/* NORMALIZE ASSIGNED USER */

if ($assigned_to === '') {

    $assigned_to = null;

} else {

    if (!is_numeric($assigned_to)) {

        die("Invalid assigned user.");

    }

    $assigned_to = (int) $assigned_to;

}


/* GET CURRENT LEAD */

$stmt = $pdo->prepare("
    SELECT id, status, assigned_to
    FROM leads
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$lead_id]);

$lead = $stmt->fetch();


if (!$lead) {

    die("Lead not found.");

}


$old_status = $lead['status'];


/* VERIFY ASSIGNED USER */

if ($assigned_to !== null) {

    $stmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE id = ?
        AND status = 1
        LIMIT 1
    ");

    $stmt->execute([$assigned_to]);

    if (!$stmt->fetch()) {

        die("Invalid assigned user.");

    }

}


/* UPDATE LEAD */

$stmt = $pdo->prepare("
    UPDATE leads
    SET
        status = ?,
        assigned_to = ?
    WHERE id = ?
");

$stmt->execute([

    $status,
    $assigned_to,
    $lead_id

]);


/* SAVE STATUS HISTORY ONLY WHEN STATUS CHANGES */

if ($old_status !== $status) {

    $stmt = $pdo->prepare("
        INSERT INTO lead_status_history
        (
            lead_id,
            old_status,
            new_status,
            remarks,
            changed_by
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([

        $lead_id,
        $old_status,
        $status,
        $remarks,
        $_SESSION['admin_id']

    ]);

}


/* REDIRECT */

header(
    "Location: lead-details.php?id="
    . urlencode($lead_id)
);

exit;

?>