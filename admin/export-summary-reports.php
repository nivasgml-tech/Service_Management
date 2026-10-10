
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

/* Validate date filters */
$start_date = trim($_GET['start_date'] ?? '');
$end_date = trim($_GET['end_date'] ?? '');

function isValidSummaryDate($date)
{
    if ($date === '') {
        return true;
    }

    $parsed = DateTime::createFromFormat('!Y-m-d', $date);

    return $parsed && $parsed->format('Y-m-d') === $date;
}

if (
    !isValidSummaryDate($start_date) ||
    !isValidSummaryDate($end_date) ||
    (
        $start_date !== '' &&
        $end_date !== '' &&
        $start_date > $end_date
    )
) {
    http_response_code(400);
    exit('Invalid date range. Please return to Reports and select valid dates.');
}

/* Build date filter for lead records */
function getSummaryDateFilter($start, $end, $column)
{
    $sql = '';
    $params = [];

    if ($start !== '') {
        $sql .= " AND $column >= ?";
        $params[] = $start . ' 00:00:00';
    }

    if ($end !== '') {
        $sql .= " AND $column < DATE_ADD(?, INTERVAL 1 DAY)";
        $params[] = $end;
    }

    return [$sql, $params];
}

/* 1. Lead status summary */
list($dateFilter, $dateParams) = getSummaryDateFilter(
    $start_date,
    $end_date,
    'created_at'
);

$stmt = $pdo->prepare("
    SELECT status, COUNT(*) AS total
    FROM leads
    WHERE 1=1 $dateFilter
    GROUP BY status
    ORDER BY total DESC, status ASC
");
$stmt->execute($dateParams);
$statusReports = $stmt->fetchAll();

/* 2. Service-wise summary */
list($serviceFilter, $serviceParams) = getSummaryDateFilter(
    $start_date,
    $end_date,
    'l.created_at'
);

$stmt = $pdo->prepare("
    SELECT s.service_name, COUNT(l.id) AS total
    FROM services s
    LEFT JOIN leads l
        ON l.service_id = s.id $serviceFilter
    GROUP BY s.id, s.service_name
    ORDER BY total DESC, s.service_name ASC
");
$stmt->execute($serviceParams);
$serviceReports = $stmt->fetchAll();

/* 3. Lead source summary */
$stmt = $pdo->prepare("
    SELECT ls.source_name, COUNT(l.id) AS total
    FROM lead_sources ls
    LEFT JOIN leads l
        ON l.source_id = ls.id $serviceFilter
    GROUP BY ls.id, ls.source_name
    ORDER BY total DESC, ls.source_name ASC
");
$stmt->execute($serviceParams);
$sourceReports = $stmt->fetchAll();

/* Prepare CSV download */
$filename = 'summary_reports_' . date('Y-m-d_H-i-s') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');

$output = fopen('php://output', 'w');

/* UTF-8 BOM for Excel */
fwrite($output, "\xEF\xBB\xBF");

/* Report filter details */
fputcsv($output, ['SUMMARY REPORTS']);
fputcsv($output, [
    'Start Date',
    $start_date !== '' ? $start_date : 'All dates'
]);
fputcsv($output, [
    'End Date',
    $end_date !== '' ? $end_date : 'All dates'
]);
fputcsv($output, []);

/* Status summary */
fputcsv($output, ['LEAD STATUS SUMMARY']);
fputcsv($output, ['Lead Status', 'Total Leads']);

foreach ($statusReports as $row) {
    fputcsv($output, [
        $row['status'],
        (int)$row['total']
    ]);
}

fputcsv($output, []);

/* Service summary */
fputcsv($output, ['SERVICE-WISE LEAD SUMMARY']);
fputcsv($output, ['Service', 'Total Leads']);

foreach ($serviceReports as $row) {
    fputcsv($output, [
        $row['service_name'],
        (int)$row['total']
    ]);
}

fputcsv($output, []);

/* Source summary */
fputcsv($output, ['LEAD SOURCE SUMMARY']);
fputcsv($output, ['Lead Source', 'Total Leads']);

foreach ($sourceReports as $row) {
    fputcsv($output, [
        $row['source_name'],
        (int)$row['total']
    ]);
}

fclose($output);
exit;
?>
