<?php

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| Get Active Services
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT *
    FROM services
    WHERE status = 1
    ORDER BY sort_order ASC, id ASC
");

$services = $stmt->fetchAll();

?>

<?php require_once "includes/header.php"; ?>


<main class="public-services-page">

    <!-- =========================================================
         PAGE HERO
    ========================================================== -->

    <section class="services-hero">

        <div class="container">

            <div class="services-hero-content">

                <div class="section-eyebrow">
                    OUR SERVICES
                </div>

                <h1>
                    Solutions Designed Around Your Needs
                </h1>

                <p>
                    Explore our property, construction, financial,
                    loan and consulting services.
                </p>

            </div>

        </div>

    </section>


    <!-- =========================================================
         SERVICES LIST
    ========================================================== -->

    <section class="services-list-section">

        <div class="container">

            <?php if (!empty($services)): ?>

                <div class="public-services-grid">

                    <?php foreach ($services as $service): ?>

                        <?php

                        /*
                        |--------------------------------------------------------------------------
                        | Get First Active Gallery Image
                        |--------------------------------------------------------------------------
                        */

                        $imageStmt = $pdo->prepare("
                            SELECT image_path
                            FROM service_images
                            WHERE service_id = ?
                            AND status = 1
                            ORDER BY sort_order ASC, id ASC
                            LIMIT 1
                        ");

                        $imageStmt->execute([
                            $service['id']
                        ]);

                        $serviceImage = $imageStmt->fetchColumn();

                        /*
                        |--------------------------------------------------------------------------
                        | Main Image Fallback
                        |--------------------------------------------------------------------------
                        */

                        $displayImage = '';

                        if (!empty($service['main_image'])) {

                            $mainImageFile =
                                __DIR__ . "/" .
                                ltrim($service['main_image'], "/");

                            if (file_exists($mainImageFile)) {
                                $displayImage =
                                    $service['main_image'];
                            }
                        }

                        if (
                            $displayImage === '' &&
                            !empty($serviceImage)
                        ) {
                            $displayImage = $serviceImage;
                        }

                        ?>

                        <article class="public-service-card">


                            <!-- SERVICE IMAGE -->

                            <div class="public-service-image">

                                <?php if ($displayImage !== ''): ?>

                                    <img
                                        src="<?php
                                        echo htmlspecialchars(
                                            $displayImage
                                        );
                                        ?>"
                                        alt="<?php
                                        echo htmlspecialchars(
                                            $service['service_name']
                                        );
                                        ?>"
                                    >

                                <?php else: ?>

                                    <div class="public-service-placeholder">
                                        <span>
                                            <?php
                                            echo htmlspecialchars(
                                                $service['service_name']
                                            );
                                            ?>
                                        </span>
                                    </div>

                                <?php endif; ?>

                            </div>


                            <!-- SERVICE CONTENT -->

                            <div class="public-service-content">

                                <h2>
                                    <?php
                                    echo htmlspecialchars(
                                        $service['service_name']
                                    );
                                    ?>
                                </h2>


                                <?php if (
                                    !empty(
                                        $service['short_description']
                                    )
                                ): ?>

                                    <p>
                                        <?php
                                        echo nl2br(
                                            htmlspecialchars(
                                                $service[
                                                    'short_description'
                                                ]
                                            )
                                        );
                                        ?>
                                    </p>

                                <?php endif; ?>


                                <div class="public-service-actions">

                                    <a
                                        href="service-details.php?slug=<?php
                                        echo urlencode(
                                            $service['slug']
                                        );
                                        ?>"
                                        class="service-view-btn"
                                    >
                                        View Details
                                    </a>


                                    <a
                                        href="enquiry.php?service=<?php
                                        echo urlencode(
                                            $service['slug']
                                        );
                                        ?>"
                                        class="service-consult-btn"
                                    >
                                        Get Consultation
                                    </a>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="services-empty">

                    <h2>
                        Services Coming Soon
                    </h2>

                    <p>
                        We are currently updating our services.
                        Please check back soon.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </section>


    <!-- =========================================================
         FINAL CTA
    ========================================================== -->

    <section class="services-final-cta">

        <div class="container">

            <div class="services-final-cta-inner">

                <div>

                    <div class="section-eyebrow">
                        NEED ASSISTANCE?
                    </div>

                    <h2>
                        Not Sure Which Service You Need?
                    </h2>

                    <p>
                        Tell us about your requirement and our team
                        will help you find the right solution.
                    </p>

                </div>


                <div>

                    <a
                        href="enquiry.php"
                        class="services-cta-button"
                    >
                        Send Enquiry
                    </a>

                </div>

            </div>

        </div>

    </section>

</main>


<?php require_once "includes/footer.php"; ?>