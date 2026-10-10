
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

/* Validate date format and actual calendar dates */
function isValidReportDate($date)
{
    if ($date === '') {
        return true;
    }

    $parsed = DateTime::createFromFormat('!Y-m-d', $date);

    return $parsed && $parsed->format('Y-m-d') === $date;
}

if (!isValidReportDate($start_date) ||
    !isValidReportDate($end_date)) {
    $date_error = 'Please enter valid dates.';
    $start_date = '';
    $end_date = '';
}

if ($start_date !== '' &&
    $end_date !== '' &&
    $start_date > $end_date) {
    $date_error = 'Start date cannot be after end date.';
    $start_date = '';
    $end_date = '';
}

/* Reusable date filter for lead queries */
function buildLeadDateFilter($start_date, $end_date, $column = 'created_at')
{
    $sql = '';
    $params = [];

    if ($start_date !== '') {
        $sql .= " AND $column >= ?";
        $params[] = $start_date . ' 00:00:00';
    }

    if ($end_date !== '') {
        $sql .= " AND $column < DATE_ADD(?, INTERVAL 1 DAY)";
        $params[] = $end_date;
    }

    return [$sql, $params];
}

/* Total leads */
list($dateFilter, $dateParams) =
    buildLeadDateFilter($start_date, $end_date);

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM leads WHERE 1=1 $dateFilter"
);
$stmt->execute($dateParams);
$totalLeads = (int)$stmt->fetchColumn();

/* Leads by status */
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) AS total
    FROM leads
    WHERE 1=1 $dateFilter
    GROUP BY status
    ORDER BY total DESC, status ASC
");
$stmt->execute($dateParams);
$statusReports = $stmt->fetchAll();

/* Leads by service */
list($serviceDateFilter, $serviceParams) =
    buildLeadDateFilter($start_date, $end_date, 'l.created_at');

