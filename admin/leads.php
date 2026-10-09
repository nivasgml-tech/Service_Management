<?php

session_start();

if (!isset($_SESSION['admin_id'])) {

    header("Location: login.php");

    exit;
}

require_once "../config/database.php";


/* FILTER VALUES */

$search = trim($_GET['search'] ?? '');

$status_filter = trim($_GET['status'] ?? '');

$service_filter = trim($_GET['service_id'] ?? '');

$source_filter = trim($_GET['source_id'] ?? '');
$conversion_filter = trim($_GET['conversion'] ?? '');


/* STATUS LIST */

$statuses = [

    'New',
    'Contacted',
    'Follow-up',
    'Qualified',
    'Quotation Sent',
    'Negotiation',
    'Won',
    'Lost',
    'Closed'

];


/* GET SERVICES */

$stmt = $pdo->query("
    SELECT id, service_name
    FROM services
    ORDER BY service_name ASC
");

$services = $stmt->fetchAll();


/* GET LEAD SOURCES */

$stmt = $pdo->query("
    SELECT id, source_name
    FROM lead_sources
    WHERE status = 1
    ORDER BY source_name ASC
");

$sources = $stmt->fetchAll();


/* BUILD LEAD QUERY */

$sql = "

    SELECT
    leads.*,
    services.service_name,
    lead_sources.source_name,
    users.name AS assigned_user,
    customers.name AS linked_customer_name,

        (
            SELECT MIN(lf.followup_date)

            FROM lead_followups lf

            WHERE lf.lead_id = leads.id

            AND lf.followup_date >= NOW()

        ) AS next_followup

    FROM leads

    LEFT JOIN services
        ON leads.service_id = services.id

    LEFT JOIN lead_sources
        ON leads.source_id = lead_sources.id

   LEFT JOIN users
    ON leads.assigned_to = users.id

LEFT JOIN customers
    ON leads.customer_id = customers.id

WHERE 1 = 1
";


$params = [];


/* SEARCH */

if ($search !== '') {

    $sql .= "

        AND (

            leads.name LIKE ?

            OR leads.mobile LIKE ?

            OR leads.email LIKE ?

        )

    ";

    $search_value = '%' . $search . '%';

    $params[] = $search_value;

    $params[] = $search_value;

    $params[] = $search_value;

}


/* STATUS FILTER */

if ($status_filter !== '') {

    $sql .= "
        AND leads.status = ?
    ";

    $params[] = $status_filter;

}


/* SERVICE FILTER */

if ($service_filter !== '' && is_numeric($service_filter)) {

    $sql .= "
        AND leads.service_id = ?
    ";

    $params[] = $service_filter;

}


/* SOURCE FILTER */

if ($source_filter !== '' && is_numeric($source_filter)) {

    $sql .= "
        AND leads.source_id = ?
    ";

    $params[] = $source_filter;

}

/* CONVERSION FILTER */

if ($conversion_filter === 'converted') {

    $sql .= " AND leads.customer_id IS NOT NULL
              AND leads.customer_id IN (
                  SELECT id FROM customers
              ) ";

} elseif ($conversion_filter === 'not_converted') {

    $sql .= " AND (
                  leads.customer_id IS NULL
                  OR leads.customer_id NOT IN (
                      SELECT id FROM customers
                  )
              ) ";
}



/* ORDER */

$sql .= "

    ORDER BY leads.created_at DESC

";


/* GET LEADS */

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$leads = $stmt->fetchAll();


/* TOTAL LEADS */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM leads
");

$total_leads = $stmt->fetchColumn();


/* NEW LEADS */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM leads
    WHERE status = 'New'
");

$new_leads = $stmt->fetchColumn();


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
        Lead Management | YourDream
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


        .topbar a {

            color: #ffffff;

            text-decoration: none;

        }


        .logout-btn {

            border: 1px solid #8293a3;

            padding: 10px 20px;

            border-radius: 6px;

        }


        .container {

            max-width: 1400px;

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

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;

        }


        .page-header h1 {

            margin: 0 0 8px 0;

        }


        .page-header p {

            margin: 0;

            color: #687681;

        }


        /* SUMMARY */

        .summary {

            display: flex;

            gap: 15px;

            margin-bottom: 25px;

            flex-wrap: wrap;

        }


        .summary-box {

            background: #ffffff;

            padding: 18px 25px;

            border-radius: 10px;

            box-shadow: 0 4px 18px rgba(0,0,0,0.05);

            min-width: 180px;

        }


        .summary-label {

            color: #687681;

            font-size: 14px;

            margin-bottom: 7px;

        }


        .summary-number {

            font-size: 28px;

            font-weight: 700;

            color: #10253d;

        }


        /* FILTERS */

        .filter-card {

            background: #ffffff;

            padding: 22px;

            border-radius: 12px;

            box-shadow: 0 4px 18px rgba(0,0,0,0.05);

            margin-bottom: 25px;

        }


        .filter-grid {

            display: grid;

            grid-template-columns:
                2fr
                1fr
                1fr
                1fr
                auto
                auto;

            gap: 12px;

            align-items: end;

        }


        .form-group label {

            display: block;

            margin-bottom: 7px;

            font-size: 13px;

            font-weight: 700;

            color: #52616d;

        }


        .form-group input,

        .form-group select {

            width: 100%;

            padding: 11px 12px;

            border: 1px solid #d9e0e6;

            border-radius: 6px;

            font-family: inherit;

            background: #ffffff;

        }


        .btn {

            display: inline-block;

            padding: 11px 18px;

            border-radius: 6px;

            border: 0;

            text-decoration: none;

            font-weight: 700;

            cursor: pointer;

            white-space: nowrap;

        }


        .btn-search {

            background: #10253d;

            color: #ffffff;

        }


        .btn-clear {

            background: #edf0f2;

            color: #263746;

        }


        /* TABLE */

        .table-card {

            background: #ffffff;

            border-radius: 12px;

            box-shadow: 0 4px 18px rgba(0,0,0,0.05);

            overflow-x: auto;

        }


        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 1150px;

        }


        th {

            background: #f0f3f5;

            padding: 14px;

            text-align: left;

            font-size: 13px;

            color: #52616d;

            white-space: nowrap;

        }


        td {

            padding: 14px;

            border-top: 1px solid #edf0f2;

            vertical-align: top;

            font-size: 14px;

        }


        tr:hover td {

            background: #fafbfc;

        }


        .lead-id {

            color: #10253d;

            font-weight: 700;

            text-decoration: none;

        }


        .lead-id:hover {

            text-decoration: underline;

        }


        .customer-name {

            font-weight: 700;

            margin-bottom: 4px;

        }


        .mobile {

            color: #687681;

        }


        .status {

            display: inline-block;

            padding: 5px 9px;

            border-radius: 5px;

            background: #eef2f5;

            font-size: 12px;

            font-weight: 700;

            white-space: nowrap;

        }


        .followup {

            font-weight: 700;

            color: #10253d;

            white-space: nowrap;

        }


        .no-followup {

            color: #9aa5ad;

        }


        .empty {

            text-align: center;

            padding: 45px;

            color: #7a8791;

        }


        @media (max-width: 1000px) {

            .filter-grid {

                grid-template-columns: 1fr 1fr;

            }

        }


        @media (max-width: 600px) {

            .filter-grid {

                grid-template-columns: 1fr;

            }


            .page-header {

                display: block;

            }

        }
