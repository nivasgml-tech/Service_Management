
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
    <title>Customer Reports</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            background: #f4f6f9;
            color: #263238;
            font-family: Arial, sans-serif;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        h1 {
            margin: 0 0 8px;
        }

        .subtitle {
            margin: 0;
            color: #687782;
        }

        .button {
            display: inline-block;
            padding: 11px 15px;
            border: 1px solid #1769aa;
            border-radius: 5px;
            text-decoration: none;
            color: #1769aa;
            background: white;
        }

        .primary {
            color: white;
            background: #1769aa;
        }

        .filter-form,
        .summary-card,
        .report-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 22px;
            box-shadow: 0 2px 8px #0000000b;
        }

        .filter-form {
            display: flex;
            align-items: end;
            flex-wrap: wrap;
            gap: 14px;
        }

        .date-field {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .date-field label {
            font-size: 14px;
            font-weight: bold;
        }

        .date-field input {
            padding: 10px;
            border: 1px solid #ccd4da;
            border-radius: 5px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 22px;
        }

        .summary-card {
            margin-bottom: 0;
            border-left: 5px solid #1769aa;
        }

        .summary-card p {
            margin: 0 0 12px;
            color: #687782;
        }

        .summary-card strong {
            font-size: 28px;
        }

        .report-section h2 {
            margin-top: 0;
            font-size: 20px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 650px;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #e9edf0;
            text-align: left;
        }

        th {
            background: #f6f8fa;
        }

        .error {
            background: #fde8e7;
            color: #a12622;
            border: 1px solid #f5c2c0;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .empty {
            color: #687782;
            padding: 14px;
            background: #f7f9fb;
            border-radius: 6px;
        }

        @media (max-width: 850px) {
            .summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 520px) {
            body {
                padding: 14px;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .filter-form,
            .summary-card,
            .report-section {
                padding: 15px;
            }

            .date-field,
            .date-field input {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<div class="page-header">
    <div>
        <h1>Customer Reports</h1>
        <p class="subtitle">
            Customer totals and lead conversion overview.
        </p>
    </div>

    <a href="dashboard.php" class="button">Back to Dashboard</a>
</div>

<form method="GET" action="customer-reports.php" class="filter-form">
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

    <button type="submit" class="button primary">Apply Filter</button>

    <a href="customer-reports.php" class="button">Reset</a>

    <a
        class="button"
        href="customer-reports.php?start_date=<?php echo urlencode($start_date); ?>&amp;end_date=<?php echo urlencode($end_date); ?>&amp;export=csv"
    >Export Customers CSV</a>
</form>

<?php if ($date_error !== ''): ?>
    <div class="error">
        <?php echo htmlspecialchars($date_error); ?>
    </div>
<?php endif; ?>

<div class="summary-grid">
    <div class="summary-card">
        <p>Total Customers</p>
        <strong><?php echo $totalCustomers; ?></strong>
    </div>

    <div class="summary-card">
        <p>Total Leads</p>
        <strong><?php echo $totalLeads; ?></strong>
    </div>

    <div class="summary-card">
        <p>Converted Leads</p>
        <strong><?php echo $convertedLeads; ?></strong>
    </div>

    <div class="summary-card">
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
                    <td><?php echo $totalLeads; ?></td>
                </tr>
                <tr>
                    <td>Converted Leads</td>
                    <td><?php echo $convertedLeads; ?></td>
                </tr>
                <tr>
                    <td>Not Converted Leads</td>
                    <td><?php echo $notConvertedLeads; ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="report-section">
    <h2>Customer List</h2>

    <?php if (empty($customerRows)): ?>
        <p class="empty">No customers found for the selected dates.</p>
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
                            <td><?php echo (int)$customer['id']; ?></td>
                            <td><?php echo htmlspecialchars($customer['name']); ?></td>
                            <td><?php echo htmlspecialchars($customer['mobile']); ?></td>
                            <td><?php echo htmlspecialchars($customer['email'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($customer['city'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($customer['state'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($customer['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

</body>
</html>
