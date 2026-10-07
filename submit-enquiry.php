<?php

require_once "config/database.php";


/* GET FORM DATA */

$service_id = $_POST['service_id'] ?? '';
$name = trim($_POST['name'] ?? '');
$mobile = trim($_POST['mobile'] ?? '');
$email = trim($_POST['email'] ?? '');
$requirement = trim($_POST['requirement'] ?? '');
$preferred_date = $_POST['preferred_date'] ?? '';
$preferred_time = $_POST['preferred_time'] ?? '';


/* BASIC VALIDATION */

if (
    $service_id == '' ||
    $name == '' ||
    $mobile == ''
) {

    die("Please fill all required fields.");

}


/* CHECK SERVICE */

$stmt = $pdo->prepare("
    SELECT id, service_name
    FROM services
    WHERE id = ?
    AND status = 1
    LIMIT 1
");

$stmt->execute([$service_id]);

$service = $stmt->fetch();

if (!$service) {

    die("Invalid service selected.");

}


/* INSERT LEAD */

/* GET WEBSITE SOURCE */

$stmt = $pdo->prepare("
    SELECT id
    FROM lead_sources
    WHERE source_name = 'Website'
    AND status = 1
    LIMIT 1
");

$stmt->execute();

$website_source = $stmt->fetch();

$source_id = $website_source
    ? $website_source['id']
    : null;


/* INSERT LEAD */

$stmt = $pdo->prepare("
    INSERT INTO leads
    (
        service_id,
        source_id,
        name,
        mobile,
        email,
        requirement,
        preferred_date,
        preferred_time,
        status
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        'New'
    )
");


$stmt->execute([
    $service_id,
    $source_id,
    $name,
    $mobile,
    $email != '' ? $email : null,
    $requirement != '' ? $requirement : null,
    $preferred_date != '' ? $preferred_date : null,
    $preferred_time != '' ? $preferred_time : null
]);


/* SUCCESS PAGE */

require_once "includes/header.php";

?>

<section class="enquiry-success">

    <div class="container">

        <div class="success-box">

            <div class="success-icon">
                ✓
            </div>

            <h1>
                Enquiry Submitted Successfully
            </h1>

            <p>
                Thank you, <?php echo htmlspecialchars($name); ?>.
            </p>

            <p>
                We have received your enquiry for
                <strong>
                    <?php echo htmlspecialchars($service['service_name']); ?>
                </strong>.
            </p>

            <p>
                Our team will contact you shortly.
            </p>

            <div class="success-actions">

                <a
                    href="index.php"
                    class="btn btn-primary"
                >
                    Back to Home
                </a>

                <a
                    href="services.php"
                    class="btn btn-secondary"
                >
                    View Services
                </a>

            </div>

        </div>

    </div>

</section>


<?php

require_once "includes/footer.php";

?>