.filter-grid {
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto auto;
}
    </style>

</head>


<body>


<!-- TOP BAR -->

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
        href="dashboard.php"
        class="back-link"
    >
        ← Dashboard
    </a>


    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>

            <h1>
                Lead Management
            </h1>

            <p>
                Manage enquiries, status, assignments and follow-ups.
            </p>

        </div>

    </div>


    <!-- SUMMARY -->

    <div class="summary">


        <div class="summary-box">

            <div class="summary-label">
                Total Leads
            </div>

            <div class="summary-number">

                <?php
                echo $total_leads;
                ?>

            </div>

        </div>


        <div class="summary-box">

            <div class="summary-label">
                New Leads
            </div>

            <div class="summary-number">

                <?php
                echo $new_leads;
                ?>

            </div>

        </div>


        <div class="summary-box">

            <div class="summary-label">
                Showing
            </div>

            <div class="summary-number">

                <?php
                echo count($leads);
                ?>

            </div>

        </div>


    </div>


    <!-- FILTERS -->

    <div class="filter-card">

        <form
            method="GET"
            action="leads.php"
        >

            <div class="filter-grid">


                <!-- SEARCH -->

                <div class="form-group">

                    <label>
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Name, mobile or email"
                    >

                </div>


                <!-- STATUS -->

                <div class="form-group">

                    <label>
                        Status
                    </label>

                    <select name="status">

                        <option value="">
                            All Status
                        </option>

                        <?php foreach ($statuses as $status): ?>

                            <option
                                value="<?php echo htmlspecialchars($status); ?>"
                                <?php
                                if ($status_filter === $status) {
                                    echo 'selected';
                                }
                                ?>
                            >

                                <?php
                                echo htmlspecialchars($status);
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- SERVICE -->

                <div class="form-group">

                    <label>
                        Service
                    </label>

                    <select name="service_id">

                        <option value="">
                            All Services
                        </option>

                        <?php foreach ($services as $service): ?>

                            <option
                                value="<?php echo $service['id']; ?>"
                                <?php
                                if (
                                    $service_filter ==
                                    $service['id']
                                ) {
                                    echo 'selected';
                                }
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $service['service_name']
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- SOURCE -->

                <div class="form-group">

                    <label>
                        Source
                    </label>

                    <select name="source_id">

                        <option value="">
                            All Sources
                        </option>

                        <?php foreach ($sources as $source): ?>

                            <option
                                value="<?php echo $source['id']; ?>"
                                <?php
                                if (
                                    $source_filter ==
                                    $source['id']
                                ) {
                                    echo 'selected';
                                }
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $source['source_name']
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

