<?php

require_once "config/database.php";

$service_slug = $_GET['service'] ?? '';

$service = null;


/* GET SELECTED SERVICE */

if ($service_slug != '') {

    $stmt = $pdo->prepare("
        SELECT *
        FROM services
        WHERE slug = ?
        AND status = 1
        LIMIT 1
    ");

    $stmt->execute([$service_slug]);

    $service = $stmt->fetch();

}


/* PAGE */

require_once "includes/header.php";

?>

<section class="enquiry-page">

    <div class="container">

        <div class="enquiry-page-heading">

            <span>
                GET IN TOUCH
            </span>

            <h1>
                Tell Us About Your Requirement
            </h1>

            <p>
                Share your requirement with us.
                Our team will contact you shortly.
            </p>

        </div>


        <div class="enquiry-form-wrapper">

            <form
                method="POST"
                action="submit-enquiry.php"
                class="enquiry-form"
            >


                <!-- SERVICE -->

                <div class="form-group">

                    <label for="service_id">
                        Service
                    </label>

                    <select
                        name="service_id"
                        id="service_id"
                        required
                    >

                        <option value="">
                            Select Service
                        </option>


                        <?php

                        $stmt = $pdo->query("
                            SELECT id, service_name
                            FROM services
                            WHERE status = 1
                            ORDER BY sort_order ASC, id ASC
                        ");

                        $services = $stmt->fetchAll();

                        foreach ($services as $item):

                        ?>

                            <option
                                value="<?php echo $item['id']; ?>"
                                <?php
                                if (
                                    $service &&
                                    $service['id'] == $item['id']
                                ) {
                                    echo 'selected';
                                }
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $item['service_name']
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- NAME -->

                <div class="form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        id="name"
                        placeholder="Enter your full name"
                        required
                    >

                </div>


                <!-- MOBILE -->

                <div class="form-group">

                    <label for="mobile">
                        Mobile Number
                    </label>

                    <input
                        type="tel"
                        name="mobile"
                        id="mobile"
                        placeholder="Enter your mobile number"
                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        name="email"
                        id="email"
                        placeholder="Enter your email address"
                    >

                </div>


                <!-- REQUIREMENT -->

                <div class="form-group full-width">

                    <label for="requirement">
                        Your Requirement
                    </label>

                    <textarea
                        name="requirement"
                        id="requirement"
                        rows="6"
                        placeholder="Please describe your requirement..."
                    ></textarea>

                </div>


                <!-- DATE -->

                <div class="form-group">

                    <label for="preferred_date">
                        Preferred Date
                    </label>

                    <input
                        type="date"
                        name="preferred_date"
                        id="preferred_date"
                    >

                </div>


                <!-- TIME -->

                <div class="form-group">

                    <label for="preferred_time">
                        Preferred Time
                    </label>

                    <select
                        name="preferred_time"
                        id="preferred_time"
                    >

                        <option value="">
                            Select Time
                        </option>

                        <option value="Morning">
                            Morning
                        </option>

                        <option value="Afternoon">
                            Afternoon
                        </option>

                        <option value="Evening">
                            Evening
                        </option>

                    </select>

                </div>


                <!-- SUBMIT -->

                <div class="form-submit full-width">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Submit Enquiry
                    </button>

                </div>

            </form>

        </div>

    </div>

</section>


<?php

require_once "includes/footer.php";

?>