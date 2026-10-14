
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

/* Read filters */
$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$service_filter = trim($_GET['service_id'] ?? '');
$source_filter = trim($_GET['source_id'] ?? '');
$conversion_filter = trim($_GET['conversion'] ?? '');

/* Build the same filtered query as leads.php */
$sql = "
    SELECT
        leads.id,
        leads.name,
        leads.mobile,
        leads.email,
        leads.requirement,
        leads.preferred_date,
        leads.preferred_time,
        leads.status,
        leads.created_at,
        leads.customer_id,
        services.service_name,
        lead_sources.source_name,
        users.name AS assigned_user,
        customers.name AS linked_customer_name,
        (
            SELECT MIN(lf.followup_date)
            FROM lead_followups lf
            WHERE lf.lead_id = leads.id
              AND lf.followup_date >= NOW()
        ) AS next_followup
    FROM leads
    LEFT JOIN services
        ON leads.service_id = services.id
    LEFT JOIN lead_sources
        ON leads.source_id = lead_sources.id
    LEFT JOIN users
        ON leads.assigned_to = users.id
    LEFT JOIN customers
        ON leads.customer_id = customers.id
    WHERE 1 = 1
";

$params = [];

/* Search */
if ($search !== '') {
    $sql .= "
        AND (
            leads.name LIKE ?
            OR leads.mobile LIKE ?
            OR leads.email LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}

/* Status filter */
if ($status_filter !== '') {
    $sql .= " AND leads.status = ?";
    $params[] = $status_filter;
}

/* Service filter */
if ($service_filter !== '' && ctype_digit($service_filter)) {
    $sql .= " AND leads.service_id = ?";
    $params[] = (int)$service_filter;
}

/* Source filter */
if ($source_filter !== '' && ctype_digit($source_filter)) {
    $sql .= " AND leads.source_id = ?";
    $params[] = (int)$source_filter;
}

/* Conversion filter */
if ($conversion_filter === 'converted') {
    $sql .= "
        AND leads.customer_id IS NOT NULL
        AND EXISTS (
            SELECT 1
            FROM customers c
            WHERE c.id = leads.customer_id
        )
    ";
} elseif ($conversion_filter === 'not_converted') {
    $sql .= "
        AND (
            leads.customer_id IS NULL
            OR NOT EXISTS (
                SELECT 1
                FROM customers c
                WHERE c.id = leads.customer_id
            )
        )
    ";
}

$sql .= " ORDER BY leads.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* XML-safe text */
function leadExcelText($value)
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

/* Format date safely */
function leadExcelDate($value, $format = 'd-m-Y H:i')
{
    if (empty($value)) {
        return '';
    }

    $timestamp = strtotime($value);

    return $timestamp ? date($format, $timestamp) : '';
}

/* Report filter description */
$filterDescription = [];

if ($search !== '') {
    $filterDescription[] = 'Search: ' . $search;
}
if ($status_filter !== '') {
    $filterDescription[] = 'Status: ' . $status_filter;
}
if ($service_filter !== '') {
    $filterDescription[] = 'Service ID: ' . $service_filter;
}
if ($source_filter !== '') {
    $filterDescription[] = 'Source ID: ' . $source_filter;
}
if ($conversion_filter === 'converted') {
    $filterDescription[] = 'Conversion: Converted';
} elseif ($conversion_filter === 'not_converted') {
    $filterDescription[] = 'Conversion: Not Converted';
}

$filterText = $filterDescription
    ? implode(' | ', $filterDescription)
    : 'All Leads';

/* Table headings */
$headers = [
    'Lead ID',
    'Customer Name',
    'Mobile',
    'Email',
    'Customer Record',
    'Service',
    'Lead Source',
    'Requirement',
    'Preferred Date',
    'Preferred Time',
    'Status',
    'Assigned To',
    'Next Follow-up',
    'Created Date'
];

/* Create title and filter rows */
$rows = '';

$rows .= '<Row ss:Height="32">
    <Cell ss:MergeAcross="13" ss:StyleID="Title">
        <Data ss:Type="String">YOURDREAMHOME - LEAD MANAGEMENT REPORT</Data>
    </Cell>
</Row>';

$rows .= '<Row>
    <Cell ss:StyleID="InfoLabel">
        <Data ss:Type="String">Applied Filters</Data>
    </Cell>
    <Cell ss:MergeAcross="12" ss:StyleID="Info">
        <Data ss:Type="String">' . leadExcelText($filterText) . '</Data>
    </Cell>
</Row>';

$rows .= '<Row>
    <Cell ss:StyleID="InfoLabel">
        <Data ss:Type="String">Generated On</Data>
    </Cell>
    <Cell ss:MergeAcross="12" ss:StyleID="Info">
        <Data ss:Type="String">' . date('Y-m-d H:i:s') . '</Data>
    </Cell>
</Row>';

$rows .= '<Row/>';

/* Column header row */
$rows .= '<Row ss:Height="32">';

foreach ($headers as $header) {
    $rows .= '<Cell ss:StyleID="Header">
        <Data ss:Type="String">' . leadExcelText($header) . '</Data>
    </Cell>';
}

$rows .= '</Row>';

/* Data rows */
foreach ($leads as $lead) {
    $customerRecord = '';

    if (!empty($lead['customer_id']) &&
        !empty($lead['linked_customer_name'])) {
        $customerRecord = 'Converted - ' . $lead['linked_customer_name'];
    } else {
        $customerRecord = 'Not Converted';
    }

    $values = [
        $lead['id'],
        $lead['name'],
        $lead['mobile'],
        $lead['email'],
        $customerRecord,
        $lead['service_name'] ?? '-',
        $lead['source_name'] ?? 'Unknown',
        $lead['requirement'] ?? '',
        leadExcelDate($lead['preferred_date'], 'd-m-Y'),
        $lead['preferred_time'] ?? '',
        $lead['status'],
        $lead['assigned_user'] ?? 'Not Assigned',
        leadExcelDate($lead['next_followup']),
        leadExcelDate($lead['created_at'])
    ];

    $rows .= '<Row>';

    foreach ($values as $index => $value) {
        if ($index === 0) {
            $rows .= '<Cell ss:StyleID="DataNumber">
                <Data ss:Type="Number">' . (int)$value . '</Data>
            </Cell>';
        } else {
            $rows .= '<Cell ss:StyleID="Data">
                <Data ss:Type="String">' .
                    leadExcelText($value) .
                '</Data>
            </Cell>';
        }
    }

    $rows .= '</Row>';
}

/* Total row */
$rows .= '<Row>
    <Cell ss:StyleID="Total">
        <Data ss:Type="String">TOTAL LEADS</Data>
    </Cell>
    <Cell ss:StyleID="TotalNumber">
        <Data ss:Type="Number">' . count($leads) . '</Data>
    </Cell>
</Row>';

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
        <Alignment ss:Vertical="Top"/>
        <Font ss:FontName="Calibri" ss:Size="10"/>
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
        <Alignment ss:Vertical="Center" ss:WrapText="1"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous"
                    ss:Weight="1" ss:Color="#D9E2EF"/>
        </Borders>
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
</Styles>

<Worksheet ss:Name="Lead Management">
    <Table>
        <Column ss:Width="55"/>
        <Column ss:Width="145"/>
        <Column ss:Width="105"/>
        <Column ss:Width="175"/>
        <Column ss:Width="190"/>
        <Column ss:Width="145"/>
        <Column ss:Width="125"/>
        <Column ss:Width="240"/>
        <Column ss:Width="95"/>
        <Column ss:Width="95"/>
        <Column ss:Width="110"/>
        <Column ss:Width="130"/>
        <Column ss:Width="125"/>
        <Column ss:Width="125"/>
        ' . $rows . '
    </Table>

    <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
        <FreezePanes/>
        <FrozenNoSplit/>
        <SplitHorizontal>4</SplitHorizontal>
        <TopRowBottomPane>4</TopRowBottomPane>
        <ActivePane>2</ActivePane>
        <Print>
            <FitWidth>1</FitWidth>
            <FitHeight>0</FitHeight>
            <ValidPrinterInfo/>
            <PaperSizeIndex>9</PaperSizeIndex>
        </Print>
    </WorksheetOptions>
</Worksheet>
</Workbook>';

/* Download file */
$filename = 'lead_management_' . date('Y-m-d_H-i-s') . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";
echo $xml;
exit;
