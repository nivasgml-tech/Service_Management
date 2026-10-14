<?php

session_start();

if (!isset($_SESSION['admin_id'])) {

    header("Location: login.php");

    exit;
}

require_once "../config/database.php";


/* LIVE CUSTOMER SUGGESTIONS */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'customer_suggestions') {
    header('Content-Type: application/json; charset=UTF-8');

    $term = trim($_GET['q'] ?? '');

    if ($term === '' || mb_strlen($term) < 1) {
        echo json_encode([]);
        exit;
    }

    $suggestionSql = "
        SELECT id, name, mobile, email, city, state
        FROM customers
        WHERE name LIKE ?
           OR mobile LIKE ?
           OR email LIKE ?
           OR city LIKE ?
           OR state LIKE ?
        ORDER BY
            CASE WHEN name LIKE ? THEN 0 ELSE 1 END,
            name ASC
        LIMIT 10
    ";

    $like = '%' . $term . '%';
    $nameStartsWith = $term . '%';

    $suggestionStmt = $pdo->prepare($suggestionSql);
    $suggestionStmt->execute([
        $like,
        $like,
        $like,
        $like,
        $like,
        $nameStartsWith
    ]);

    echo json_encode(
        $suggestionStmt->fetchAll(PDO::FETCH_ASSOC),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}



/* SEARCH */

$search = trim($_GET['search'] ?? '');


/* GET CUSTOMERS */

$sql = "
    SELECT *
    FROM customers
";

$params = [];

if ($search !== '') {

    $sql .= "
        WHERE
            name LIKE ?
            OR mobile LIKE ?
            OR email LIKE ?
            OR city LIKE ?
            OR state LIKE ?
    ";

    $search_value = '%' . $search . '%';

    $params = [
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $search_value
    ];
}

$sql .= "
    ORDER BY created_at DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$customers = $stmt->fetchAll();


/* TOTAL CUSTOMERS */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM customers
");

$total_customers = $stmt->fetchColumn();

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
        Customer Management | YourDream
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


        /* TOPBAR */

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


        /* CONTAINER */

        .container {

            max-width: 1400px;

            margin: auto;

            padding: 35px 20px;

        }


        /* PAGE HEADER */

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


        /* BUTTONS */

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


        .btn-primary {

            background: #10253d;

            color: #ffffff;

        }


        .btn-primary:hover {

            background: #071a2d;

        }


        .btn-search {

            background: #10253d;

            color: #ffffff;

        }


        .btn-clear {

            background: #edf0f2;

            color: #263746;

        }


        /* FILTER */

        .filter-card {

            background: #ffffff;

            padding: 22px;

            border-radius: 12px;

            box-shadow: 0 4px 18px rgba(0,0,0,0.05);

            margin-bottom: 25px;

        }


        .filter-grid {

            display: grid;

            grid-template-columns: 2fr auto auto;

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


        .form-group input {

            width: 100%;

            padding: 11px 12px;

            border: 1px solid #d9e0e6;

            border-radius: 6px;

            font-family: inherit;

            background: #ffffff;

        }


        .form-group input:focus {

            outline: none;

            border-color: #10253d;

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

            min-width: 1000px;

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


        .customer-id {

            color: #10253d;

            font-weight: 700;

        }


        .customer-name {

            font-weight: 700;

            margin-bottom: 4px;

        }


        .mobile {

            color: #687681;

        }


        .empty {

            text-align: center;

            padding: 45px;

            color: #7a8791;

        }


        /* ACTIONS */

        .action-view,

        .action-edit {

            display: inline-block;

            padding: 7px 11px;

            border-radius: 5px;

            text-decoration: none;

            font-size: 12px;

            font-weight: 700;

            margin-right: 5px;

        }


        .action-view {

            background: #eef2f5;

            color: #10253d;

        }


        .action-edit {

            background: #10253d;

            color: #ffffff;

        }


        /* MOBILE */

        @media (max-width: 800px) {

            .filter-grid {

                grid-template-columns: 1fr;

            }


            .page-header {

                display: block;

            }


            .page-header .btn {

                margin-top: 15px;

            }

        }


        @media (max-width: 600px) {

            .topbar {

                padding: 15px 18px;

            }


            .logo {

                font-size: 20px;

            }


            .container {

                padding: 25px 15px;

            }

        }

        
/* Search layout and Excel export */
.filter-grid {
    grid-template-columns: minmax(220px, 1fr) auto auto;
}

.customer-search-group {
    position: relative;
    min-width: 0;
}

.filter-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-excel {
    background: #16804a;
    color: #fff;
}

.btn-excel:hover {
    background: #11683b;
}

.customer-suggestions {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    z-index: 1000;
    background: #fff;
    border: 1px solid #d9e0e6;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, .12);
    max-height: 320px;
    overflow-y: auto;
}

.customer-suggestion {
    display: block;
    width: 100%;
    padding: 11px 14px;
    text-align: left;
    background: #fff;
    border: 0;
    border-bottom: 1px solid #edf0f2;
    cursor: pointer;
    font: inherit;
    color: #263746;
}

.customer-suggestion:last-child {
    border-bottom: 0;
}

.customer-suggestion:hover,
.customer-suggestion:focus {
    background: #f1f5fb;
    outline: none;
}

.customer-suggestion strong {
    display: block;
    margin-bottom: 3px;
    color: #10253d;
}

.customer-suggestion small {
    display: block;
    color: #687681;
    overflow-wrap: anywhere;
}

.customer-suggestion-empty {
    padding: 13px;
    color: #687681;
    font-size: 13px;
}

@media (max-width: 800px) {
    .filter-grid {
        grid-template-columns: 1fr;
    }

    .filter-actions {
        align-items: stretch;
    }
}


    </style>

</head>


<body>


<!-- TOPBAR -->

<div class="topbar">

    <div class="logo">

        Your<span>Dream</span> Admin

    </div>

<a href="dashboard.php" class="dashboard-link">
    ← Back to Dashboard
</a>


    <a
        href="logout.php"
        class="logout-btn"
    >
        Logout
    </a>

</div>


<!-- MAIN -->

<div class="container">


    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>

            <h1>Customer Management</h1>

            <p>
                Manage website customers and customer information.
            </p>

        </div>


        <div>

            <a
                href="customer-add.php"
                class="btn btn-primary"
            >
                + Add Customer
            </a>

        </div>

    </div>


    <!-- SUMMARY -->

    <div class="summary">

        <div class="summary-box">

            <div class="summary-label">
                Total Customers
            </div>

            <div class="summary-number">
                <?php echo (int)$total_customers; ?>
            </div>

        </div>

    </div>


    <!-- SEARCH -->

    <div class="filter-card">

        <form method="GET">

            <div class="filter-grid">


                <div class="form-group customer-search-group">
    <label for="customerSearch">Search Customer</label>

    <input
        type="text"
        id="customerSearch"
        name="search"
        value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
        placeholder="Name, mobile, email, city or state"
        autocomplete="off"
    >

    <div
        id="customerSuggestions"
        class="customer-suggestions"
        role="listbox"
    ></div>
</div>
                <div>

                    <button
                        type="submit"
                        class="btn btn-search"
                    >
                        Search
                    </button>

                </div>


               <div class="filter-actions">
    <a href="customers.php" class="btn btn-clear">
        Clear
    </a>

    <a
        href="export-customer-excel.php?search=<?php echo urlencode($search); ?>"
        class="btn btn-excel"
    >
        Export Excel
    </a>
</div>

            </div>

        </form>

    </div>


    <!-- CUSTOMER TABLE -->

    <div class="table-card">

        <table>

            <thead>

                <tr>

                    <th>#</th>

                    <th>Customer</th>

                    <th>Mobile</th>

                    <th>Email</th>

                    <th>City</th>

                    <th>State</th>

                    <th>Created</th>

                    <th>Actions</th>

                </tr>

            </thead>


            <tbody>

            <?php if (!empty($customers)): ?>

                <?php foreach ($customers as $customer): ?>

                    <tr>


                        <td>

                            <span class="customer-id">

                                #<?php
                                echo (int)$customer['id'];
                                ?>

                            </span>

                        </td>


                        <td>

                            <div class="customer-name">

                                <?php
                                echo htmlspecialchars(
                                    $customer['name']
                                );
                                ?>

                            </div>

                        </td>


                        <td>

                            <span class="mobile">

                                <?php
                                echo htmlspecialchars(
                                    $customer['mobile']
                                );
                                ?>

                            </span>

                        </td>


                        <td>

                            <?php

                            echo !empty($customer['email'])

                                ? htmlspecialchars(
                                    $customer['email']
                                )

                                : '-';

                            ?>

                        </td>


                        <td>

                            <?php

                            echo !empty($customer['city'])

                                ? htmlspecialchars(
                                    $customer['city']
                                )

                                : '-';

                            ?>

                        </td>


                        <td>

                            <?php

                            echo !empty($customer['state'])

                                ? htmlspecialchars(
                                    $customer['state']
                                )

                                : '-';

                            ?>

                        </td>


                        <td>

                            <?php

                            echo date(
                                'd-m-Y',
                                strtotime(
                                    $customer['created_at']
                                )
                            );

                            ?>

                        </td>


                        <td>

                            <a
                                href="customer-details.php?id=<?php echo (int)$customer['id']; ?>"
                                class="action-view"
                            >
                                View
                            </a>


                            <a
                                href="customer-edit.php?id=<?php echo (int)$customer['id']; ?>"
                                class="action-edit"
                            >
                                Edit
                            </a>

                        </td>


                    </tr>

                <?php endforeach; ?>


            <?php else: ?>

                <tr>

                    <td
                        colspan="8"
                        class="empty"
                    >

                        No customers found.

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


</div>


<script>

document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('customerSearch');
    const suggestions = document.getElementById('customerSuggestions');

    if (!input || !suggestions) return;

    let timer = null;
    let requestController = null;
    let activeIndex = -1;
    let currentItems = [];

    function hideSuggestions() {
        suggestions.style.display = 'none';
        suggestions.innerHTML = '';
        currentItems = [];
        activeIndex = -1;
    }

    function selectCustomer(item) {
        input.value = item.name || item.mobile || '';
        hideSuggestions();

        const url = new URL(window.location.href);
        url.searchParams.set('search', input.value);
        window.location.href = url.toString();
    }

    function renderSuggestions(items) {
        suggestions.innerHTML = '';
        currentItems = items;
        activeIndex = -1;

        if (!items.length) {
            suggestions.innerHTML =
                '<div class="customer-suggestion-empty">No matching customers found.</div>';
            suggestions.style.display = 'block';
            return;
        }

        items.forEach(function (item, index) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'customer-suggestion';
            button.setAttribute('role', 'option');

            const name = document.createElement('strong');
            name.textContent = item.name || 'Unnamed customer';

            const details = document.createElement('small');
            details.textContent = [
                item.mobile,
                item.email,
                item.city,
                item.state
            ].filter(Boolean).join(' · ');

            button.appendChild(name);
            button.appendChild(details);

            button.addEventListener('mousedown', function (event) {
                event.preventDefault();
                selectCustomer(item);
            });

            suggestions.appendChild(button);
        });

        suggestions.style.display = 'block';
    }

    async function fetchSuggestions() {
        const term = input.value.trim();

        if (!term) {
            hideSuggestions();
            return;
        }

        if (requestController) {
            requestController.abort();
        }

        requestController = new AbortController();

        try {
            const url = new URL(window.location.href);
            url.search = '';
            url.searchParams.set('ajax', 'customer_suggestions');
            url.searchParams.set('q', term);

            const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json' },
                signal: requestController.signal
            });

            if (!response.ok) {
                throw new Error('Suggestion request failed');
            }

            const items = await response.json();

            if (input.value.trim() === term) {
                renderSuggestions(items);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                hideSuggestions();
            }
        }
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(fetchSuggestions, 250);
    });

    input.addEventListener('keydown', function (event) {
        const buttons = suggestions.querySelectorAll('.customer-suggestion');

        if (suggestions.style.display !== 'block' || !buttons.length) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            activeIndex = (activeIndex + 1) % buttons.length;
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            activeIndex = (activeIndex - 1 + buttons.length) % buttons.length;
        } else if (event.key === 'Enter' && activeIndex >= 0) {
            event.preventDefault();
            selectCustomer(currentItems[activeIndex]);
            return;
        } else if (event.key === 'Escape') {
            hideSuggestions();
            return;
        } else {
            return;
        }

        buttons.forEach(function (button, index) {
            button.style.background =
                index === activeIndex ? '#f1f5fb' : '#fff';
        });
    });

    input.addEventListener('blur', function () {
        setTimeout(hideSuggestions, 150);
    });

    input.addEventListener('focus', function () {
        if (input.value.trim()) {
            fetchSuggestions();
        }
    });
});

</script>


</body>

</html>