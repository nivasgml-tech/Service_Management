<?php

require_once "config/database.php";

$slug = $_GET['slug'] ?? '';

if ($slug == '') {
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


require_once "includes/header.php";

?>

<!-- SERVICE HERO -->

<section class="service-detail-hero">

    <div class="container">

        <span class="service-detail-label">
            OUR SERVICE
        </span>

        <h1>
            <?php echo htmlspecialchars($service['service_name']); ?>
        </h1>

        <p>
            <?php echo htmlspecialchars($service['short_description']); ?>
        </p>

    </div>

</section>


<!-- SERVICE CONTENT -->

<section class="service-detail-section">

    <div class="container">

        <div class="service-detail-grid">


            <!-- IMAGE -->

            <div class="service-detail-image">

                <?php if (!empty($service['main_image'])): ?>

                    <img
                        src="<?php echo htmlspecialchars($service['main_image']); ?>"
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


                <div class="service-description">

                    <?php
                    echo nl2br(
                        htmlspecialchars(
                            $service['description']
                        )
                    );
                    ?>

                </div>


                <?php if (!empty($service['video_url'])): ?>

                    <a
                        href="<?php echo htmlspecialchars($service['video_url']); ?>"
                        target="_blank"
                        class="btn btn-primary"
                    >
                        Watch Video
                    </a>

                <?php endif; ?>

            </div>

        </div>


        <!-- FEATURES -->

        <?php if (count($features) > 0): ?>

            <div class="service-features">

                <div class="section-heading">

                    <span>WHY CHOOSE THIS SERVICE</span>

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


        <!-- FAQ -->

        <?php if (count($faqs) > 0): ?>

            <div class="service-faq">

                <div class="section-heading">

                    <span>FAQ</span>

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


        <!-- ENQUIRY -->

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