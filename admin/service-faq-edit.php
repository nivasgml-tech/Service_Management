<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$faq_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : (int)($_POST['id'] ?? 0);

if ($faq_id <= 0) {
    header("Location: services.php");
    exit;
}

/* Get FAQ */

$stmt = $pdo->prepare("
    SELECT
        service_faqs.*,
        services.service_name
    FROM service_faqs
    INNER JOIN services
        ON services.id = service_faqs.service_id
    WHERE service_faqs.id = ?
    LIMIT 1
");

$stmt->execute([$faq_id]);

$faq = $stmt->fetch();

if (!$faq) {
    header("Location: services.php");
    exit;
}

$service_id = (int)$faq['service_id'];

$error = "";

$question = $faq['question'];
$answer = $faq['answer'];
$sort_order = (int)$faq['sort_order'];
$status = (int)$faq['status'];

/* Update FAQ */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $question = trim($_POST['question'] ?? '');
    $answer = trim($_POST['answer'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $status = isset($_POST['status']) ? 1 : 0;

    if ($question === '') {

        $error = "Please enter the FAQ question.";

    } elseif ($answer === '') {

        $error = "Please enter the FAQ answer.";

    } else {

        $stmt = $pdo->prepare("
            UPDATE service_faqs
            SET
                question = ?,
                answer = ?,
                sort_order = ?,
                status = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $question,
            $answer,
            $sort_order,
            $status,
            $faq_id
        ]);

        header(
            "Location: service-faqs.php?service_id=" . $service_id
        );
        exit;
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
    Edit FAQ - <?php echo htmlspecialchars($faq['service_name']); ?>
</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f7fb;
    color: #1f2937;
}

.page {
    max-width: 900px;
    margin: 0 auto;
    padding: 30px 20px;
}

.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

h1 {
    margin: 0;
    font-size: 28px;
}

.subtitle {
    margin-top: 6px;
    color: #6b7280;
    font-size: 14px;
}

.buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn {
    display: inline-block;
    padding: 10px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
}

.btn-secondary {
    background: #6b7280;
    color: #fff;
}

.form-box {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 25px;
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 600;
}

input[type="text"],
input[type="number"],
textarea {
    width: 100%;
    padding: 12px 13px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    font-family: Arial, sans-serif;
}

textarea {
    min-height: 180px;
    resize: vertical;
}

input:focus,
textarea:focus {
    outline: none;
    border-color: #2563eb;
}

.checkbox-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.checkbox-row label {
    margin: 0;
    font-weight: 500;
}

.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
    padding: 12px 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 14px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 25px;
    flex-wrap: wrap;
}

.btn-save {
    border: none;
    background: #2563eb;
    color: #fff;
    padding: 11px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.btn-cancel {
    background: #6b7280;
    color: #fff;
    padding: 11px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
}

.help-text {
    margin-top: 6px;
    color: #6b7280;
    font-size: 12px;
}

</style>

</head>

<body>

<div class="page">

    <div class="top-bar">

        <div>

            <h1>Edit Service FAQ</h1>

            <div class="subtitle">
                Service:
                <strong>
                    <?php
                    echo htmlspecialchars(
                        $faq['service_name']
                    );
                    ?>
                </strong>
            </div>

        </div>

        <div class="buttons">

            <a
                href="service-faqs.php?service_id=<?php echo $service_id; ?>"
                class="btn btn-secondary"
            >
                Back to FAQs
            </a>

        </div>

    </div>


    <?php if ($error !== ''): ?>

        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <div class="form-box">

        <form method="POST">

            <input
                type="hidden"
                name="id"
                value="<?php echo $faq_id; ?>"
            >


            <div class="form-group">

                <label for="question">
                    FAQ Question *
                </label>

                <input
                    type="text"
                    id="question"
                    name="question"
                    maxlength="500"
                    value="<?php echo htmlspecialchars($question); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label for="answer">
                    FAQ Answer *
                </label>

                <textarea
                    id="answer"
                    name="answer"
                    required
                ><?php echo htmlspecialchars($answer); ?></textarea>

            </div>


            <div class="form-group">

                <label for="sort_order">
                    Sort Order
                </label>

                <input
                    type="number"
                    id="sort_order"
                    name="sort_order"
                    value="<?php echo $sort_order; ?>"
                    min="0"
                >

                <div class="help-text">
                    Lower number will appear first.
                </div>

            </div>


            <div class="form-group">

                <div class="checkbox-row">

                    <input
                        type="checkbox"
                        id="status"
                        name="status"
                        value="1"
                        <?php echo $status === 1 ? 'checked' : ''; ?>
                    >

                    <label for="status">
                        Active
                    </label>

                </div>

            </div>


            <div class="form-actions">

                <button
                    type="submit"
                    class="btn-save"
                >
                    Update FAQ
                </button>

                <a
                    href="service-faqs.php?service_id=<?php echo $service_id; ?>"
                    class="btn-cancel"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</body>
</html>