<!-- CONVERSION STATUS -->

<div class="form-group">

    <label>Conversion Status</label>

    <select name="conversion">

        <option value=""
            <?php echo $conversion_filter === '' ? 'selected' : ''; ?>>
            All Leads
        </option>

        <option value="converted"
            <?php echo $conversion_filter === 'converted' ? 'selected' : ''; ?>>
            Converted
        </option>

        <option value="not_converted"
            <?php echo $conversion_filter === 'not_converted' ? 'selected' : ''; ?>>
            Not Converted
        </option>

    </select>

</div>

                <!-- SEARCH BUTTON -->

                <div>

                    <button
                        type="submit"
                        class="btn btn-search"
                    >
                        Search
                    </button>

                </div>


                <!-- CLEAR -->

                <div>

                    <a
                        href="leads.php"
                        class="btn btn-clear"
                    >
                        Clear
                    </a>

                </div>


            </div>

        </form>

    </div>


    <!-- LEAD TABLE -->

    <div class="table-card">


        <?php if (count($leads) > 0): ?>


            <table>

                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Customer
                        </th>
<th>
    Customer Record
</th>
                        <th>
                            Service
                        </th>

                        <th>
                            Source
                        </th>

                        <th>
                            Requirement
                        </th>

                        <th>
                            Preferred
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Assigned To
                        </th>

                        <th>
                            Next Follow-up
                        </th>

                        <th>
                            Created
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php foreach ($leads as $lead): ?>


                        <tr>


                            <!-- ID -->

                            <td>

                                <a
                                    href="lead-details.php?id=<?php echo $lead['id']; ?>"
                                    class="lead-id"
                                >

                                    #<?php
                                    echo $lead['id'];
                                    ?>

                                </a>

                            </td>


                            <!-- CUSTOMER -->

                            <td>

                                <div class="customer-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $lead['name']
                                    );
                                    ?>

                                </div>

                                <div class="mobile">

                                    <?php
                                    echo htmlspecialchars(
                                        $lead['mobile']
                                    );
                                    ?>

                                </div>

                                <?php if (!empty($lead['email'])): ?>

                                    <div class="mobile">

                                        <?php
                                        echo htmlspecialchars(
                                            $lead['email']
                                        );
                                        ?>

                                    </div>

                                <?php endif; ?>

                            </td>

                            <!-- LINKED CUSTOMER RECORD -->
