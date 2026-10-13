
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
list($sourceDateFilter, $sourceParams) =
    buildLeadDateFilter(
        $start_date,
        $end_date,
        'l.created_at'
    );

$stmt = $pdo->prepare("
    SELECT
        ls.source_name,
        COUNT(l.id) AS total
    FROM lead_sources ls
    LEFT JOIN leads l
        ON l.source_id = ls.id
        $sourceDateFilter
    GROUP BY ls.id, ls.source_name
    ORDER BY total DESC, ls.source_name ASC
");

$stmt->execute($sourceParams);
$sourceReports = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* SMART REPORT CHARTS */

function reportColors()
{
    return [
        '#3478f6',
        '#36bd8b',
        '#ff850d',
        '#9b5cf6',
        '#20aaa4',
        '#ef4444',
        '#8998ad',
        '#eab308',
        '#ec4899'
    ];
}

function renderChart($title, $rows, $labelKey, $emptyMessage)
{
    $colors = reportColors();
    $total = 0;

    foreach ($rows as $row) {
        $total += (int)$row['total'];
    }

    $segments = [];
    $legend = [];
    $offset = 0;
    $index = 0;

    foreach ($rows as $row) {
        $count = (int)$row['total'];
        $percentage = $total > 0
            ? ($count / $total) * 100
            : 0;

        $color = $colors[$index % count($colors)];

        $segments[] = $color . ' ' .
            round($offset, 2) . '% ' .
            round($offset + $percentage, 2) . '%';

        $offset += $percentage;

        $legend[] = [
            'label' => (string)$row[$labelKey],
            'total' => $count,
            'percentage' => $percentage,
            'color' => $color
        ];

        $index++;
    }

    $gradient = $total > 0
        ? 'conic-gradient(' . implode(', ', $segments) . ')'
        : 'conic-gradient(#e8edf5 0% 100%)';

    $icons = [
        'Leads by Status' => 'chart-column',
        'Leads by Service' => 'wrench',
        'Leads by Source' => 'megaphone'
    ];

    $descriptions = [
        'Leads by Status' =>
            'Distribution of leads by current status',
        'Leads by Service' =>
            'Distribution of leads by service type',
        'Leads by Source' =>
            'Distribution of leads by source channel'
    ];
    ?>
    

    <section class="smart-report-card">

        <div class="smart-card-heading">

            <div class="smart-icon <?php
                echo htmlspecialchars(
                    strtolower(str_replace(' ', '-', $title))
                );
            ?>">
                <?php
                echo htmlspecialchars($icons[$title] ?? 'chart-column');
                ?>
            </div>

            <div class="smart-heading-text">
                <h2><?php echo htmlspecialchars($title); ?></h2>

                <p>
                    <?php
                    echo htmlspecialchars(
                        $descriptions[$title] ?? ''
                    );
                    ?>
                </p>
            </div>

        </div>

        <?php if ($total === 0): ?>

            <div class="smart-empty">
                <?php echo htmlspecialchars($emptyMessage); ?>
            </div>

        <?php else: ?>

            <div class="smart-chart-layout">

                <div
                    class="smart-donut"
                    style="--donut-gradient: <?php
                        echo htmlspecialchars($gradient);
                    ?>;"
                    role="img"
                    aria-label="<?php
                        echo htmlspecialchars($title . ': ' . $total . ' leads');
                    ?>"
                >
                    <div class="smart-donut-center">
                        <span>Total</span>
                        <strong><?php echo $total; ?></strong>
                    </div>
                </div>

                <div class="smart-legend">

                    <?php foreach ($legend as $item): ?>

                        <div class="smart-legend-row">

                            <span
                                class="smart-dot"
                                style="--item-color: <?php
                                    echo htmlspecialchars($item['color']);
                                ?>;"
                            ></span>

                            <span class="smart-legend-label">
                                <?php
                                echo htmlspecialchars($item['label']);
                                ?>
                            </span>

                            <div class="smart-progress">
                                <div
                                    class="smart-progress-fill"
                                    style="
                                        width: <?php
                                            echo round(
                                                $item['percentage'],
                                                2
                                            );
                                        ?>%;
                                        --item-color: <?php
                                            echo htmlspecialchars(
                                                $item['color']
                                            );
                                        ?>;
                                    "
                                ></div>
                            </div>

                            <strong class="smart-count">
                                <?php echo $item['total']; ?>
                            </strong>

                            <span class="smart-percent">
                                <?php
                                echo round($item['percentage']);
                                ?>%
                            </span>

                        </div>

                    <?php endforeach; ?>

                </div>

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
<link
    rel="stylesheet"
    href="assets/css/admin-style.css?v=2"
>
   
</head>

<body class="admin-page">
    
<div class="reports-container">

    <div class="reports-header">
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
<div class="smart-reports-grid">

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

</div>
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
</div>
</body>
</html>