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
<link rel="stylesheet" href="assets/css/admin-style.css">

    </head>


<body class="admin-page">


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

<div class="live-search-wrapper">

    <input
        type="text"
        id="leadLiveSearch"
        name="search"
        value="<?php echo htmlspecialchars($search); ?>"
        placeholder="Name, mobile or email"
        autocomplete="off"
        aria-autocomplete="list"
        aria-controls="leadSearchSuggestions"
        aria-expanded="false"
    >

    <div
        id="leadSearchSuggestions"
        class="lead-search-suggestions"
        role="listbox"
    ></div>

</div>
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

             <!-- CLEAR -->

<div class="filter-action">

    <a
        href="leads.php"
        class="btn btn-clear"
    >
        Clear
    </a>

</div>

<!-- EXPORT EXCEL -->

<div class="filter-action">

    <a
        href="export-excel-leads.php?<?php
            echo htmlspecialchars(
                http_build_query([
                    'search' => $search,
                    'status' => $status_filter,
                    'service_id' => $service_filter,
                    'source_id' => $source_filter,
                    'conversion' => $conversion_filter
                ]),
                ENT_QUOTES,
                'UTF-8'
            );
        ?>"
        class="btn btn-excel"
    >
        Export Excel
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


<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('leadLiveSearch');
    const dropdown = document.getElementById('leadSearchSuggestions');

    if (!input || !dropdown) return;

    let debounceTimer;
    let activeController = null;

    function closeSuggestions() {
        dropdown.classList.remove('show');
        dropdown.replaceChildren();
        input.setAttribute('aria-expanded', 'false');
    }

    function showMessage(message) {
        dropdown.replaceChildren();

        const item = document.createElement('div');
        item.className = 'lead-suggestion-message';
        item.textContent = message;

        dropdown.appendChild(item);
        dropdown.classList.add('show');
        input.setAttribute('aria-expanded', 'true');
    }

    function addText(parent, className, text) {
        const element = document.createElement('span');
        element.className = className;
        element.textContent = text || '';
        parent.appendChild(element);
    }

    function displaySuggestions(results) {
        dropdown.replaceChildren();

        if (!results.length) {
            showMessage('No matching leads found.');
            return;
        }

        results.forEach(function (lead) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'lead-suggestion-item';
            button.setAttribute('role', 'option');

            addText(
                button,
                'lead-suggestion-name',
                lead.name || 'Unnamed lead'
            );

            const details = [
                lead.mobile || '',
                lead.email || ''
            ].filter(Boolean).join(' · ');

            addText(
                button,
                'lead-suggestion-details',
                details
            );

            button.addEventListener('click', function () {
                input.value = lead.name || lead.mobile || lead.email || '';
                closeSuggestions();
                input.focus();
            });

            dropdown.appendChild(button);
        });

        dropdown.classList.add('show');
        input.setAttribute('aria-expanded', 'true');
    }

    input.addEventListener('input', function () {
        clearTimeout(debounceTimer);

        if (activeController) {
            activeController.abort();
            activeController = null;
        }

        const term = input.value.trim();

        if (term.length < 2) {
            closeSuggestions();
            return;
        }

        debounceTimer = setTimeout(async function () {
            activeController = new AbortController();

            try {
                const response = await fetch(
                    'lead-search-suggestions.php?q=' +
                    encodeURIComponent(term),
                    {
                        headers: {
                            'Accept': 'application/json'
                        },
                        signal: activeController.signal
                    }
                );

                if (!response.ok) {
                    showMessage('Unable to load suggestions.');
                    return;
                }

                const results = await response.json();

                // Ignore results if the user has typed something newer.
                if (input.value.trim() !== term) return;

                displaySuggestions(results);

            } catch (error) {
                if (error.name !== 'AbortError') {
                    showMessage('Unable to load suggestions.');
                }
            }
        }, 250);
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.live-search-wrapper')) {
            closeSuggestions();
        }
    });

    input.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSuggestions();
        }
    });
});
</script>

</html>