
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

if (
    ($start_date !== '' &&
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) ||
    ($end_date !== '' &&
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date))
) {
    $date_error = 'Please enter valid start and end dates.';
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


/* Total leads with date filter */

$totalSql = "SELECT COUNT(*) FROM leads WHERE 1=1";
$totalParams = [];

if ($start_date !== '') {
    $totalSql .= " AND created_at >= ?";
    $totalParams[] = $start_date . " 00:00:00";
}

if ($end_date !== '') {
    $totalSql .= " AND created_at < DATE_ADD(?, INTERVAL 1 DAY)";
    $totalParams[] = $end_date;
}

$totalStmt = $pdo->prepare($totalSql);
$totalStmt->execute($totalParams);
$totalLeads = (int)$totalStmt->fetchColumn();


/* Leads by status with date filter */

$statusSql = "
    SELECT status, COUNT(*) AS total
    FROM leads
    WHERE 1=1
";
$statusParams = [];

if ($start_date !== '') {
    $statusSql .= " AND created_at >= ?";
    $statusParams[] = $start_date . " 00:00:00";
}

if ($end_date !== '') {
    $statusSql .= " AND created_at < DATE_ADD(?, INTERVAL 1 DAY)";
    $statusParams[] = $end_date;
}

$statusSql .= " GROUP BY status ORDER BY total DESC";

$statusStmt = $pdo->prepare($statusSql);
$statusStmt->execute($statusParams);
$statusReports = $statusStmt->fetchAll();


/* Leads by service with date filter */

$serviceSql = "
    SELECT
        s.service_name,
        COUNT(l.id) AS total
    FROM services s
    LEFT JOIN leads l
        ON l.service_id = s.id
";
$serviceParams = [];
$serviceConditions = [];

if ($start_date !== '') {
    $serviceConditions[] = "l.created_at >= ?";
    $serviceParams[] = $start_date . " 00:00:00";
}

if ($end_date !== '') {
    $serviceConditions[] =
        "l.created_at < DATE_ADD(?, INTERVAL 1 DAY)";
    $serviceParams[] = $end_date;
}

if (!empty($serviceConditions)) {
    $serviceSql .= " AND " .
        implode(" AND ", $serviceConditions);
}

$serviceSql .= "
    GROUP BY s.id, s.service_name
    ORDER BY total DESC, s.service_name ASC
";

$serviceStmt = $pdo->prepare($serviceSql);
$serviceStmt->execute($serviceParams);
$serviceReports = $serviceStmt->fetchAll();


/* Leads by source with date filter */

$sourceSql = "
    SELECT
        ls.source_name,
        COUNT(l.id) AS total
    FROM lead_sources ls
    LEFT JOIN leads l
        ON l.source_id = ls.id
";
$sourceParams = [];
$sourceConditions = [];

if ($start_date !== '') {
    $sourceConditions[] = "l.created_at >= ?";
    $sourceParams[] = $start_date . " 00:00:00";
}

if ($end_date !== '') {
    $sourceConditions[] =
        "l.created_at < DATE_ADD(?, INTERVAL 1 DAY)";
    $sourceParams[] = $end_date;
}

if (!empty($sourceConditions)) {
    $sourceSql .= " AND " .
        implode(" AND ", $sourceConditions);
}

$sourceSql .= "
    GROUP BY ls.id, ls.source_name
    ORDER BY total DESC, ls.source_name ASC
";

$sourceStmt = $pdo->prepare($sourceSql);
$sourceStmt->execute($sourceParams);
$sourceReports = $sourceStmt->fetchAll();
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
        .report-section {
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

        @media (max-width: 600px) {
            body {
                padding: 14px;
            }

            .summary-card,
            .report-section {
                padding: 15px;
            }
        }

        
.date-filter-form {
    display: flex;
    flex-wrap: wrap;
    align-items: end;
    gap: 15px;
    padding: 20px;
    margin-bottom: 25px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px #0000000b;
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

<a href="reports.php" class="reset-filter">
    Reset
</a>

<a
    href="export-reports.php?start_date=<?php echo urlencode($start_date); ?>&amp;end_date=<?php echo urlencode($end_date); ?>"
    class="reset-filter"
>
    Export CSV
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
                                <?php echo htmlspecialchars($row['service_name']); ?>
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
                                <?php echo htmlspecialchars($row['source_name']); ?>
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