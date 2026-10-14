
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

$start_date = trim($_GET['start_date'] ?? '');
$end_date = trim($_GET['end_date'] ?? '');
$date_error = '';

function validCustomerReportDate($date)
{
    if ($date === '') {
        return true;
    }

    $parsed = DateTime::createFromFormat('!Y-m-d', $date);

    return $parsed && $parsed->format('Y-m-d') === $date;
}

if (
    !validCustomerReportDate($start_date) ||
    !validCustomerReportDate($end_date)
) {
    $date_error = 'Please enter valid dates.';
    $start_date = '';
    $end_date = '';
}

if (
    $start_date !== '' &&
    $end_date !== '' &&
    $start_date > $end_date
) {
    $date_error = 'Start date cannot be after end date.';
    $start_date = '';
    $end_date = '';
}

/* Customer date filter */
$customerWhere = [];
$customerParams = [];

if ($start_date !== '') {
    $customerWhere[] = "created_at >= ?";
    $customerParams[] = $start_date . " 00:00:00";
}

if ($end_date !== '') {
    $customerWhere[] = "created_at < DATE_ADD(?, INTERVAL 1 DAY)";
    $customerParams[] = $end_date;
}

$customerCondition = $customerWhere
    ? " WHERE " . implode(" AND ", $customerWhere)
    : "";

/* Total customers */
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM customers $customerCondition"
);
$stmt->execute($customerParams);
$totalCustomers = (int)$stmt->fetchColumn();

