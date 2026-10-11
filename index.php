<?php include __DIR__ . "/includes/header.php"; ?>


<!-- HERO SECTION -->


<!-- PREMIUM HERO SECTION -->

<section class="hero ydh-home-hero">

    <div class="hero-overlay"></div>

    <div class="container hero-content">

        <span class="hero-tag">
            YOUR DREAM HOME & FINANCIAL SERVICES
        </span>

        <h1>
            Your Goals.<br>
            Our Services.<br>
            <span>One Trusted Partner.</span>
        </h1>

        <p>
            From dream homes to smart investments,
            from construction to financial solutions,
            we are here to guide you at every step.
        </p>

        <div class="hero-buttons">

            <a href="#services" class="btn btn-primary">
                Explore Our Services →
            </a>

            <a href="#enquiry" class="btn btn-outline">
                Contact Us
            </a>

        </div>

    </div>

</section>


<!-- SERVICE CATEGORY SHORTCUTS -->

<section class="service-shortcuts">
    <div class="container shortcut-grid">

        <a href="services.php" class="shortcut-card">
            <span class="shortcut-icon">⌂</span>
            <h3>Real Estate</h3>
            <p>Buy | Sell | Rent</p>
        </a>

        <a href="services.php" class="shortcut-card">
            <span class="shortcut-icon">▦</span>
            <h3>Construction</h3>
            <p>Build Your Dreams</p>
        </a>

        <a href="services.php" class="shortcut-card">
            <span class="shortcut-icon">◈</span>
            <h3>Financial Advice</h3>
            <p>Plan Your Future</p>
        </a>

        <a href="services.php" class="shortcut-card">
            <span class="shortcut-icon">▣</span>
            <h3>Loan Management</h3>
            <p>Easy & Secure Process</p>
        </a>

        <a href="services.php" class="shortcut-card">
            <span class="shortcut-icon">✦</span>
            <h3>End-to-End Support</h3>
            <p>From Start to Finish</p>
        </a>

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


<section class="why-us-section">
    <div class="container">
        <div class="section-heading">
            <span class="hero-tag">WHY CHOOSE US</span>
            <h2>Your Trusted Partner at Every Step</h2>
            <p>Practical guidance and support for your property, construction and financial needs.</p>
        </div>

        <div class="why-us-grid">
            <div class="why-us-card">
                <div class="why-us-icon">⌂</div>
                <h3>Complete Solutions</h3>
                <p>Property, construction and financial services coordinated in one place.</p>
            </div>

            <div class="why-us-card">
                <div class="why-us-icon">✓</div>
                <h3>Personalised Guidance</h3>
                <p>Support based on your requirements, priorities and project needs.</p>
            </div>

            <div class="why-us-card">
                <div class="why-us-icon">↗</div>
                <h3>End-to-End Support</h3>
                <p>Guidance through enquiries, service details and the next steps in your journey.</p>
            </div>
        </div>
    </div>
</section>


<!-- ENQUIRY CTA -->


<section class="consultation-banner" id="enquiry">
    <div class="container">
        <div class="consultation-inner">
            <div>
                <h2>Let's Discuss Your Requirements</h2>
                <p>
                    Looking for property, construction or financial assistance?
                    Contact us to discuss your requirements and explore the
                    services available for your needs.
                </p>
            </div>

            <a href="/service-management/enquiry.php" class="btn">
                Get Consultation →
            </a>
        </div>
    </div>
</section>



<?php include "includes/footer.php"; ?>