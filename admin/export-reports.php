
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

// Read date filters
$start_date = trim($_GET['start_date'] ?? '');
$end_date = trim($_GET['end_date'] ?? '');

// Validate date format and real calendar dates
function isValidDate($date)
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }

    $parsed = DateTime::createFromFormat('!Y-m-d', $date);

    return $parsed && $parsed->format('Y-m-d') === $date;
}

if (
    ($start_date !== '' && !isValidDate($start_date)) ||
    ($end_date !== '' && !isValidDate($end_date)) ||
    ($start_date !== '' && $end_date !== '' &&
        $start_date > $end_date)
) {
    http_response_code(400);
    exit("Invalid date range. Please return to Reports and select valid dates.");
}

// Build query
$sql = "
    SELECT
        l.id AS lead_id,
        l.name AS customer_name,
        l.mobile,
        l.email,
        s.service_name,
        ls.source_name,
        l.requirement,
        l.status,
        l.created_at
    FROM leads l
    LEFT JOIN services s ON l.service_id = s.id
    LEFT JOIN lead_sources ls ON l.source_id = ls.id
    WHERE 1=1
";

$params = [];

if ($start_date !== '') {
    $sql .= " AND l.created_at >= ?";
    $params[] = $start_date . " 00:00:00";
}

if ($end_date !== '') {
    $sql .= " AND l.created_at < DATE_ADD(?, INTERVAL 1 DAY)";
    $params[] = $end_date;
}

$sql .= " ORDER BY l.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Prepare CSV download
$filename = "lead_report_" . date('Y-m-d_H-i-s') . ".csv";

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM helps Excel display text correctly
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, [
    'Lead ID',
    'Customer Name',
    'Mobile',
    'Email',
    'Service',
    'Lead Source',
    'Requirement',
    'Status',
    'Created Date'
]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['lead_id'],
        $row['customer_name'],
        $row['mobile'],
        $row['email'],
        $row['service_name'],
        $row['source_name'],
        $row['requirement'],
        $row['status'],
        $row['created_at']
    ]);
}

fclose($output);
exit;