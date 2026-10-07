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
| Fetch Service
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
| Fetch Images
|--------------------------------------------------------------------------
*/

$imageStmt = $pdo->prepare("
    SELECT *
    FROM service_images
    WHERE service_id = ?
    ORDER BY sort_order ASC, id ASC
");

$imageStmt->execute([$service_id]);

$images = $imageStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Counts
|--------------------------------------------------------------------------
*/

$totalImages = count($images);

$activeImages = 0;
$inactiveImages = 0;

foreach ($images as $image) {

    if ((int)$image['status'] === 1) {
        $activeImages++;
    } else {
        $inactiveImages++;
    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Service Images -
        <?php echo htmlspecialchars($service['service_name']); ?>
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #222;
        }

        .page {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .top-bar h1 {
            margin: 0 0 5px;
            font-size: 28px;
        }

        .service-name {
            color: #666;
            font-size: 14px;
        }

        .top-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-back {
            background: #555;
            color: #fff;
        }

        .btn-add {
            background: #0b7a3b;
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
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .summary-card h3 {
            margin: 0 0 8px;
            font-size: 14px;
            color: #666;
        }

        .summary-card strong {
            font-size: 26px;
        }

        .card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.07);
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #f8f9fa;
            font-size: 13px;
        }

        .image-preview {
            width: 110px;
            height: 75px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #ddd;
            background: #f1f1f1;
        }

        .no-image {
            width: 110px;
            height: 75px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eee;
            color: #777;
            border-radius: 6px;
            font-size: 12px;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-active {
            background: #dff5e7;
            color: #137333;
        }

        .status-inactive {
            background: #fce8e6;
            color: #b3261e;
        }

        .empty {
            text-align: center;
            padding: 50px 20px;
            color: #777;
        }

        .empty h3 {
            margin-bottom: 8px;
            color: #444;
        }

        @media (max-width: 700px) {

            .top-bar {
                flex-direction: column;
                align-items: flex-start;
            }

            .top-actions {
                width: 100%;
            }

            .top-actions .btn {
                flex: 1;
                text-align: center;
            }

            .summary {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<div class="page">


    <!-- Top Bar -->

    <div class="top-bar">

        <div>

            <h1>Service Images</h1>

            <div class="service-name">

                Service:
                <strong>
                    <?php echo htmlspecialchars($service['service_name']); ?>
                </strong>

            </div>

        </div>


        <div class="top-actions">

            <a
                href="services.php"
                class="btn btn-back"
            >
                ← Services
            </a>

            <a
                href="service-image-add.php?service_id=<?php echo $service_id; ?>"
                class="btn btn-add"
            >
                + Add Image
            </a>

        </div>

    </div>


    <!-- Summary -->

    <div class="summary">

        <div class="summary-card">

            <h3>Total Images</h3>

            <strong>
                <?php echo $totalImages; ?>
            </strong>

        </div>


        <div class="summary-card">

            <h3>Active Images</h3>

            <strong>
                <?php echo $activeImages; ?>
            </strong>

        </div>


        <div class="summary-card">

            <h3>Inactive Images</h3>

            <strong>
                <?php echo $inactiveImages; ?>
            </strong>

        </div>

    </div>


    <!-- Image List -->

    <div class="card">

        <?php if (empty($images)): ?>

            <div class="empty">

                <h3>No Images Added</h3>

                <p>
                    No images have been added for this service yet.
                </p>

                <a
                    href="service-image-add.php?service_id=<?php echo $service_id; ?>"
                    class="btn btn-add"
                >
                    + Add First Image
                </a>

            </div>

        <?php else: ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Image</th>

                            <th>Title</th>

                            <th>Sort Order</th>

                            <th>Status</th>

                            <th>Created</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($images as $image): ?>

                        <tr>

                            <td>
                                <?php echo (int)$image['id']; ?>
                            </td>


                            <td>

                                <?php

                                $imagePath = "../" . ltrim(
                                    $image['image_path'],
                                    "/"
                                );

                                ?>

                                <?php if (
                                    !empty($image['image_path'])
                                    && file_exists($imagePath)
                                ): ?>

                                    <img
                                        src="<?php echo htmlspecialchars(
                                            $imagePath
                                        ); ?>"
                                        class="image-preview"
                                        alt="<?php echo htmlspecialchars(
                                            $image['image_title'] ?? ''
                                        ); ?>"
                                    >

                                <?php else: ?>

                                    <div class="no-image">
                                        Image not found
                                    </div>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $image['image_title'] ?? ''
                                );

                                ?>

                            </td>


                            <td>
                                <?php echo (int)$image['sort_order']; ?>
                            </td>


                            <td>

                                <?php if ((int)$image['status'] === 1): ?>

                                    <span class="status status-active">
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span class="status status-inactive">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?php

                                echo date(
                                    "d-m-Y",
                                    strtotime($image['created_at'])
                                );

                                ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>


</div>

</body>

</html>