<td>

    <?php if (!empty($lead['customer_id']) && !empty($lead['linked_customer_name'])): ?>

        <a
            href="customer-details.php?id=<?php echo (int)$lead['customer_id']; ?>"
            style="color:#16804a; font-weight:700; text-decoration:none;"
        >
            View Customer
        </a>

        <div class="mobile">
            <?php echo htmlspecialchars($lead['linked_customer_name']); ?>
        </div>

    <?php else: ?>

        <span style="color:#9aa5ad;">
            Not Converted
        </span>

    <?php endif; ?>

</td>



                            <!-- SERVICE -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $lead['service_name']
                                    ?? '-'
                                );
                                ?>

                            </td>


                            <!-- SOURCE -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $lead['source_name']
                                    ?? 'Unknown'
                                );
                                ?>

                            </td>


                            <!-- REQUIREMENT -->

                            <td>

                                <?php

                                $requirement =
                                    trim(
                                        $lead['requirement']
                                        ?? ''
                                    );

                                if ($requirement === '') {

                                    echo '-';

                                } else {

                                    if (
                                        strlen($requirement) > 70
                                    ) {

                                        echo htmlspecialchars(
                                            substr(
                                                $requirement,
                                                0,
                                                70
                                            )
                                            . '...'
                                        );

                                    } else {

                                        echo htmlspecialchars(
                                            $requirement
                                        );

                                    }

                                }

                                ?>

                            </td>


                            <!-- PREFERRED -->

                            <td>

                                <?php

                                if (
                                    !empty(
                                        $lead['preferred_date']
                                    )
                                ) {

                                    echo date(
                                        'd-m-Y',
                                        strtotime(
                                            $lead['preferred_date']
                                        )
                                    );

                                } else {

                                    echo '-';

                                }


                                if (
                                    !empty(
                                        $lead['preferred_time']
                                    )
                                ) {

                                    echo '<br>';

                                    echo htmlspecialchars(
                                        $lead['preferred_time']
                                    );

                                }

                                ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span class="status">

                                    <?php
                                    echo htmlspecialchars(
                                        $lead['status']
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- ASSIGNED -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $lead['assigned_user']
                                    ?? 'Not Assigned'
                                );
                                ?>

                            </td>


                            <!-- NEXT FOLLOW-UP -->

                            <td>

                                <?php

                                if (
                                    !empty(
                                        $lead['next_followup']
                                    )
                                ) {

                                    echo '<span class="followup">';

                                    echo date(
                                        'd-m-Y h:i A',
                                        strtotime(
                                            $lead['next_followup']
                                        )
                                    );

                                    echo '</span>';

                                } else {

                                    echo '<span class="no-followup">';

                                    echo 'No upcoming';

                                    echo '</span>';

                                }

                                ?>

                            </td>


                            <!-- CREATED -->

                            <td>

                                <?php

                                echo date(
                                    'd-m-Y h:i A',
                                    strtotime(
                                        $lead['created_at']
                                    )
                                );

                                ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                </tbody>

            </table>


        <?php else: ?>


            <div class="empty">

                No leads found for the selected filters.

            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>