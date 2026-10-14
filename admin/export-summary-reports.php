
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

/* Validate date filters */
$start_date = trim($_GET['start_date'] ?? '');
$end_date   = trim($_GET['end_date'] ?? '');

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
    ($start_date !== '' && $end_date !== '' &&
        $start_date > $end_date)
) {
    http_response_code(400);
    exit('Invalid date range. Please return to Reports and select valid dates.');
}

/* Build reusable date filter */
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
$statusReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
$serviceReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
$sourceReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* XML-safe text */
function summaryExcelText($value)
{
    $value = (string)($value ?? '');

    $value = preg_replace(
        '/[\x00-\x08\x0B\x0C\x0E-\x1F]/',
        '',
        $value
    );

    if (preg_match('/^[\s]*[=+\-@]/u', $value)) {
        $value = "'" . $value;
    }

    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_XML1,
        'UTF-8'
    );
}

/* Reusable styled cells */
function summaryCell($value, $style = 'Data', $type = 'String')
{
    if ($type === 'Number') {
        $content = (string)(int)$value;
    } else {
        $content = summaryExcelText($value);
    }

    return '<Cell ss:StyleID="' . $style . '">
        <Data ss:Type="' . $type . '">' .
            $content .
        '</Data>
    </Cell>';
}

/* Report period */
$period = 'All Dates';

if ($start_date !== '' && $end_date !== '') {
    $period = $start_date . ' to ' . $end_date;
} elseif ($start_date !== '') {
    $period = 'From ' . $start_date;
} elseif ($end_date !== '') {
    $period = 'Up to ' . $end_date;
}

/* Build worksheet */
$rows = '';

/* Main title */
$rows .= '<Row ss:Height="32">'
    . '<Cell ss:MergeAcross="2" ss:StyleID="Title">'
    . '<Data ss:Type="String">YOURDREAMHOME - SUMMARY REPORTS</Data>'
    . '</Cell></Row>';

/* Report information */
$rows .= '<Row>'
    . summaryCell('Report Period', 'InfoLabel')
    . '<Cell ss:MergeAcross="1" ss:StyleID="Info">'
    . '<Data ss:Type="String">' . summaryExcelText($period) . '</Data>'
    . '</Cell></Row>';

$rows .= '<Row>'
    . summaryCell('Generated On', 'InfoLabel')
    . '<Cell ss:MergeAcross="1" ss:StyleID="Info">'
    . '<Data ss:Type="String">' . date('Y-m-d H:i:s') . '</Data>'
    . '</Cell></Row>';

$rows .= '<Row/>';

/* Section 1: Status */
$rows .= '<Row ss:Height="25">'
    . '<Cell ss:MergeAcross="1" ss:StyleID="Section">'
    . '<Data ss:Type="String">1. LEAD STATUS SUMMARY</Data>'
    . '</Cell></Row>';

$rows .= '<Row>'
    . summaryCell('Lead Status', 'Header')
    . summaryCell('Total Leads', 'Header')
    . '</Row>';

$statusTotal = 0;

if (empty($statusReports)) {
    $rows .= '<Row>'
        . summaryCell('No lead records found', 'Empty')
        . summaryCell(0, 'DataNumber', 'Number')
        . '</Row>';
} else {
    foreach ($statusReports as $row) {
        $count = (int)$row['total'];
        $statusTotal += $count;

        $rows .= '<Row>'
            . summaryCell($row['status'])
            . summaryCell($count, 'DataNumber', 'Number')
            . '</Row>';
    }
}

$rows .= '<Row>'
    . summaryCell('TOTAL STATUS LEADS', 'Total')
    . summaryCell($statusTotal, 'TotalNumber', 'Number')
    . '</Row>';

$rows .= '<Row/>';

/* Section 2: Services */
$rows .= '<Row ss:Height="25">'
    . '<Cell ss:MergeAcross="1" ss:StyleID="Section">'
    . '<Data ss:Type="String">2. SERVICE-WISE LEAD SUMMARY</Data>'
    . '</Cell></Row>';

$rows .= '<Row>'
    . summaryCell('Service', 'Header')
    . summaryCell('Total Leads', 'Header')
    . '</Row>';

$serviceTotal = 0;

if (empty($serviceReports)) {
    $rows .= '<Row>'
        . summaryCell('No services found', 'Empty')
        . summaryCell(0, 'DataNumber', 'Number')
        . '</Row>';
} else {
    foreach ($serviceReports as $row) {
        $count = (int)$row['total'];
        $serviceTotal += $count;

        $rows .= '<Row>'
            . summaryCell($row['service_name'])
            . summaryCell($count, 'DataNumber', 'Number')
            . '</Row>';
    }
}