$stmt = $pdo->prepare("
    SELECT s.service_name, COUNT(l.id) AS total
    FROM services s
    LEFT JOIN leads l
        ON l.service_id = s.id $serviceDateFilter
    GROUP BY s.id, s.service_name
    ORDER BY total DESC, s.service_name ASC
");
$stmt->execute($serviceParams);
$serviceReports = $stmt->fetchAll();

/* Leads by source */
$stmt = $pdo->prepare("
    SELECT ls.source_name, COUNT(l.id) AS total
    FROM lead_sources ls
    LEFT JOIN leads l
        ON l.source_id = ls.id $serviceDateFilter
    GROUP BY ls.id, ls.source_name
    ORDER BY total DESC, ls.source_name ASC
");
$stmt->execute($serviceParams);
$sourceReports = $stmt->fetchAll();

/* Chart helper: avoid division by zero */
function chartMaximum($rows)
{
    $maximum = 0;

    foreach ($rows as $row) {
        $maximum = max($maximum, (int)$row['total']);
    }

    return max(1, $maximum);
}

/* Render a responsive horizontal bar chart */
function renderChart($title, $rows, $labelKey, $emptyMessage)
{
    $maximum = chartMaximum($rows);
    ?>
    <section class="report-section chart-section">
        <h2><?php echo htmlspecialchars($title); ?></h2>

        <?php if (empty($rows)): ?>
            <p class="empty-message">
                <?php echo htmlspecialchars($emptyMessage); ?>
            </p>
        <?php else: ?>
            <div class="chart-list">
                <?php foreach ($rows as $row): ?>
                    <?php
                    $label = (string)$row[$labelKey];
                    $total = (int)$row['total'];
                    $width = ($total / $maximum) * 100;
                    ?>
                    <div class="chart-row">
                        <div class="chart-label-line">
                            <span class="chart-label">
                                <?php echo htmlspecialchars($label); ?>
                            </span>
                            <strong><?php echo $total; ?></strong>
                        </div>

                        <div class="bar-track">
                            <div
                                class="bar-fill"
                                style="width: <?php echo $width; ?>%;"
                                role="img"
                                aria-label="<?php
                                    echo htmlspecialchars(
                                        $label . ': ' . $total . ' leads'
                                    );
                                ?>"
                            ></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Lead Reports</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #263238;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        h1 {
            margin: 0 0 8px;
        }

        .subtitle {
            color: #687782;
            margin: 0;
        }

        .back-btn {
            display: inline-block;
            padding: 11px 16px;
            background: #1769aa;
            color: white;
            border-radius: 5px;
            text-decoration: none;
        }

        .summary-card,
        .report-section,
        .date-filter-form {
            background: white;
            padding: 22px;
            margin-bottom: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #0000000b;
        }

        .summary-card {
            border-left: 5px solid #1769aa;
        }

        .summary-card p {
            color: #687782;
            margin: 0 0 10px;
        }

        .summary-card strong {
            font-size: 32px;
        }

        .report-section h2 {
            margin-top: 0;
            font-size: 21px;
        }

        .date-filter-form {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: 15px;
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

        .date-filter-form button {
            padding: 11px 18px;
            background: #1769aa;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .reset-filter {
            display: inline-block;
            padding: 11px 14px;
            color: #1769aa;
            text-decoration: none;
            border: 1px solid #1769aa;
            border-radius: 5px;
        }

        .date-error {
            padding: 12px 16px;
            margin-bottom: 20px;
            background: #fde8e7;
            color: #a12622;
            border: 1px solid #f5c2c0;
            border-radius: 6px;
            font-weight: bold;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 350px;
        }

        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #e9edf0;
        }

        th {
            background: #f6f8fa;
        }

        .count {
            font-weight: bold;
            color: #1769aa;
        }

        .empty-message {
            padding: 14px;
            color: #687782;
            background: #f7f9fb;
            border-radius: 6px;
        }

        /* Chart styles */
        .chart-section {
            overflow: hidden;
        }

        .chart-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .chart-row {
            width: 100%;
            min-width: 0;
        }

        .chart-label-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 8px;
        }

        .chart-label {
            overflow-wrap: anywhere;
            font-size: 14px;
        }

        .chart-label-line strong {
            color: #1769aa;
            flex-shrink: 0;
        }

        .bar-track {
            width: 100%;
            height: 16px;
            background: #eaf0f5;
            border-radius: 10px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            min-width: 0;
            background: #1769aa;
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        .chart-section:nth-of-type(2) .bar-fill {
            background: #159578;
        }

        @media (max-width: 600px) {
            body {
                padding: 14px;
            }

            .summary-card,
            .report-section,
            .date-filter-form {
                padding: 15px;
            }

            .date-field {
                width: 100%;
            }

            .date-field input {
                width: 100%;
            }

            .date-filter-form button,
            .date-filter-form .reset-filter {
                text-align: center;
            }
        }
    </style>
</head>

<body>

<div class="page-header">
    <div>
        <h1>Reports</h1>
        <p class="subtitle">
            Overview of enquiries, services and lead sources.
        </p>
    </div>

    <a href="dashboard.php" class="back-btn">
        Back to Dashboard
    </a>
</div>

<form method="GET" action="reports.php" class="date-filter-form">
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

    <button type="submit">Apply Filter</button>

    <a href="reports.php" class="reset-filter">Reset</a>

    <a
        href="export-reports.php?start_date=<?php
            echo urlencode($start_date);
        ?>&amp;end_date=<?php echo urlencode($end_date); ?>"
        class="reset-filter"
    >Export CSV</a>

    
<a
    href="export-summary-reports.php?start_date=<?php echo urlencode($start_date); ?>&amp;end_date=<?php echo urlencode($end_date); ?>"
    class="reset-filter"
>
    Export Summary Reports
</a>

</form>

<?php if ($date_error !== ''): ?>
    <div class="date-error">
        <?php echo htmlspecialchars($date_error); ?>
    </div>
<?php endif; ?>

<div class="summary-card">
    <p>Total Leads</p>
    <strong><?php echo $totalLeads; ?></strong>
</div>

<!-- Charts -->
<?php
renderChart(
    'Leads by Status',
    $statusReports,
    'status',
    'No lead records found.'
);

renderChart(
    'Leads by Service',
    $serviceReports,
    'service_name',
    'No services found.'
);

renderChart(
    'Leads by Source',
    $sourceReports,
    'source_name',
    'No lead sources found.'
);
?>

<!-- Existing detailed report tables -->

<section class="report-section">
    <h2>1. Lead Status Report</h2>

    <?php if (empty($statusReports)): ?>
        <p class="empty-message">No lead records found.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Lead Status</th>
                        <th>Total Leads</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($statusReports as $row): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($row['status']); ?>
                            </td>
                            <td class="count">
                                <?php echo (int)$row['total']; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="report-section">
    <h2>2. Service-wise Lead Report</h2>

    <?php if (empty($serviceReports)): ?>
        <p class="empty-message">No services found.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Total Leads</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($serviceReports as $row): ?>
                        <tr>
                            <td>
                                <?php
                                echo htmlspecialchars($row['service_name']);
                                ?>
                            </td>
                            <td class="count">
                                <?php echo (int)$row['total']; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="report-section">
    <h2>3. Lead Source Report</h2>

    <?php if (empty($sourceReports)): ?>
        <p class="empty-message">No lead sources found.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Lead Source</th>
                        <th>Total Leads</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sourceReports as $row): ?>
                        <tr>
                            <td>
                                <?php
                                echo htmlspecialchars($row['source_name']);
                                ?>
                            </td>
                            <td class="count">
                                <?php echo (int)$row['total']; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

</body>
</html>