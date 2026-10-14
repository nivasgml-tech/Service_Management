
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    exit("ZipArchive is not enabled in PHP.");
}

/* Prevent PHP warnings/notices from corrupting the Excel download */
ini_set('display_errors', '0');

$search = trim($_GET['search'] ?? '');

/* Fetch customers using the existing search filter */
$sql = "
    SELECT id, name, mobile, email, city, state, created_at
    FROM customers
    WHERE 1 = 1
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            name LIKE ?
            OR mobile LIKE ?
            OR email LIKE ?
            OR city LIKE ?
            OR state LIKE ?
        )
    ";

    $like = '%' . $search . '%';
    $params = array_fill(0, 5, $like);
}

$sql .= " ORDER BY created_at DESC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Excel XML helpers */
function customerExcelText($value)
{
    $value = (string)($value ?? '');

    $value = preg_replace(
        '/[\x00-\x08\x0B\x0C\x0E-\x1F]/',
        '',
        $value
    );

    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_XML1,
        'UTF-8'
    );
}

function customerExcelColumn($number)
{
    $name = '';

    while ($number > 0) {
        $remainder = ($number - 1) % 26;
        $name = chr(65 + $remainder) . $name;
        $number = intdiv($number - 1, 26);
    }

    return $name;
}

function customerExcelCell($reference, $value, $style = 0, $numeric = false)
{
    $styleAttribute = $style ? ' s="' . $style . '"' : '';

    if ($numeric && is_numeric($value)) {
        return '<c r="' . $reference . '"' . $styleAttribute .
            '><v>' . (int)$value . '</v></c>';
    }

    return '<c r="' . $reference . '" t="inlineStr"' .
        $styleAttribute . '><is><t xml:space="preserve">' .
        customerExcelText($value) .
        '</t></is></c>';
}

/* Report metadata */
$headers = [
    'Customer ID',
    'Customer Name',
    'Mobile',
    'Email',
    'City',
    'State',
    'Created Date'
];

$reportPeriod = $search !== ''
    ? 'Search: ' . $search
    : 'All Customers';

$sheetRows = '';

$sheetRows .= '<row r="1" ht="32" customHeight="1">';
$sheetRows .= customerExcelCell(
    'A1',
    'YOURDREAMHOME - CUSTOMER REPORT',
    1
);
$sheetRows .= '</row>';

$sheetRows .= '<row r="2">';
$sheetRows .= customerExcelCell('A2', 'Report Filter', 2);
$sheetRows .= customerExcelCell('B2', $reportPeriod, 2);
$sheetRows .= '</row>';

$sheetRows .= '<row r="3">';
$sheetRows .= customerExcelCell('A3', 'Generated On', 2);
$sheetRows .= customerExcelCell('B3', date('Y-m-d H:i:s'), 2);
$sheetRows .= '</row>';

$sheetRows .= '<row r="4"></row>';

/* Table headings */
$sheetRows .= '<row r="5" ht="28" customHeight="1">';

foreach ($headers as $index => $heading) {
    $column = customerExcelColumn($index + 1);
    $sheetRows .= customerExcelCell($column . '5', $heading, 3);
}

$sheetRows .= '</row>';

/* Customer records */
$rowNumber = 6;

foreach ($customers as $customer) {
    $values = [
        $customer['id'],
        $customer['name'],
        $customer['mobile'],
        $customer['email'],
        $customer['city'],
        $customer['state'],
        $customer['created_at']
    ];

    $sheetRows .= '<row r="' . $rowNumber . '">';

    foreach ($values as $index => $value) {
        $column = customerExcelColumn($index + 1);

        $sheetRows .= customerExcelCell(
            $column . $rowNumber,
            $value,
            4,
            $index === 0
        );
    }

    $sheetRows .= '</row>';
    $rowNumber++;
}

/* Total row */
$totalRow = $rowNumber + 1;

$sheetRows .= '<row r="' . $totalRow . '">';
$sheetRows .= customerExcelCell(
    'A' . $totalRow,
    'Total Customers',
    5
);
$sheetRows .= customerExcelCell(
    'B' . $totalRow,
    count($customers),
    5,
    true
);
$sheetRows .= '</row>';

$lastDataRow = max(5, $rowNumber - 1);

/* Column widths */
$columnsXml = '
<cols>
    <col min="1" max="1" width="14" customWidth="1"/>
    <col min="2" max="2" width="28" customWidth="1"/>
    <col min="3" max="3" width="20" customWidth="1"/>
    <col min="4" max="4" width="34" customWidth="1"/>
    <col min="5" max="5" width="22" customWidth="1"/>
    <col min="6" max="6" width="22" customWidth="1"/>
    <col min="7" max="7" width="24" customWidth="1"/>
</cols>';

