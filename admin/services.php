
<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

/* GET ALL SERVICES */

$stmt = $pdo->query("
    SELECT *
    FROM services
    ORDER BY sort_order ASC, id ASC
");

$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* COUNTS */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM services
");

$total_services = (int) $stmt->fetchColumn();

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM services
    WHERE status = 1
");

$active_services = (int) $stmt->fetchColumn();

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM services
    WHERE status = 0
");

$inactive_services = (int) $stmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Services Management | YourDream</title>

    <!-- Common Admin CSS -->
    <link
        rel="stylesheet" href="assets/css/admin-style.css?v=2"
    >
</head>

<body class="admin-page">

    <!-- TOP BAR -->

    <div class="topbar">

        <div class="logo">
            Your<span>Dream</span> Admin
        </div>

        <a href="logout.php" class="logout-btn">
            Logout
        </a>

    </div>

    <div class="container">

        <!-- BACK TO DASHBOARD -->

        <a href="dashboard.php" class="back-link">
            ← Dashboard
        </a>

        <!-- PAGE HEADER -->

        <div class="page-header">

            <div>
                <h1>Services Management</h1>

                <p>
                    Manage website services and their content.
                </p>
            </div>

            <a href="service-add.php" class="add-btn">
                + Add Service
            </a>

        </div>

        <!-- SUMMARY CARDS -->

        <div class="summary">

            <div class="summary-card">

                <div class="summary-label">
                    Total Services
                </div>

                <div class="summary-number">
                    <?= $total_services ?>
                </div>

            </div>

            <div class="summary-card">

                <div class="summary-label">
                    Active Services
                </div>

                <div class="summary-number">
                    <?= $active_services ?>
                </div>

            </div>

            <div class="summary-card">

                <div class="summary-label">
                    Inactive Services
                </div>

                <div class="summary-number">
                    <?= $inactive_services ?>
                </div>

            </div>

        </div>

        <!-- SERVICES TABLE -->

        <div class="table-card">

            <?php if (!empty($services)): ?>

                <table>

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Service</th>
                            <th>Short Description</th>
                            <th>Status</th>
                            <th>Sort Order</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($services as $service): ?>

                            <tr>

                                <!-- ID -->

                                <td>
                                    <?= (int) $service['id'] ?>
                                </td>

                                <!-- SERVICE -->

                                <td>

                                    <div class="service-name">
                                        <?= htmlspecialchars(
                                            $service['service_name'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </div>

                                    <div class="slug">
                                        /
                                        <?= htmlspecialchars(
                                            $service['slug'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </div>

                                </td>

                                <!-- DESCRIPTION -->

                                <td>

                                    <div class="description">

                                        <?php

                                        $description = trim(
                                            $service['short_description'] ?? ''
                                        );

                                        if ($description === '') {

                                            echo '-';

                                        } elseif (strlen($description) > 120) {

                                            echo htmlspecialchars(
                                                substr($description, 0, 120) . '...',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );

                                        } else {

                                            echo htmlspecialchars(
                                                $description,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );

                                        }

                                        ?>

                                    </div>

                                </td>

                                <!-- STATUS -->

                                <td>

                                    <?php if ((int) $service['status'] === 1): ?>

                                        <span class="status active">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="status inactive">
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <!-- SORT ORDER -->

                                <td>
                                    <?= (int) $service['sort_order'] ?>
                                </td>

                                <!-- CREATED DATE -->

                                <td>

                                    <?php

                                    if (!empty($service['created_at'])) {
                                        echo date(
                                            'd-m-Y',
                                            strtotime($service['created_at'])
                                        );
                                    } else {
                                        echo '-';
                                    }

                                    ?>

                                </td>

                                <!-- ACTION BUTTONS -->

                                <td class="service-actions">

                                    <a
                                        href="service-edit.php?id=<?= (int) $service['id'] ?>"
                                        class="action-edit"
                                    >
                                        Edit
                                    </a>

                                    <a
                                        href="service-images.php?service_id=<?= (int) $service['id'] ?>"
                                        class="action-images"
                                    >
                                        Images
                                    </a>

                                    <a
                                        href="service-features.php?service_id=<?= (int) $service['id'] ?>"
                                        class="action-features"
                                    >
                                        Features
                                    </a>

                                    <a
                                        href="service-faqs.php?service_id=<?= (int) $service['id'] ?>"
                                        class="action-faqs"
                                    >
                                        FAQs
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <div class="empty">
                    No services found.
                </div>

            <?php endif; ?>

        </div>

    </div>

</body>
</html>
