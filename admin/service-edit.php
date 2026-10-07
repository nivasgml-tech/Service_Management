<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$service_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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

$error = "";

/*
|--------------------------------------------------------------------------
| Update Service
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $service_name = trim($_POST['service_name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $short_description = trim($_POST['short_description'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $main_image = trim($_POST['main_image'] ?? '');
    $video_url = trim($_POST['video_url'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    $status = isset($_POST['status']) ? 1 : 0;

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($service_name === '') {

        $error = "Service name is required.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Generate Slug if Empty
        |--------------------------------------------------------------------------
        */

        if ($slug === '') {

            $slug = strtolower($service_name);

            $slug = preg_replace('/[^a-zA-Z0-9]+/', '-', $slug);

            $slug = trim($slug, '-');
        }

        /*
        |--------------------------------------------------------------------------
        | Check Slug
        |--------------------------------------------------------------------------
        */

        $slugCheck = $pdo->prepare("
            SELECT id
            FROM services
            WHERE slug = ?
            AND id != ?
            LIMIT 1
        ");

        $slugCheck->execute([
            $slug,
            $service_id
        ]);

        if ($slugCheck->fetch()) {

            $error = "This slug is already used by another service.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Update Service
            |--------------------------------------------------------------------------
            */

            $update = $pdo->prepare("
                UPDATE services
                SET
                    service_name = ?,
                    slug = ?,
                    short_description = ?,
                    description = ?,
                    main_image = ?,
                    video_url = ?,
                    status = ?,
                    sort_order = ?
                WHERE id = ?
            ");

            $update->execute([
                $service_name,
                $slug,
                $short_description,
                $description,
                $main_image,
                $video_url,
                $status,
                $sort_order,
                $service_id
            ]);

            header("Location: services.php");
            exit;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Service</title>

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
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .top-bar h1 {
            margin: 0;
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

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 7px;
        }

        input[type="text"],
        input[type="url"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        textarea {
            min-height: 130px;
            resize: vertical;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .checkbox-group input {
            width: 18px;
            height: 18px;
        }

        .error {
            background: #ffe5e5;
            color: #b00020;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .current-status {
            margin-top: 8px;
            font-size: 13px;
            color: #666;
        }

        .btn-area {
            margin-top: 25px;
            display: flex;
            gap: 10px;
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

        @media (max-width: 700px) {

            .row {
                grid-template-columns: 1fr;
            }

            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

        }

    </style>

</head>

<body>

<div class="page">

    <div class="top-bar">

        <h1>Edit Service</h1>

        <a href="services.php" class="back-btn">
            ← Back to Services
        </a>

    </div>


    <div class="card">

        <?php if ($error !== ''): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <form method="POST">


            <!-- Service Name -->

            <div class="form-group">

                <label>
                    Service Name *
                </label>

                <input
                    type="text"
                    name="service_name"
                    value="<?php echo htmlspecialchars($service['service_name']); ?>"
                    required
                >

            </div>


            <!-- Slug -->

            <div class="form-group">

                <label>
                    Slug
                </label>

                <input
                    type="text"
                    name="slug"
                    value="<?php echo htmlspecialchars($service['slug']); ?>"
                >

                <small>
                    Example: property-management
                </small>

            </div>


            <!-- Short Description -->

            <div class="form-group">

                <label>
                    Short Description
                </label>

                <textarea
                    name="short_description"
                    rows="3"
                ><?php echo htmlspecialchars($service['short_description'] ?? ''); ?></textarea>

            </div>


            <!-- Description -->

            <div class="form-group">

                <label>
                    Full Description
                </label>

                <textarea
                    name="description"
                    rows="7"
                ><?php echo htmlspecialchars($service['description'] ?? ''); ?></textarea>

            </div>


            <!-- Image + Video -->

            <div class="row">

                <div class="form-group">

                    <label>
                        Main Image Path
                    </label>

                    <input
                        type="text"
                        name="main_image"
                        value="<?php echo htmlspecialchars($service['main_image'] ?? ''); ?>"
                        placeholder="assets/images/service.jpg"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Video URL
                    </label>

                    <input
                        type="url"
                        name="video_url"
                        value="<?php echo htmlspecialchars($service['video_url'] ?? ''); ?>"
                        placeholder="https://www.youtube.com/..."
                    >

                </div>

            </div>


            <!-- Sort Order -->

            <div class="form-group">

                <label>
                    Sort Order
                </label>

                <input
                    type="number"
                    name="sort_order"
                    value="<?php echo (int)$service['sort_order']; ?>"
                    min="0"
                >

            </div>


            <!-- Status -->

            <div class="form-group">

                <label>
                    Service Status
                </label>

                <div class="checkbox-group">

                    <input
                        type="checkbox"
                        name="status"
                        value="1"
                        <?php echo ((int)$service['status'] === 1) ? 'checked' : ''; ?>
                    >

                    <span>
                        Active
                    </span>

                </div>

                <div class="current-status">

                    Current status:

                    <strong>
                        <?php
                        echo ((int)$service['status'] === 1)
                            ? 'Active'
                            : 'Inactive';
                        ?>
                    </strong>

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
                    href="services.php"
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