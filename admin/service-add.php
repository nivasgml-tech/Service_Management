<?php

session_start();

if (!isset($_SESSION['admin_id'])) {

    header("Location: login.php");

    exit;
}

require_once "../config/database.php";


$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $service_name = trim($_POST['service_name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $short_description = trim($_POST['short_description'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $main_image = trim($_POST['main_image'] ?? '');
    $video_url = trim($_POST['video_url'] ?? '');
    $status = isset($_POST['status']) ? 1 : 0;
    $sort_order = (int)($_POST['sort_order'] ?? 0);


    /* REQUIRED */

    if ($service_name === '') {

        $error = "Service name is required.";

    } else {

        /* GENERATE SLUG IF EMPTY */

        if ($slug === '') {

            $slug = strtolower($service_name);

            $slug = preg_replace(
                '/[^a-z0-9]+/',
                '-',
                $slug
            );

            $slug = trim($slug, '-');

        }


        /* CLEAN SLUG */

        $slug = strtolower($slug);

        $slug = preg_replace(
            '/[^a-z0-9-]+/',
            '-',
            $slug
        );

        $slug = trim($slug, '-');


        /* CHECK SLUG */

        $stmt = $pdo->prepare("
            SELECT id
            FROM services
            WHERE slug = ?
            LIMIT 1
        ");

        $stmt->execute([$slug]);

        if ($stmt->fetch()) {

            $error = "This slug already exists. Please use another slug.";

        } else {

            /* INSERT */

            $stmt = $pdo->prepare("
                INSERT INTO services
                (
                    service_name,
                    slug,
                    short_description,
                    description,
                    main_image,
                    video_url,
                    status,
                    sort_order
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([

                $service_name,
                $slug,
                $short_description,
                $description,
                $main_image,
                $video_url,
                $status,
                $sort_order

            ]);


            /* REDIRECT */

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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Add Service | YourDream
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family: Arial, sans-serif;

            background: #f5f7f9;

            color: #263746;

        }


        .topbar {

            background: #071a2d;

            color: #ffffff;

            padding: 18px 30px;

            display: flex;

            justify-content: space-between;

            align-items: center;

        }


        .logo {

            font-size: 24px;

            font-weight: 700;

        }


        .logo span {

            color: #c6a15b;

        }


        .logout-btn {

            color: #ffffff;

            text-decoration: none;

            border: 1px solid #8293a3;

            padding: 10px 20px;

            border-radius: 6px;

        }


        .container {

            max-width: 1000px;

            margin: auto;

            padding: 35px 20px;

        }


        .back-link {

            display: inline-block;

            margin-bottom: 20px;

            color: #10253d;

            text-decoration: none;

            font-weight: 700;

        }


        .page-header {

            margin-bottom: 25px;

        }


        .page-header h1 {

            margin: 0 0 8px 0;

        }


        .page-header p {

            margin: 0;

            color: #687681;

        }


        .card {

            background: #ffffff;

            padding: 30px;

            border-radius: 12px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,0.05);

        }


        .form-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 20px;

        }


        .form-group {

            margin-bottom: 20px;

        }


        .full {

            grid-column: 1 / -1;

        }


        label {

            display: block;

            margin-bottom: 8px;

            font-weight: 700;

            font-size: 14px;

        }


        .required {

            color: #c62828;

        }


        input,
        textarea,
        select {

            width: 100%;

            padding: 12px 13px;

            border: 1px solid #d9e0e6;

            border-radius: 7px;

            font-family: inherit;

            font-size: 14px;

        }


        textarea {

            resize: vertical;

            min-height: 130px;

        }


        .help {

            margin-top: 6px;

            font-size: 12px;

            color: #7a8791;

        }


        .checkbox-row {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .checkbox-row input {

            width: auto;

        }


        .error {

            background: #ffebee;

            color: #c62828;

            padding: 13px 15px;

            border-radius: 7px;

            margin-bottom: 20px;

            font-weight: 700;

        }


        .actions {

            display: flex;

            gap: 10px;

            margin-top: 10px;

        }


        .btn {

            display: inline-block;

            padding: 12px 22px;

            border: 0;

            border-radius: 7px;

            font-weight: 700;

            text-decoration: none;

            cursor: pointer;

        }


        .save-btn {

            background: #10253d;

            color: #ffffff;

        }


        .cancel-btn {

            background: #edf0f2;

            color: #263746;

        }


        @media (max-width: 700px) {

            .form-grid {

                grid-template-columns: 1fr;

            }

            .full {

                grid-column: auto;

            }

        }

    </style>

</head>


<body>


<div class="topbar">

    <div class="logo">

        Your<span>Dream</span> Admin

    </div>


    <a
        href="logout.php"
        class="logout-btn"
    >
        Logout
    </a>

</div>


<div class="container">


    <a
        href="services.php"
        class="back-link"
    >
        ← Back to Services
    </a>


    <div class="page-header">

        <h1>
            Add New Service
        </h1>

        <p>
            Add a new service to your website.
        </p>

    </div>


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
        >


            <div class="form-grid">


                <!-- SERVICE NAME -->

                <div class="form-group">

                    <label>

                        Service Name
                        <span class="required">*</span>

                    </label>

                    <input
                        type="text"
                        name="service_name"
                        value="<?php echo htmlspecialchars($_POST['service_name'] ?? ''); ?>"
                        placeholder="Example: Property Management"
                        required
                    >

                </div>


                <!-- SLUG -->

                <div class="form-group">

                    <label>
                        Slug
                    </label>

                    <input
                        type="text"
                        name="slug"
                        value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>"
                        placeholder="property-management"
                    >

                    <div class="help">

                        Leave empty to generate automatically.

                    </div>

                </div>


                <!-- SHORT DESCRIPTION -->

                <div class="form-group full">

                    <label>
                        Short Description
                    </label>

                    <textarea
                        name="short_description"
                        rows="4"
                        placeholder="Short description shown on the services page..."
                    ><?php echo htmlspecialchars($_POST['short_description'] ?? ''); ?></textarea>

                </div>


                <!-- FULL DESCRIPTION -->

                <div class="form-group full">

                    <label>
                        Full Description
                    </label>

                    <textarea
                        name="description"
                        rows="8"
                        placeholder="Detailed service description..."
                    ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>

                </div>


                <!-- MAIN IMAGE -->

                <div class="form-group">

                    <label>
                        Main Image
                    </label>

                    <input
                        type="text"
                        name="main_image"
                        value="<?php echo htmlspecialchars($_POST['main_image'] ?? ''); ?>"
                        placeholder="assets/images/service.jpg"
                    >

                    <div class="help">

                        Image path for now. File upload will be added later.

                    </div>

                </div>


                <!-- VIDEO -->

                <div class="form-group">

                    <label>
                        Video URL
                    </label>

                    <input
                        type="text"
                        name="video_url"
                        value="<?php echo htmlspecialchars($_POST['video_url'] ?? ''); ?>"
                        placeholder="https://www.youtube.com/watch?v=..."
                    >

                </div>


                <!-- SORT ORDER -->

                <div class="form-group">

                    <label>
                        Sort Order
                    </label>

                    <input
                        type="number"
                        name="sort_order"
                        value="<?php echo htmlspecialchars($_POST['sort_order'] ?? '0'); ?>"
                        min="0"
                    >

                    <div class="help">

                        Lower number appears first.

                    </div>

                </div>


                <!-- STATUS -->

                <div class="form-group">

                    <label>
                        Status
                    </label>

                    <div class="checkbox-row">

                        <input
                            type="checkbox"
                            name="status"
                            value="1"
                            <?php
                            if (
                                !isset($_POST['status'])
                                || isset($_POST['status'])
                            ) {
                                echo 'checked';
                            }
                            ?>
                        >

                        <span>
                            Active
                        </span>

                    </div>

                </div>


            </div>


            <!-- ACTIONS -->

            <div class="actions">

                <button
                    type="submit"
                    class="btn save-btn"
                >
                    Save Service
                </button>


                <a
                    href="services.php"
                    class="btn cancel-btn"
                >
                    Cancel
                </a>

            </div>


        </form>


    </div>


</div>


</body>

</html>
