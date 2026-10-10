<?php include __DIR__ . "/includes/header.php"; ?>


<!-- HERO SECTION -->

<section class="hero">

    <div class="hero-overlay"></div>

    <div class="container hero-content">

        <span class="hero-tag">
            YOUR TRUSTED BUSINESS PARTNER
        </span>

        <h1>
            Property. Construction.
            <span>Finance.</span>
            One Trusted Partner.
        </h1>

        <p>
            From finding the right property to construction,
            financial guidance and loan processing,
            we bring multiple solutions together under one roof.
        </p>

        <div class="hero-buttons">

            <a href="#services" class="btn btn-primary">
                Explore Services
            </a>

            <a href="#enquiry" class="btn btn-outline">
                Talk to an Expert
            </a>

        </div>

    </div>

</section>


<!-- TRUST / STATS -->

<section class="trust-section">

    <div class="container stats-grid">

        <div class="stat-item">

            <strong>10+</strong>

            <span>Years Experience</span>

        </div>

        <div class="stat-item">

            <strong>500+</strong>

            <span>Clients Served</span>

        </div>

        <div class="stat-item">

            <strong>6+</strong>

            <span>Core Services</span>

        </div>

        <div class="stat-item">

            <strong>100%</strong>

            <span>Dedicated Support</span>

        </div>

    </div>

</section>


<!-- SERVICES -->

<!-- SERVICES -->

<section class="services-section" id="services">

    <div class="container">

        <div class="section-heading">

            <span>WHAT WE DO</span>

            <h2>
                Our Services
            </h2>

            <p>
                Comprehensive property, construction and financial
                solutions designed around your requirements.
            </p>

        </div>


        <div class="service-grid">

            <?php

            $stmt = $pdo->query("
                SELECT *
                FROM services
                WHERE status = 1
                ORDER BY sort_order ASC, id ASC
            ");

            $services = $stmt->fetchAll();

            foreach ($services as $service):

            ?>

                <article class="service-card">

                    <div class="service-image">

                        <div class="service-image-placeholder">

                            <span>
                                <?php echo htmlspecialchars($service['service_name']); ?>
                            </span>

                        </div>

                    </div>


                    <div class="service-content">

                        <span class="service-number">

                            <?php
                            echo str_pad(
                                $service['sort_order'],
                                2,
                                '0',
                                STR_PAD_LEFT
                            );
                            ?>

                        </span>


                        <h3>

                            <?php
                            echo htmlspecialchars(
                                $service['service_name']
                            );
                            ?>

                        </h3>


                        <p>

                            <?php
                            echo htmlspecialchars(
                                $service['short_description']
                            );
                            ?>

                        </p>


                        <a
                            href="service-details.php?slug=<?php echo urlencode($service['slug']); ?>"
                            class="service-link"
                        >
                            View Service →
                        </a>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    </div>

</section>


<!-- WHY US -->

<section class="why-section">

    <div class="container why-grid">

        <div>

            <span class="section-label">
                WHY CHOOSE US
            </span>

            <h2>
                One Partner.
                Multiple Solutions.
            </h2>

            <p>
                Whether you are planning to buy a property,
                construct a building, arrange finance or
                process a loan, our team helps you move
                from requirement to execution.
            </p>

            <a href="#enquiry" class="btn btn-primary">
                Talk to Our Team
            </a>

        </div>


        <div class="why-points">

            <div class="why-point">

                <strong>01</strong>

                <div>
                    <h3>End-to-End Support</h3>
                    <p>
                        From initial consultation to completion.
                    </p>
                </div>

            </div>


            <div class="why-point">

                <strong>02</strong>

                <div>
                    <h3>Multiple Services</h3>
                    <p>
                        Property, construction and finance in one place.
                    </p>
                </div>

            </div>


            <div class="why-point">

                <strong>03</strong>

                <div>
                    <h3>Personalised Guidance</h3>
                    <p>
                        Solutions based on your individual requirements.
                    </p>
                </div>

            </div>

        </div>

    </div>

</section>


<!-- ENQUIRY CTA -->

<section class="enquiry-cta" id="enquiry">

    <div class="container">

        <span>
            HAVE A REQUIREMENT?
        </span>

        <h2>
            Let's discuss your requirement.
        </h2>

        <p>
            Tell us what you are looking for and
            our team will get back to you.
        </p>

        <a href="#" class="btn btn-light">
            Send an Enquiry
        </a>

    </div>

</section>


<?php include "includes/footer.php"; ?>