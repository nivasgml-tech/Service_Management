<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$image_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : (int)($_POST['image_id'] ?? 0);

if ($image_id <= 0) {
    header("Location: services.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Fetch Image
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        si.*,
        s.service_name
    FROM service_images si
    INNER JOIN services s
        ON s.id = si.service_id
    WHERE si.id = ?
    LIMIT 1
");

$stmt->execute([$image_id]);

$image = $stmt->fetch();

if (!$image) {
    header("Location: services.php");
    exit;
}

$error = "";


/*
|--------------------------------------------------------------------------
| Update Image
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $image_title = trim($_POST['image_title'] ?? '');

    $sort_order = (int)($_POST['sort_order'] ?? 0);

    $status = isset($_POST['status']) ? 1 : 0;


    if ($sort_order < 0) {
        $sort_order = 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Update Database
    |--------------------------------------------------------------------------
    */

    $update = $pdo->prepare("
        UPDATE service_images
        SET
            image_title = ?,
            sort_order = ?,
            status = ?
        WHERE id = ?
    ");

    $update->execute([
        $image_title,
        $sort_order,
        $status,
        $image_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | Return to Image List
    |--------------------------------------------------------------------------
    */

    header(
        "Location: service-images.php?service_id=" .
        (int)$image['service_id']
    );

    exit;
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
        Edit Service Image
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
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .top-bar h1 {
            margin: 0;
        }

        .service-name {
            margin-top: 6px;
            color: #666;
            font-size: 14px;
        }

        .back-btn {
            text-decoration: none;
            background: #555;
            color: #fff;
            padding: 10px 16px;
            border-radius: 6px;
        }

        .card {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .current-image {
            margin-bottom: 25px;
        }

        .current-image img {
            width: 260px;
            max-width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        .help-text {
            margin-top: 7px;
            font-size: 13px;
            color: #666;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input {
            width: 18px;
            height: 18px;
        }

        .btn-area {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .save-btn {
            border: none;
            background: #0b7a3b;
            color: #fff;
            padding: 12px 22px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
        }

        .cancel-btn {
            text-decoration: none;
            background: #777;
            color: #fff;
            padding: 12px 22px;
            border-radius: 6px;
        }

        @media (max-width: 600px) {

            .top-bar {
                flex-direction: column;
                align-items: flex-start;
            }

        }

    </style>

</head>

<body>

<div class="page">


    <!-- Header -->

    <div class="top-bar">

        <div>

            <h1>Edit Service Image</h1>

            <div class="service-name">

                Service:

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $image['service_name']
                    );
                    ?>
                </strong>

            </div>

        </div>


        <a
            href="service-images.php?service_id=<?php echo (int)$image['service_id']; ?>"
            class="back-btn"
        >
            ← Back
        </a>

    </div>


    <div class="card">


        <!-- Current Image -->

        <div class="current-image">

            <label>
                Current Image
            </label>

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
                    src="<?php echo htmlspecialchars($imagePath); ?>"
                    alt="<?php echo htmlspecialchars(
                        $image['image_title'] ?? ''
                    ); ?>"
                >

            <?php else: ?>

                <p>
                    Image file not found.
                </p>

            <?php endif; ?>

        </div>


        <form method="POST">


            <input
                type="hidden"
                name="image_id"
                value="<?php echo (int)$image['id']; ?>"
            >


            <!-- Image Title -->

            <div class="form-group">

                <label>
                    Image Title
                </label>

                <input
                    type="text"
                    name="image_title"
                    value="<?php echo htmlspecialchars(
                        $image['image_title'] ?? ''
                    ); ?>"
                    placeholder="Example: Modern Apartment Exterior"
                >

            </div>


            <!-- Sort Order -->

            <div class="form-group">

                <label>
                    Sort Order
                </label>

                <input
                    type="number"
                    name="sort_order"
                    value="<?php echo (int)$image['sort_order']; ?>"
                    min="0"
                >

                <div class="help-text">
                    Lower number will appear first.
                </div>

            </div>


            <!-- Status -->

            <div class="form-group">

                <label>
                    Status
                </label>

                <div class="checkbox-group">

                    <input
                        type="checkbox"
                        name="status"
                        value="1"
                        <?php echo (
                            (int)$image['status'] === 1
                        ) ? 'checked' : ''; ?>
                    >

                    <span>
                        Active
                    </span>

                </div>

            </div>


            <!-- Buttons -->

            <div class="btn-area">

                <button
                    type="submit"
                    class="save-btn"
                >
                    Save Changes
                </button>

                <a
                    href="service-images.php?service_id=<?php echo (int)$image['service_id']; ?>"
                    class="cancel-btn"
                >
                    Cancel
                </a>

            </div>


        </form>

    </div>

</div>

</body>

</html>