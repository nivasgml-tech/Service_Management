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
| Add Image
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $image_title = trim($_POST['image_title'] ?? '');

    $sort_order = (int)($_POST['sort_order'] ?? 0);

    $status = isset($_POST['status']) ? 1 : 0;


    /*
    |--------------------------------------------------------------------------
    | Validate Image
    |--------------------------------------------------------------------------
    */

    if (
        !isset($_FILES['service_image'])
        || $_FILES['service_image']['error'] !== UPLOAD_ERR_OK
    ) {

        $error = "Please select an image.";

    } else {

        $file = $_FILES['service_image'];

        $originalName = $file['name'];
        $tmpName = $file['tmp_name'];
        $fileSize = (int)$file['size'];


        /*
        |--------------------------------------------------------------------------
        | File Size - Maximum 5 MB
        |--------------------------------------------------------------------------
        */

        if ($fileSize > 5 * 1024 * 1024) {

            $error = "Image size must be 5 MB or less.";

        } else {


            /*
            |--------------------------------------------------------------------------
            | Allowed Extensions
            |--------------------------------------------------------------------------
            */

            $allowedExtensions = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];

            $extension = strtolower(
                pathinfo($originalName, PATHINFO_EXTENSION)
            );


            if (!in_array($extension, $allowedExtensions, true)) {

                $error = "Only JPG, JPEG, PNG and WEBP images are allowed.";

            } else {


                /*
                |--------------------------------------------------------------------------
                | Verify Actual Image
                |--------------------------------------------------------------------------
                */

                $imageInfo = @getimagesize($tmpName);

                if ($imageInfo === false) {

                    $error = "The selected file is not a valid image.";

                } else {


                    /*
                    |--------------------------------------------------------------------------
                    | Create Upload Folder
                    |--------------------------------------------------------------------------
                    */

                    $uploadDirectory = "../uploads/services/";

                    if (!is_dir($uploadDirectory)) {

                        mkdir(
                            $uploadDirectory,
                            0755,
                            true
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Generate Unique File Name
                    |--------------------------------------------------------------------------
                    */

                    $newFileName =
                        'service_' .
                        $service_id .
                        '_' .
                        time() .
                        '_' .
                        bin2hex(random_bytes(4)) .
                        '.' .
                        $extension;


                    $destination =
                        $uploadDirectory .
                        $newFileName;


                    /*
                    |--------------------------------------------------------------------------
                    | Move File
                    |--------------------------------------------------------------------------
                    */

                    if (!move_uploaded_file(
                        $tmpName,
                        $destination
                    )) {

                        $error = "Unable to upload the image.";

                    } else {


                        /*
                        |--------------------------------------------------------------------------
                        | Database Path
                        |--------------------------------------------------------------------------
                        */

                        $imagePath =
                            "uploads/services/" .
                            $newFileName;


                        /*
                        |--------------------------------------------------------------------------
                        | Insert Image
                        |--------------------------------------------------------------------------
                        */

                        $insert = $pdo->prepare("
                            INSERT INTO service_images
                            (
                                service_id,
                                image_path,
                                image_title,
                                sort_order,
                                status
                            )
                            VALUES (?, ?, ?, ?, ?)
                        ");

                        $insert->execute([
                            $service_id,
                            $imagePath,
                            $image_title,
                            $sort_order,
                            $status
                        ]);


                        /*
                        |--------------------------------------------------------------------------
                        | Success
                        |--------------------------------------------------------------------------
                        */

                        header(
                            "Location: service-images.php?service_id=" .
                            $service_id
                        );

                        exit;
                    }
                }
            }
        }
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
        Add Service Image -
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
            max-width: 800px;
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

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        input[type="text"],
        input[type="number"],
        input[type="file"] {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        input[type="file"] {
            background: #fff;
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

        .error {
            background: #ffe5e5;
            color: #b00020;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
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
                gap: 15px;
            }

        }

    </style>

</head>

<body>

<div class="page">


    <!-- Header -->

    <div class="top-bar">

        <div>

            <h1>Add Service Image</h1>

            <div class="service-name">

                Service:
                <strong>
                    <?php
                    echo htmlspecialchars(
                        $service['service_name']
                    );
                    ?>
                </strong>

            </div>

        </div>


        <a
            href="service-images.php?service_id=<?php echo $service_id; ?>"
            class="back-btn"
        >
            ← Back
        </a>

    </div>


    <!-- Form -->

    <div class="card">


        <?php if ($error !== ''): ?>

            <div class="error">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <input
                type="hidden"
                name="service_id"
                value="<?php echo $service_id; ?>"
            >


            <!-- Image -->

            <div class="form-group">

                <label>
                    Select Image *
                </label>

                <input
                    type="file"
                    name="service_image"
                    accept=".jpg,.jpeg,.png,.webp"
                    required
                >

                <div class="help-text">

                    Allowed:
                    JPG, JPEG, PNG, WEBP

                    <br>

                    Maximum size:
                    5 MB

                </div>

            </div>


            <!-- Image Title -->

            <div class="form-group">

                <label>
                    Image Title
                </label>

                <input
                    type="text"
                    name="image_title"
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
                    value="0"
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
                        checked
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
                    Upload Image
                </button>

                <a
                    href="service-images.php?service_id=<?php echo $service_id; ?>"
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