$rows .= '<Row>'
    . summaryCell('TOTAL SERVICE LEADS', 'Total')
    . summaryCell($serviceTotal, 'TotalNumber', 'Number')
    . '</Row>';

$rows .= '<Row/>';

/* Section 3: Sources */
$rows .= '<Row ss:Height="25">'
    . '<Cell ss:MergeAcross="1" ss:StyleID="Section">'
    . '<Data ss:Type="String">3. LEAD SOURCE SUMMARY</Data>'
    . '</Cell></Row>';

$rows .= '<Row>'
    . summaryCell('Lead Source', 'Header')
    . summaryCell('Total Leads', 'Header')
    . '</Row>';

$sourceTotal = 0;

if (empty($sourceReports)) {
    $rows .= '<Row>'
        . summaryCell('No lead sources found', 'Empty')
        . summaryCell(0, 'DataNumber', 'Number')
        . '</Row>';
} else {
    foreach ($sourceReports as $row) {
        $count = (int)$row['total'];
        $sourceTotal += $count;

        $rows .= '<Row>'
            . summaryCell($row['source_name'])
            . summaryCell($count, 'DataNumber', 'Number')
            . '</Row>';
    }
}

$rows .= '<Row>'
    . summaryCell('TOTAL SOURCE LEADS', 'Total')
    . summaryCell($sourceTotal, 'TotalNumber', 'Number')
    . '</Row>';

/* Excel-compatible XML workbook */
$xml = '<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook
 xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">

<Styles>
    <Style ss:ID="Default" ss:Name="Normal">
        <Alignment ss:Vertical="Center"/>
        <Font ss:FontName="Calibri" ss:Size="11"/>
    </Style>

    <Style ss:ID="Title">
        <Alignment ss:Vertical="Center"/>
        <Font ss:FontName="Calibri" ss:Size="16"
              ss:Bold="1" ss:Color="#FFFFFF"/>
        <Interior ss:Color="#14243D" ss:Pattern="Solid"/>
    </Style>

    <Style ss:ID="InfoLabel">
        <Font ss:Bold="1" ss:Color="#243B5A"/>
    </Style>

    <Style ss:ID="Info">
        <Alignment ss:Vertical="Center"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous"
                    ss:Weight="1" ss:Color="#D9E2EF"/>
        </Borders>
    </Style>

    <Style ss:ID="Section">
        <Font ss:FontName="Calibri" ss:Size="12"
              ss:Bold="1" ss:Color="#FFFFFF"/>
        <Interior ss:Color="#243B5A" ss:Pattern="Solid"/>
    </Style>

    <Style ss:ID="Header">
        <Alignment ss:Horizontal="Center" ss:Vertical="Center"
                   ss:WrapText="1"/>
        <Font ss:Bold="1" ss:Color="#FFFFFF"/>
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
        <Alignment ss:Vertical="Center" ss:WrapText="1"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous"
                    ss:Weight="1" ss:Color="#D9E2EF"/>
            <Border ss:Position="Left" ss:LineStyle="Continuous"
                    ss:Weight="1" ss:Color="#D9E2EF"/>
            <Border ss:Position="Right" ss:LineStyle="Continuous"
                    ss:Weight="1" ss:Color="#D9E2EF"/>
        </Borders>
    </Style>

    <Style ss:ID="DataNumber">
        <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
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
        <Interior ss:Color="#EAF0FA" ss:Pattern="Solid"/>
        <Borders>
            <Border ss:Position="Top" ss:LineStyle="Continuous"
                    ss:Weight="2" ss:Color="#3478F6"/>
        </Borders>
    </Style>

    <Style ss:ID="TotalNumber">
        <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
        <Font ss:Bold="1" ss:Color="#243B5A"/>
        <Interior ss:Color="#EAF0FA" ss:Pattern="Solid"/>
        <Borders>
            <Border ss:Position="Top" ss:LineStyle="Continuous"
                    ss:Weight="2" ss:Color="#3478F6"/>
        </Borders>
    </Style>

    <Style ss:ID="Empty">
        <Font ss:Italic="1" ss:Color="#6B7280"/>
    </Style>
</Styles>

<Worksheet ss:Name="Summary Reports">
    <Table>
        <Column ss:Width="260"/>
        <Column ss:Width="120"/>
        <Column ss:Width="30"/>
        ' . $rows . '
    </Table>

    <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
        <PageSetup>
            <Layout x:Orientation="Portrait"/>
        </PageSetup>
        <Print>
            <FitWidth>1</FitWidth>
            <FitHeight>0</FitHeight>
            <ValidPrinterInfo/>
            <PaperSizeIndex>9</PaperSizeIndex>
        </Print>
    </WorksheetOptions>
</Worksheet>
</Workbook>';

/* Download */
$filename = 'summary_reports_' . date('Y-m-d_H-i-s') . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";
echo $xml;
exit;