/* Worksheet XML */
$worksheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
 xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheetViews>
        <sheetView workbookViewId="0">
            <pane ySplit="5" topLeftCell="A6"
                activePane="bottomLeft" state="frozen"/>
        </sheetView>
    </sheetViews>
    <sheetFormatPr defaultRowHeight="22"/>
    ' . $columnsXml . '
    <sheetData>' . $sheetRows . '</sheetData>
    <autoFilter ref="A5:G' . $lastDataRow . '"/>
    <mergeCells count="1">
        <mergeCell ref="A1:G1"/>
    </mergeCells>
    <pageMargins left="0.3" right="0.3" top="0.5"
        bottom="0.5" header="0.2" footer="0.2"/>
</worksheet>';

/* Styles XML */
$stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="4">
        <font><sz val="11"/><name val="Calibri"/></font>
        <font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
        <font><b/><color rgb="FF243B5A"/><name val="Calibri"/></font>
        <font><b/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
    </fonts>
    <fills count="4">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid">
            <fgColor rgb="FF14243D"/>
            <bgColor indexed="64"/>
        </patternFill></fill>
        <fill><patternFill patternType="solid">
            <fgColor rgb="FF3478F6"/>
            <bgColor indexed="64"/>
        </patternFill></fill>
    </fills>
    <borders count="2">
        <border><left/><right/><top/><bottom/><diagonal/></border>
        <border>
            <left style="thin"><color rgb="FFD9E2EF"/></left>
            <right style="thin"><color rgb="FFD9E2EF"/></right>
            <top style="thin"><color rgb="FFD9E2EF"/></top>
            <bottom style="thin"><color rgb="FFD9E2EF"/></bottom>
            <diagonal/>
        </border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="6">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="0"
            xfId="0" applyAlignment="1">
            <alignment vertical="center"/>
        </xf>
        <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="3" fillId="3" borderId="1"
            xfId="0" applyAlignment="1" applyFill="1">
            <alignment horizontal="center" vertical="center" wrapText="1"/>
        </xf>
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1"
            xfId="0" applyAlignment="1">
            <alignment vertical="top" wrapText="1"/>
        </xf>
        <xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0"/>
    </cellXfs>
    <cellStyles count="1">
        <cellStyle name="Normal" xfId="0" builtinId="0"/>
    </cellStyles>
</styleSheet>';

/* XLSX package definitions */
$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>';

$rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1"
        Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"
        Target="xl/workbook.xml"/>
</Relationships>';

$workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
 xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="Customer Report" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>';

$workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1"
        Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"
        Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2"
        Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"
        Target="styles.xml"/>
</Relationships>';

/* Create a real XLSX ZIP package */
$tempFile = tempnam(sys_get_temp_dir(), 'ydh_customer_');

if ($tempFile === false) {
    http_response_code(500);
    exit('Could not create temporary Excel file.');
}

$zip = new ZipArchive();
$opened = $zip->open($tempFile, ZipArchive::OVERWRITE);

if ($opened !== true) {
    @unlink($tempFile);
    http_response_code(500);
    exit('Could not open temporary ZIP file. Code: ' . $opened);
}

$filesToAdd = [
    '[Content_Types].xml' => $contentTypes,
    '_rels/.rels' => $rootRels,
    'xl/workbook.xml' => $workbookXml,
    'xl/_rels/workbook.xml.rels' => $workbookRels,
    'xl/styles.xml' => $stylesXml,
    'xl/worksheets/sheet1.xml' => $worksheetXml
];

foreach ($filesToAdd as $path => $contents) {
    if (!$zip->addFromString($path, $contents)) {
        $zip->close();
        @unlink($tempFile);
        http_response_code(500);
        exit('Failed to add Excel component: ' . $path);
    }
}

if (!$zip->close()) {
    @unlink($tempFile);
    http_response_code(500);
    exit('Could not finish Excel workbook.');
}

/* Validate ZIP before downloading */
$checkZip = new ZipArchive();
$checkResult = $checkZip->open($tempFile);

if ($checkResult !== true) {
    @unlink($tempFile);
    http_response_code(500);
    exit('Generated Excel file is not a valid ZIP package.');
}

$requiredFiles = [
    '[Content_Types].xml',
    '_rels/.rels',
    'xl/workbook.xml',
    'xl/_rels/workbook.xml.rels',
    'xl/styles.xml',
    'xl/worksheets/sheet1.xml'
];

foreach ($requiredFiles as $requiredFile) {
    if ($checkZip->locateName($requiredFile) === false) {
        $checkZip->close();
        @unlink($tempFile);
        http_response_code(500);
        exit('Excel package is missing: ' . $requiredFile);
    }
}

$checkZip->close();

/* Send clean download response */
if (headers_sent($sourceFile, $sourceLine)) {
    @unlink($tempFile);
    error_log("Excel export headers already sent at $sourceFile:$sourceLine");
    exit('Excel download failed because output was sent before the file.');
}

$filename = 'customer_report_' . date('Y-m-d_H-i-s') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tempFile));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

while (ob_get_level() > 0) {
    ob_end_clean();
}

readfile($tempFile);
@unlink($tempFile);
exit;
