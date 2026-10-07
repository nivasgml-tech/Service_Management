<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$service_id = isset($_GET['service_id'])
    ? (int)$_GET['service_id']
    : (int)($_POST['service_id'] ?? 0);

if ($service_id <= 0) {
    header("Location: services.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Service
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM services
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$service_id]);

$service = $stmt->fetch();

if (!$service) {
    header("Location: services.php");
    exit;
}

$error = "";

$feature_text = "";
$sort_order = 0;
$status = 1;

/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $feature_text = trim($_POST['feature_text'] ?? '');

    $sort_order = (int)($_POST['sort_order'] ?? 0);

    $status = isset($_POST['status']) ? 1 : 0;

    if ($feature_text === '') {

        $error = "Please enter the feature.";

    } elseif (mb_strlen($feature_text) > 255) {

        $error = "Feature must not exceed 255 characters.";

    } else {

        if ($sort_order < 0) {
            $sort_order = 0;
        }

        $insert = $pdo->prepare("
            INSERT INTO service_features
            (
                service_id,
                feature_text,
                sort_order,
                status
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?
            )
        ");

        $insert->execute([
            $service_id,
            $feature_text,
            $sort_order,
            $status
        ]);

        header(
            "Location: service-features.php?service_id=" .
            $service_id
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
        Add Feature - <?php echo htmlspecialchars($service['service_name']); ?>
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
            max-width: 800px;
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

        .required {
            color: #dc2626;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        input:focus {
            outline: none;
            border-color: #2563eb;
        }

        .help {
            margin-top: 6px;
            font-size: 12px;
            color: #6b7280;
        }

        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-row label {
            margin: 0;
            font-weight: 600;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 12px 14px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .btn-save {
            border: none;
            background: #2563eb;
            color: #fff;
            padding: 11px 18px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-cancel {
            display: inline-block;
            background: #6b7280;
            color: #fff;
            padding: 11px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

    </style>

</head>

<body>

<div class="page">

    <div class="top-bar">

        <div>

            <h1>Add Feature</h1>

            <div class="subtitle">

                Service:
                <strong>
                    <?php echo htmlspecialchars($service['service_name']); ?>
                </strong>

            </div>

        </div>

        <a
            href="service-features.php?service_id=<?php echo $service_id; ?>"
            class="btn btn-secondary"
        >
            Back to Features
        </a>

    </div>


    <div class="form-box">

        <?php if ($error !== ''): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <input
                type="hidden"
                name="service_id"
                value="<?php echo $service_id; ?>"
            >


            <div class="form-group">

                <label>
                    Feature
                    <span class="required">*</span>
                </label>

                <input
                    type="text"
                    name="feature_text"
                    value="<?php echo htmlspecialchars($feature_text); ?>"
                    maxlength="255"
                    placeholder="Enter service feature"
                    required
                >

                <div class="help">
                    Maximum 255 characters.
                </div>

            </div>


            <div class="form-group">

                <label>
                    Sort Order
                </label>

                <input
                    type="number"
                    name="sort_order"
                    value="<?php echo (int)$sort_order; ?>"
                    min="0"
                >

                <div class="help">
                    Lower numbers will appear first.
                </div>

            </div>


            <div class="form-group">

                <div class="checkbox-row">

                    <input
                        type="checkbox"
                        name="status"
                        value="1"
                        id="status"
                        <?php echo ($status === 1) ? 'checked' : ''; ?>
                    >

                    <label for="status">
                        Active
                    </label>

                </div>

                <div class="help">
                    Only active features will be displayed publicly.
                </div>

            </div>


            <div class="form-actions">

                <button
                    type="submit"
                    class="btn-save"
                >
                    Save Feature
                </button>

                <a
                    href="service-features.php?service_id=<?php echo $service_id; ?>"
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