/* Customer list for report */
$stmt = $pdo->prepare("
    SELECT id, name, mobile, email, city, state, created_at
    FROM customers
    $customerCondition
    ORDER BY created_at DESC
");
$stmt->execute($customerParams);
$customerRows = $stmt->fetchAll();


/* Lead conversion report uses leads.created_at */
$leadWhere = [];
$leadParams = [];

if ($start_date !== '') {
    $leadWhere[] = "l.created_at >= ?";
    $leadParams[] = $start_date . " 00:00:00";
}

if ($end_date !== '') {
    $leadWhere[] = "l.created_at < DATE_ADD(?, INTERVAL 1 DAY)";
    $leadParams[] = $end_date;
}

$leadCondition = $leadWhere
    ? " AND " . implode(" AND ", $leadWhere)
    : "";

/*
 * A lead counts as converted only when customer_id
 * points to an existing customer record.
 */
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_leads,
        SUM(
            CASE
                WHEN c.id IS NOT NULL THEN 1
                ELSE 0
            END
        ) AS converted_leads,
        SUM(
            CASE
                WHEN c.id IS NULL THEN 1
                ELSE 0
            END
        ) AS not_converted_leads
    FROM leads l
    LEFT JOIN customers c ON c.id = l.customer_id
    WHERE 1=1 $leadCondition
");
$stmt->execute($leadParams);
$conversionReport = $stmt->fetch();

$totalLeads = (int)($conversionReport['total_leads'] ?? 0);
$convertedLeads = (int)($conversionReport['converted_leads'] ?? 0);
$notConvertedLeads = (int)($conversionReport['not_converted_leads'] ?? 0);

/* Customer export */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'customers_report_' . date('Y-m-d_H-i-s') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        'Customer ID',
        'Name',
        'Mobile',
        'Email',
        'City',
        'State',
        'Created At'
    ]);

    foreach ($customerRows as $customer) {
        fputcsv($output, [
            $customer['id'],
            $customer['name'],
            $customer['mobile'],
            $customer['email'],
            $customer['city'],
            $customer['state'],
            $customer['created_at']
        ]);
    }

    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customer Reports | YourDreamHome</title>

    <link rel="stylesheet" href="assets/css/admin-style.css?v=2">

    <style>
        /* Customer Reports - match the main Reports page */

        .customer-reports-container {
            max-width: 1500px;
            margin: 0 auto;
            padding: 28px;
        }

        .customer-reports-container .reports-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 18px;
            margin-bottom: 26px;
        }

        .customer-reports-container .reports-header h1 {
            margin: 0 0 8px;
            color: #101d35;
            font-size: 30px;
            font-weight: 800;
        }

        .customer-reports-container .subtitle {
            margin: 0;
            color: #68758a;
        }

        .customer-reports-container .back-btn,
        .customer-reports-container .report-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 11px 17px;
            border: 1px solid #d9e2ef;
            border-radius: 9px;
            background: #ffffff;
            color: #101d35;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .customer-reports-container .back-btn {
            background: #101d35;
            border-color: #101d35;
            color: #ffffff;
        }

        .customer-reports-container .back-btn:hover {
            background: #1b2c49;
        }

        .customer-reports-container .report-action:hover {
            background: #f2f5fa;
        }

        .customer-reports-container .filter-card {
            margin-bottom: 25px;
            padding: 22px;
            border: 1px solid #e3e8ef;
            border-radius: 15px;
            background: #ffffff;
            box-shadow: 0 8px 28px rgba(16, 29, 53, 0.06);
        }

        .customer-reports-container .date-filter-form {
            display: flex;
            align-items: end;
            flex-wrap: wrap;
            gap: 14px;
            margin: 0;
        }

        .customer-reports-container .date-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 190px;
        }

        .customer-reports-container .date-field label {
            color: #263247;
            font-size: 14px;
            font-weight: 700;
        }

        .customer-reports-container .date-field input {
            width: 100%;
            min-height: 44px;
            padding: 10px 12px;
            border: 1px solid #d5deea;
            border-radius: 9px;
            background: #ffffff;
            color: #263247;
            font: inherit;
            box-sizing: border-box;
        }

        .customer-reports-container .date-field input:focus {
            outline: none;
            border-color: #3478f6;
            box-shadow: 0 0 0 3px rgba(52, 120, 246, 0.12);
        }

        .customer-reports-container .apply-btn {
            min-height: 44px;
            padding: 11px 19px;
            border: 1px solid #101d35;
            border-radius: 9px;
            background: #101d35;
            color: #ffffff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .customer-reports-container .apply-btn:hover {
            background: #1b2c49;
        }

        .customer-reports-container .reset-btn {
            background: #f0f2f5;
            border-color: #f0f2f5;
        }

        .customer-reports-container .csv-btn {
            border-color: #16845b;
            background: #16845b;
            color: #ffffff;
        }

        .customer-reports-container .csv-btn:hover {
            background: #116b49;
        }

        .customer-reports-container .customer-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 26px;
        }

        .customer-reports-container .customer-summary-card {
            min-width: 0;
            padding: 24px;
            border: 1px solid #e3e8ef;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 8px 28px rgba(16, 29, 53, 0.06);
        }

        .customer-reports-container .customer-summary-card p {
            margin: 0 0 13px;
            color: #68758a;
            font-size: 15px;
            font-weight: 600;
        }

        .customer-reports-container .customer-summary-card strong {
            display: block;
            color: #101d35;
            font-size: 36px;
            font-weight: 800;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .customer-reports-container .report-section {
            margin-bottom: 25px;
            padding: 24px;
            border: 1px solid #e3e8ef;
            border-radius: 15px;
            background: #ffffff;
            box-shadow: 0 8px 28px rgba(16, 29, 53, 0.06);
        }

        .customer-reports-container .report-section h2 {
            margin: 0 0 20px;
            color: #101d35;
            font-size: 20px;
            font-weight: 800;
        }

        .customer-reports-container .table-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #e3e8ef;
            border-radius: 10px;
        }

        .customer-reports-container table {
            width: 100%;
            min-width: 650px;
            border-collapse: collapse;
            background: #ffffff;
        }

        .customer-reports-container th,
        .customer-reports-container td {
            padding: 14px 16px;
            border-bottom: 1px solid #e9edf3;
            text-align: left;
            vertical-align: middle;
            font-size: 14px;
        }

        .customer-reports-container th {
            background: #f2f5fa;
            color: #263247;
            font-weight: 800;
            white-space: nowrap;
        }

        .customer-reports-container tbody tr:last-child td {
            border-bottom: 0;
        }

        .customer-reports-container tbody tr:hover {
            background: #f8faff;
        }

        .customer-reports-container .count {
            color: #101d35;
            font-weight: 800;
        }

        .customer-reports-container .error {
            margin-bottom: 22px;
            padding: 14px 17px;
            border: 1px solid #f5c2c0;
            border-radius: 10px;
            background: #fde8e7;
            color: #a12622;
            font-weight: 700;
        }

        .customer-reports-container .empty {
            margin: 0;
            padding: 18px;
            border-radius: 9px;
            background: #f5f7fb;
            color: #68758a;
        }

        @media (max-width: 1000px) {
            .customer-reports-container .customer-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .customer-reports-container {
                padding: 15px;
            }

            .customer-reports-container .reports-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .customer-reports-container .reports-header h1 {
                font-size: 25px;
            }

            .customer-reports-container .customer-summary-grid {
                grid-template-columns: 1fr;
            }

            .customer-reports-container .customer-summary-card,
            .customer-reports-container .report-section,
            .customer-reports-container .filter-card {
                padding: 17px;
            }

            .customer-reports-container .date-field {
                width: 100%;
                min-width: 0;
            }

            .customer-reports-container .apply-btn,
            .customer-reports-container .report-action {
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>

<body class="admin-page">

<div class="customer-reports-container">

    <div class="reports-header">
        <div>
            <h1>Customer Reports</h1>
            <p class="subtitle">
                Customer totals and lead conversion overview.
            </p>
        </div>

        <a href="dashboard.php" class="back-btn">
            ← Back to Dashboard
        </a>
    </div>

    <div class="filter-card">
        <form method="GET"
              action="customer-reports.php"
              class="date-filter-form">

            <div class="date-field">
                <label for="start_date">Start Date</label>
                <input
                    type="date"
                    id="start_date"
                    name="start_date"
                    value="<?php echo htmlspecialchars($start_date); ?>"
                >
            </div>

            <div class="date-field">
                <label for="end_date">End Date</label>
                <input
                    type="date"
                    id="end_date"
                    name="end_date"
                    value="<?php echo htmlspecialchars($end_date); ?>"
                >
            </div>

            <button type="submit" class="apply-btn">
                Apply Filter
            </button>

            <a href="customer-reports.php"
               class="report-action reset-btn">
                Reset
            </a>

            <a
                class="report-action csv-btn"
                href="customer-reports.php?<?php
                    echo htmlspecialchars(
                        http_build_query([
                            'start_date' => $start_date,
                            'end_date' => $end_date,
                            'export' => 'csv'
                        ]),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                ?>"
            >
                Export Customers CSV
            </a>
        </form>
    </div>

    <?php if ($date_error !== ''): ?>
        <div class="error">
            <?php echo htmlspecialchars($date_error); ?>
        </div>
    <?php endif; ?>

    <div class="customer-summary-grid">

        <div class="customer-summary-card">
            <p>Total Customers</p>
            <strong><?php echo $totalCustomers; ?></strong>
        </div>

        <div class="customer-summary-card">
            <p>Total Leads</p>
            <strong><?php echo $totalLeads; ?></strong>
        </div>

        <div class="customer-summary-card">
            <p>Converted Leads</p>
            <strong><?php echo $convertedLeads; ?></strong>
        </div>

        <div class="customer-summary-card">
            <p>Not Converted Leads</p>
            <strong><?php echo $notConvertedLeads; ?></strong>
        </div>

    </div>

    <section class="report-section">
        <h2>Lead Conversion Summary</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Total</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>Total Leads</td>
                        <td class="count"><?php echo $totalLeads; ?></td>
                    </tr>

                    <tr>
                        <td>Converted Leads</td>
                        <td class="count"><?php echo $convertedLeads; ?></td>
                    </tr>

                    <tr>
                        <td>Not Converted Leads</td>
                        <td class="count"><?php echo $notConvertedLeads; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="report-section">
        <h2>Customer List</h2>

        <?php if (empty($customerRows)): ?>

            <p class="empty">
                No customers found for the selected dates.
            </p>

        <?php else: ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <th>City</th>
                            <th>State</th>
                            <th>Created At</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($customerRows as $customer): ?>
                            <tr>
                                <td>
                                    <?php echo (int)$customer['id']; ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars($customer['name'] ?? '');
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars($customer['mobile'] ?? '');
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars($customer['email'] ?? '');
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars($customer['city'] ?? '');
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars($customer['state'] ?? '');
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars($customer['created_at'] ?? '');
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </section>

</div>

</body>
</html>
