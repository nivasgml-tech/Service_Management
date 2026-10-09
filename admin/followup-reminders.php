
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Overdue follow-ups
$overdueStmt = $pdo->prepare("
    SELECT
        lf.id AS followup_id,
        lf.followup_date,
        lf.notes,
        lf.followup_type,
        l.id AS lead_id,
        l.name,
        l.mobile,
        l.status,
        s.service_name
    FROM lead_followups lf
    INNER JOIN leads l ON lf.lead_id = l.id
    LEFT JOIN services s ON l.service_id = s.id
    WHERE lf.followup_date < ?
      AND l.status NOT IN ('Won', 'Lost', 'Closed')
    ORDER BY lf.followup_date ASC
");
$overdueStmt->execute([$today . ' 00:00:00']);
$overdue = $overdueStmt->fetchAll();

// Today's follow-ups
$todayStmt = $pdo->prepare("
    SELECT
        lf.id AS followup_id,
        lf.followup_date,
        lf.notes,
        lf.followup_type,
        l.id AS lead_id,
        l.name,
        l.mobile,
        l.status,
        s.service_name
    FROM lead_followups lf
    INNER JOIN leads l ON lf.lead_id = l.id
    LEFT JOIN services s ON l.service_id = s.id
    WHERE lf.followup_date >= ?
      AND lf.followup_date < ?
      AND l.status NOT IN ('Won', 'Lost', 'Closed')
    ORDER BY lf.followup_date ASC
");
$todayStmt->execute([
    $today . ' 00:00:00',
    $tomorrow . ' 00:00:00'
]);
$todayFollowups = $todayStmt->fetchAll();

// Upcoming follow-ups
$upcomingStmt = $pdo->prepare("
    SELECT
        lf.id AS followup_id,
        lf.followup_date,
        lf.notes,
        lf.followup_type,
        l.id AS lead_id,
        l.name,
        l.mobile,
        l.status,
        s.service_name
    FROM lead_followups lf
    INNER JOIN leads l ON lf.lead_id = l.id
    LEFT JOIN services s ON l.service_id = s.id
    WHERE lf.followup_date >= ?
      AND l.status NOT IN ('Won', 'Lost', 'Closed')
    ORDER BY lf.followup_date ASC
");
$upcomingStmt->execute([$tomorrow . ' 00:00:00']);
$upcoming = $upcomingStmt->fetchAll();

function renderFollowups($items, $sectionTitle)
{
    ?>
    <section class="reminder-section">
        <h2><?php echo htmlspecialchars($sectionTitle); ?></h2>

        <?php if (empty($items)): ?>
            <p class="empty-message">No follow-ups found.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Lead</th>
                            <th>Mobile</th>
                            <th>Service</th>
                            <th>Date & Time</th>
                            <th>Type</th>
                            <th>Notes</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($item['mobile']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars(
                                        $item['service_name'] ?? 'Not specified'
                                    ); ?>
                                </td>
                                <td>
                                    <?php echo date(
                                        'd-m-Y h:i A',
                                        strtotime($item['followup_date'])
                                    ); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars(
                                        $item['followup_type'] ?? '-'
                                    ); ?>
                                </td>
                                <td class="notes-cell">
                                    <?php echo htmlspecialchars(
                                        $item['notes'] ?? '-'
                                    ); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($item['status']); ?>
                                </td>
                                <td>
                                    <a class="view-btn"
                                       href="lead-details.php?id=<?php
                                           echo (int)$item['lead_id'];
                                       ?>">
                                        View Lead
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
    <title>Follow-up Reminders</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            background: #f4f6f9;
            color: #263238;
            font-family: Arial, sans-serif;
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
            font-size: 28px;
        }

        .subtitle {
            color: #687782;
            margin: 0;
        }

        .back-btn,
        .view-btn {
            display: inline-block;
            padding: 10px 14px;
            background: #1769aa;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 13px;
            font-weight: bold;
            white-space: nowrap;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .summary-card {
            padding: 22px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px #0000000b;
            border-left: 5px solid #1769aa;
        }

        .summary-card.overdue {
            border-left-color: #d93025;
        }

        .summary-card.today {
            border-left-color: #e69a00;
        }

        .summary-card.upcoming {
            border-left-color: #18864b;
        }

        .summary-card p {
            margin: 0 0 10px;
            color: #667580;
        }

        .summary-card strong {
            font-size: 30px;
        }

        .reminder-section {
            background: white;
            padding: 22px;
            margin-bottom: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #0000000b;
        }

        .reminder-section h2 {
            margin-top: 0;
            font-size: 21px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 950px;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #e9edf0;
            text-align: left;
            font-size: 13px;
            vertical-align: top;
        }

        th {
            background: #f6f8fa;
            color: #45545e;
        }

        .notes-cell {
            min-width: 180px;
            max-width: 280px;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .empty-message {
            padding: 15px;
            color: #687782;
            background: #f7f9fb;
            border-radius: 6px;
        }

        @media (max-width: 700px) {
            body {
                padding: 14px;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .reminder-section {
                padding: 14px;
            }
        }
    </style>
</head>
<body>

<div class="page-header">
    <div>
        <h1>Follow-up Reminders</h1>
        <p class="subtitle">
            Manage overdue, today's and upcoming customer follow-ups.
        </p>
    </div>

    <a href="dashboard.php" class="back-btn">
        Back to Dashboard
    </a>
</div>

<div class="summary-grid">
    <div class="summary-card overdue">
        <p>Overdue Follow-ups</p>
        <strong><?php echo count($overdue); ?></strong>
    </div>

    <div class="summary-card today">
        <p>Today's Follow-ups</p>
        <strong><?php echo count($todayFollowups); ?></strong>
    </div>

    <div class="summary-card upcoming">
        <p>Upcoming Follow-ups</p>
        <strong><?php echo count($upcoming); ?></strong>
    </div>
</div>

<?php renderFollowups($overdue, 'Overdue Follow-ups'); ?>
<?php renderFollowups($todayFollowups, "Today's Follow-ups"); ?>
<?php renderFollowups($upcoming, 'Upcoming Follow-ups'); ?>

</body>
</html>