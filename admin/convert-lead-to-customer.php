```php
<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: leads.php");
    exit;
}

$lead_id = filter_input(INPUT_POST, 'lead_id', FILTER_VALIDATE_INT);

if (!$lead_id || $lead_id <= 0) {
    header("Location: leads.php");
    exit;
}

try {

    $pdo->beginTransaction();

    // Get the lead
    $stmt = $pdo->prepare("
        SELECT *
        FROM leads
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([$lead_id]);
    $lead = $stmt->fetch();

    if (!$lead) {
        throw new Exception("Lead not found.");
    }

    // If already linked, reuse that customer
    if (!empty($lead['customer_id'])) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM customers
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$lead['customer_id']]);
        $customer = $stmt->fetch();

        if ($customer) {
            $customer_id = (int)$customer['id'];
        } else {
            // Repair a stale customer_id link
            $customer_id = null;
        }

    } else {
        $customer_id = null;
    }

    // If not linked, look for an existing customer by mobile
    if (!$customer_id) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM customers
            WHERE mobile = ?
            ORDER BY id ASC
            LIMIT 1
        ");
        $stmt->execute([$lead['mobile']]);
        $customer = $stmt->fetch();

        if ($customer) {

            $customer_id = (int)$customer['id'];

        } else {

            // Create a new customer
            $stmt = $pdo->prepare("
                INSERT INTO customers
                    (name, mobile, email)
                VALUES (?, ?, ?)
            ");

            $stmt->execute([
                $lead['name'],
                $lead['mobile'],
                !empty($lead['email']) ? $lead['email'] : null
            ]);

            $customer_id = (int)$pdo->lastInsertId();
        }

        // Link customer to lead
        $stmt = $pdo->prepare("
            UPDATE leads
            SET customer_id = ?
            WHERE id = ?
        ");

        $stmt->execute([$customer_id, $lead_id]);
    }

    $pdo->commit();

    header(
        "Location: customer-details.php?id=" . $customer_id
    );
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Lead conversion failed: " . $e->getMessage());

    http_response_code(500);
    echo "Unable to convert this lead into a customer. Please check the PHP error log.";
}

