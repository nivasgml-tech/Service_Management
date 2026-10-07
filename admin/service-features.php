<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$service_id = isset($_GET['service_id'])
    ? (int)$_GET['service_id']
    : 0;

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

/*
|--------------------------------------------------------------------------
| Get Features
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM service_features
    WHERE service_id = ?
    ORDER BY sort_order ASC, id ASC
");

$stmt->execute([$service_id]);

$features = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Counts
|--------------------------------------------------------------------------
*/

$total_features = count($features);

$active_features = 0;
$inactive_features = 0;

foreach ($features as $feature) {

    if ((int)$feature['status'] === 1) {
        $active_features++;
    } else {
        $inactive_features++;
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
        Service Features - <?php echo htmlspecialchars($service['service_name']); ?>
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
            max-width: 1200px;
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

        .btn-primary {
            background: #2563eb;
            color: #fff;
        }

        .btn-secondary {
            background: #6b7280;
            color: #fff;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .summary-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #e5e7eb;
        }

        .summary-label {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .summary-value {
            font-size: 26px;
            font-weight: 700;
        }

        .table-box {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th,
        td {
            padding: 14px 15px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #f9fafb;
            font-weight: 700;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .status-active {
            display: inline-block;
            padding: 5px 9px;
            background: #dcfce7;
            color: #166534;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-inactive {
            display: inline-block;
            padding: 5px 9px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .action-edit {
            display: inline-block;
            padding: 7px 12px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 12px;
        }

        .action-delete {
            display: inline-block;
            padding: 7px 12px;
            background: #dc2626;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 12px;
            margin-left: 5px;
        }

        .empty {
            padding: 50px 20px;
            text-align: center;
            color: #6b7280;
        }

        @media (max-width: 700px) {

            .summary {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<div class="page">

    <div class="top-bar">

        <div>

            <h1>Service Features</h1>

            <div class="subtitle">

                Service:
                <strong>
                    <?php echo htmlspecialchars($service['service_name']); ?>
                </strong>

            </div>

        </div>

        <div class="buttons">

            <a
                href="service-feature-add.php?service_id=<?php echo $service_id; ?>"
                class="btn btn-primary"
            >
                + Add Feature
            </a>

            <a
                href="services.php"
                class="btn btn-secondary"
            >
                Back to Services
            </a>

        </div>

    </div>


    <div class="summary">

        <div class="summary-card">

            <div class="summary-label">
                Total Features
            </div>

            <div class="summary-value">
                <?php echo $total_features; ?>
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                Active Features
            </div>

            <div class="summary-value">
                <?php echo $active_features; ?>
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                Inactive Features
            </div>

            <div class="summary-value">
                <?php echo $inactive_features; ?>
            </div>

        </div>

    </div>


    <div class="table-box">

        <?php if (!empty($features)): ?>

            <table>

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Feature</th>

                        <th>Sort Order</th>

                        <th>Status</th>

                        <th>Created</th>

                        <th>Actions</th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach ($features as $feature): ?>

                    <tr>

                        <td>
                            <?php echo (int)$feature['id']; ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $feature['feature_text']
                            );
                            ?>
                        </td>

                        <td>
                            <?php echo (int)$feature['sort_order']; ?>
                        </td>

                        <td>

                            <?php if ((int)$feature['status'] === 1): ?>

                                <span class="status-active">
                                    Active
                                </span>

                            <?php else: ?>

                                <span class="status-inactive">
                                    Inactive
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>
                            <?php echo htmlspecialchars($feature['created_at']); ?>
                        </td>

                        <td>

                            <a
                                href="service-feature-edit.php?id=<?php echo (int)$feature['id']; ?>"
                                class="action-edit"
                            >
                                Edit
                            </a>

                            <a
                                href="service-feature-delete.php?id=<?php echo (int)$feature['id']; ?>"
                                class="action-delete"
                                onclick="return confirm('Are you sure you want to delete this feature?');"
                            >
                                Delete
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        <?php else: ?>

            <div class="empty">

                No features found for this service.

            </div>

        <?php endif; ?>

    </div>

</div>

</body>

</html>