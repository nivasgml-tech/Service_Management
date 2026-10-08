<?php

require_once "config/database.php";

$slug = trim($_GET['slug'] ?? '');

if ($slug === '') {
    die("Invalid service.");
}


/* GET SERVICE */

$stmt = $pdo->prepare("
    SELECT *
    FROM services
    WHERE slug = ?
    AND status = 1
    LIMIT 1
");

$stmt->execute([$slug]);

$service = $stmt->fetch();

if (!$service) {
    die("Service not found.");
}


/* GET FEATURES */

$stmt = $pdo->prepare("
    SELECT *
    FROM service_features
    WHERE service_id = ?
    AND status = 1
    ORDER BY sort_order ASC, id ASC
");

$stmt->execute([$service['id']]);

$features = $stmt->fetchAll();


/* GET FAQS */

$stmt = $pdo->prepare("
    SELECT *
    FROM service_faqs
    WHERE service_id = ?
    AND status = 1
    ORDER BY sort_order ASC, id ASC
");

$stmt->execute([$service['id']]);

$faqs = $stmt->fetchAll();


/* GET ACTIVE SERVICE IMAGES */

$imageStmt = $pdo->prepare("
    SELECT *
    FROM service_images
    WHERE service_id = ?
    AND status = 1
    ORDER BY sort_order ASC, id ASC
");

$imageStmt->execute([$service['id']]);

$service_images = $imageStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| MAIN IMAGE
|--------------------------------------------------------------------------
| Use the service main_image when it is valid.
| If it is empty or the local file no longer exists, use the first
| active gallery image as the main image.
|--------------------------------------------------------------------------
*/

$main_image = trim($service['main_image'] ?? '');

if ($main_image !== '') {

    /* Keep remote URLs as they are. */

    $is_remote_image = preg_match(
        '/^(https?:)?\\/\\//i',
        $main_image
    );

    if (!$is_remote_image) {

        $main_image_file = __DIR__ . '/' . ltrim(
            $main_image,
            '/'
        );

        if (!is_file($main_image_file)) {
            $main_image = '';
        }
    }
}


/* FALLBACK TO FIRST GALLERY IMAGE */

if ($main_image === '' && !empty($service_images)) {
    $main_image = $service_images[0]['image_path'];
}


require_once "includes/header.php";

?>

<!-- =========================================================
     SERVICE HERO
========================================================= -->

<section class="service-detail-hero">

    <div class="container">

        <span class="service-detail-label">
            OUR SERVICE
        </span>

        <h1>
            <?php echo htmlspecialchars($service['service_name']); ?>
        </h1>

        <?php if (!empty($service['short_description'])): ?>

            <p>
                <?php
                echo nl2br(
                    htmlspecialchars(
                        $service['short_description']
                    )
                );
                ?>
            </p>

        <?php endif; ?>

    </div>

</section>


<!-- =========================================================
     SERVICE CONTENT
========================================================= -->

<section class="service-detail-section">

    <div class="container">

        <!-- TOP TWO-COLUMN SECTION ONLY -->

        <div class="service-detail-grid">


            <!-- IMAGE -->

            <div class="service-detail-image">

                <?php if ($main_image !== ''): ?>

                    <img
                        src="<?php echo htmlspecialchars($main_image); ?>"
                        alt="<?php echo htmlspecialchars($service['service_name']); ?>"
                    >

                <?php else: ?>

                    <div class="service-detail-placeholder">

                        <?php
                        echo htmlspecialchars(
                            $service['service_name']
                        );
                        ?>

                    </div>

                <?php endif; ?>

            </div>


            <!-- CONTENT -->

            <div class="service-detail-content">

                <span class="section-label">
                    ABOUT THIS SERVICE
                </span>

                <h2>
                    <?php
                    echo htmlspecialchars(
                        $service['service_name']
                    );
                    ?>
                </h2>


                <?php if (!empty($service['description'])): ?>

                    <div class="service-description">

                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $service['description']
                            )
                        );
                        ?>

                    </div>

                <?php endif; ?>


                <?php if (!empty($service['video_url'])): ?>

                    <a
                        href="<?php echo htmlspecialchars($service['video_url']); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-primary"
                    >
                        Watch Video
                    </a>

                <?php endif; ?>

            </div>

        </div>


        <!-- =====================================================
             GALLERY - OUTSIDE THE TWO-COLUMN GRID
        ====================================================== -->

        <?php if (!empty($service_images)): ?>

            <div class="service-gallery">

                <div class="section-heading">

                    <span>
                        PROJECT GALLERY
                    </span>

                    <h2>
                        Gallery
                    </h2>

                    <p>
                        Explore images related to this service.
                    </p>

                </div>


                <div class="service-gallery-grid">

                    <?php foreach ($service_images as $image): ?>

                        <div class="service-gallery-item">

                            <img
                                src="<?php echo htmlspecialchars($image['image_path']); ?>"
                                alt="<?php echo htmlspecialchars(
                                    $image['image_title'] ?: $service['service_name']
                                ); ?>"
                            >

                            <?php if (!empty($image['image_title'])): ?>

                                <div class="service-gallery-title">

                                    <?php
                                    echo htmlspecialchars(
                                        $image['image_title']
                                    );
                                    ?>

                                </div>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php endif; ?>


        <!-- =====================================================
             FEATURES
        ====================================================== -->

        <?php if (count($features) > 0): ?>

            <div class="service-features">

                <div class="section-heading">

                    <span>
                        WHY CHOOSE THIS SERVICE
                    </span>

                    <h2>
                        What We Offer
                    </h2>

                </div>


                <div class="feature-grid">

                    <?php foreach ($features as $feature): ?>

                        <div class="feature-card">

                            <div class="feature-icon">
                                ✓
                            </div>

                            <p>
                                <?php
                                echo htmlspecialchars(
                                    $feature['feature_text']
                                );
                                ?>
                            </p>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php endif; ?>


        <!-- =====================================================
             FAQ
        ====================================================== -->

        <?php if (count($faqs) > 0): ?>

            <div class="service-faq">

                <div class="section-heading">

                    <span>
                        FAQ
                    </span>

                    <h2>
                        Frequently Asked Questions
                    </h2>

                </div>


                <div class="faq-list">

                    <?php foreach ($faqs as $faq): ?>

                        <div class="faq-item">

                            <h3>
                                <?php
                                echo htmlspecialchars(
                                    $faq['question']
                                );
                                ?>
                            </h3>

                            <p>
                                <?php
                                echo nl2br(
                                    htmlspecialchars(
                                        $faq['answer']
                                    )
                                );
                                ?>
                            </p>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php endif; ?>


        <!-- =====================================================
             ENQUIRY CTA
        ====================================================== -->

        <div class="service-enquiry" id="enquiry">

            <div class="enquiry-content">

                <span class="section-label">
                    GET IN TOUCH
                </span>

                <h2>
                    Interested in this service?
                </h2>

                <p>
                    Share your requirement with us.
                    Our team will contact you shortly.
                </p>

            </div>


            <div class="enquiry-button">

                <a
                    href="enquiry.php?service=<?php echo urlencode($service['slug']); ?>"
                    class="btn btn-primary"
                >
                    Send Enquiry
                </a>

            </div>

        </div>

    </div>

</section>


<?php

require_once "includes/footer.php";

?>
