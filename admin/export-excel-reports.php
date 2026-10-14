
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

$start_date = trim($_GET['start_date'] ?? '');
$end_date   = trim($_GET['end_date'] ?? '');

function isValidExcelDate($date)
{
    if ($date === '') {
        return true;
    }

    $parsed = DateTime::createFromFormat('!Y-m-d', $date);

    return $parsed && $parsed->format('Y-m-d') === $date;
}

if (
    !isValidExcelDate($start_date) ||
    !isValidExcelDate($end_date) ||
    ($start_date !== '' && $end_date !== '' &&
        $start_date > $end_date)
) {
    http_response_code(400);
    exit('Invalid date range.');
}

/* Fetch leads using the existing report query */
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
    $params[] = $start_date . ' 00:00:00';
}

if ($end_date !== '') {
    $sql .= " AND l.created_at < DATE_ADD(?, INTERVAL 1 DAY)";
    $params[] = $end_date;
}

$sql .= " ORDER BY l.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Escape cell content */
function excelSafeText($value)
{
    $value = (string)($value ?? '');

    $value = preg_replace(
        '/[\x00-\x08\x0B\x0C\x0E-\x1F]/',
        '',
        $value
    );

    // Prevent spreadsheet formulas in user-entered text.
    if (preg_match('/^[\s]*[=+\-@]/u', $value)) {
        $value = "'" . $value;
    }

    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_XML1,
        'UTF-8'
    );
}

$period = 'All Dates';

if ($start_date !== '' && $end_date !== '') {
    $period = $start_date . ' to ' . $end_date;
} elseif ($start_date !== '') {
    $period = 'From ' . $start_date;
} elseif ($end_date !== '') {
    $period = 'Up to ' . $end_date;
}

$headers = [
    'Lead ID',
    'Customer Name',
    'Mobile',
    'Email',
    'Service',
    'Lead Source',
    'Requirement',
    'Status',
    'Created Date'
];

/* Build formatted Excel rows */
$rows = '';

$rows .= '<Row ss:Height="30">
    <Cell ss:MergeAcross="8" ss:StyleID="Title">
        <Data ss:Type="String">YOURDREAMHOME - LEAD REPORT</Data>
    </Cell>
</Row>';

$rows .= '<Row>
    <Cell ss:StyleID="Info"><Data ss:Type="String">Report Period</Data></Cell>
    <Cell ss:MergeAcross="2" ss:StyleID="Info">
        <Data ss:Type="String">' . excelSafeText($period) . '</Data>
    </Cell>
</Row>';

$rows .= '<Row>
    <Cell ss:StyleID="Info"><Data ss:Type="String">Generated On</Data></Cell>
    <Cell ss:MergeAcross="2" ss:StyleID="Info">
        <Data ss:Type="String">' . date('Y-m-d H:i:s') . '</Data>
    </Cell>
</Row>';

$rows .= '<Row/>';

/* Header row */
$rows .= '<Row ss:Height="28">';

foreach ($headers as $header) {
    $rows .= '<Cell ss:StyleID="Header">
        <Data ss:Type="String">' . excelSafeText($header) . '</Data>
    </Cell>';
}

$rows .= '</Row>';

/* Data rows */
foreach ($leads as $lead) {
    $values = [
        $lead['lead_id'],
        $lead['customer_name'],
        $lead['mobile'],
        $lead['email'],
        $lead['service_name'],
        $lead['source_name'],
        $lead['requirement'],
        $lead['status'],
        $lead['created_at']
    ];

    $rows .= '<Row>';

    foreach ($values as $index => $value) {
        // Keep mobile numbers as text to preserve leading zeroes.
        $type = ($index === 0) ? 'Number' : 'String';

        if ($type === 'Number') {
            $cellValue = (string)(int)$value;
        } else {
            $cellValue = excelSafeText($value);
        }

        $rows .= '<Cell ss:StyleID="Data">
            <Data ss:Type="' . $type . '">' .
                $cellValue .
            '</Data>
        </Cell>';
    }

    $rows .= '</Row>';
}

/* Total row */
$rows .= '<Row>
    <Cell ss:StyleID="Total">
        <Data ss:Type="String">Total Leads</Data>
    </Cell>
    <Cell ss:StyleID="Total">
        <Data ss:Type="Number">' . count($leads) . '</Data>
    </Cell>
</Row>';

$xml = '<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook
 xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">

<Styles>
    <Style ss:ID="Default" ss:Name="Normal">
        <Alignment ss:Vertical="Top"/>
        <Font ss:FontName="Calibri" ss:Size="11"/>
    </Style>

    <Style ss:ID="Title">
        <Alignment ss:Vertical="Center"/>
        <Font ss:FontName="Calibri" ss:Size="16"
              ss:Bold="1" ss:Color="#FFFFFF"/>
        <Interior ss:Color="#14243D" ss:Pattern="Solid"/>
    </Style>

    <Style ss:ID="Info">
        <Alignment ss:Vertical="Center"/>
        <Font ss:FontName="Calibri" ss:Size="11"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous"
                    ss:Weight="1" ss:Color="#D9E2EF"/>
        </Borders>
    </Style>

    <Style ss:ID="Header">
        <Alignment ss:Horizontal="Center" ss:Vertical="Center"
                   ss:WrapText="1"/>
        <Font ss:FontName="Calibri" ss:Size="11"
              ss:Bold="1" ss:Color="#FFFFFF"/>
        <Interior ss:Color="#3478F6" ss:Pattern="Solid"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous"
                    ss:Weight="1" ss:Color="#D9E2EF"/>
            <Border ss:Position="Left" ss:LineStyle="Continuous"
                    ss:Weight="1" ss:Color="#D9E2EF"/>
            <Border ss:Position="Right" ss:LineStyle="Continuous"
                    ss:Weight="1" ss:Color="#D9E2EF"/>
        </Borders>
    </Style>

    <Style ss:ID="Data">
        <Alignment ss:Vertical="Top" ss:WrapText="1"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous"
                    ss:Weight="1" ss:Color="#D9E2EF"/>
            <Border ss:Position="Left" ss:LineStyle="Continuous"
                    ss:Weight="1" ss:Color="#D9E2EF"/>
            <Border ss:Position="Right" ss:LineStyle="Continuous"
                    ss:Weight="1" ss:Color="#D9E2EF"/>
        </Borders>
    </Style>

    <Style ss:ID="Total">
        <Font ss:Bold="1" ss:Color="#243B5A"/>
        <Borders>
            <Border ss:Position="Top" ss:LineStyle="Continuous"
                    ss:Weight="2" ss:Color="#3478F6"/>
        </Borders>
    </Style>
</Styles>

<Worksheet ss:Name="Lead Report">
    <Table>
        <Column ss:Width="65"/>
        <Column ss:Width="150"/>
        <Column ss:Width="110"/>
        <Column ss:Width="190"/>
        <Column ss:Width="145"/>
        <Column ss:Width="125"/>
        <Column ss:Width="240"/>
        <Column ss:Width="110"/>
        <Column ss:Width="145"/>
        ' . $rows . '
    </Table>

    <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
        <FreezePanes/>
        <FrozenNoSplit/>
        <SplitHorizontal>5</SplitHorizontal>
        <TopRowBottomPane>5</TopRowBottomPane>
        <ActivePane>2</ActivePane>
    </WorksheetOptions>
</Worksheet>
</Workbook>';

/* Download Excel-compatible XML spreadsheet */
$filename = 'lead_report_' . date('Y-m-d_H-i-s') . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";
echo $xml;
exit;
