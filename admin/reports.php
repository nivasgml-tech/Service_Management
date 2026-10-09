
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

// Leads by status
$statusStmt = $pdo->query("
    SELECT status, COUNT(*) AS total
    FROM leads
    GROUP BY status
    ORDER BY total DESC
");
$statusReports = $statusStmt->fetchAll();

// Leads by service
$serviceStmt = $pdo->query("
    SELECT
        s.service_name,
        COUNT(l.id) AS total
    FROM services s
    LEFT JOIN leads l ON l.service_id = s.id
    GROUP BY s.id, s.service_name
    ORDER BY total DESC, s.service_name ASC
");
$serviceReports = $serviceStmt->fetchAll();

// Leads by source
$sourceStmt = $pdo->query("
    SELECT
        ls.source_name,
        COUNT(l.id) AS total
    FROM lead_sources ls
    LEFT JOIN leads l ON l.source_id = ls.id
    GROUP BY ls.id, ls.source_name
    ORDER BY total DESC, ls.source_name ASC
");
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