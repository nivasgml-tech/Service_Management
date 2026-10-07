<?php

session_start();

if (!isset($_SESSION['admin_id'])) {

    header("Location: login.php");

    exit;

}

require_once "../config/database.php";


/* GET LEAD ID */

$lead_id = $_GET['id'] ?? '';

if (!is_numeric($lead_id)) {

    die("Invalid lead.");

}


/* GET LEAD */

$stmt = $pdo->prepare("
    SELECT
        leads.*,
        services.service_name,
        lead_sources.source_name,
        users.name AS assigned_user

    FROM leads

    LEFT JOIN services
        ON leads.service_id = services.id

    LEFT JOIN lead_sources
        ON leads.source_id = lead_sources.id

    LEFT JOIN users
        ON leads.assigned_to = users.id

    WHERE leads.id = ?

    LIMIT 1
");

$stmt->execute([$lead_id]);

$lead = $stmt->fetch();


if (!$lead) {

    die("Lead not found.");

}


/* GET FOLLOWUPS */

$stmt = $pdo->prepare("
    SELECT
        lead_followups.*,
        users.name AS created_by_name

    FROM lead_followups

    LEFT JOIN users
        ON lead_followups.created_by = users.id

    WHERE lead_followups.lead_id = ?

    ORDER BY lead_followups.followup_date DESC
");

$stmt->execute([$lead_id]);

$followups = $stmt->fetchAll();


/* GET STATUS HISTORY */

$stmt = $pdo->prepare("
    SELECT
        lead_status_history.*,
        users.name AS changed_by_name

    FROM lead_status_history

    LEFT JOIN users
        ON lead_status_history.changed_by = users.id

    WHERE lead_status_history.lead_id = ?

    ORDER BY lead_status_history.created_at DESC
");

$stmt->execute([$lead_id]);

$status_history = $stmt->fetchAll();


/* USERS FOR ASSIGNMENT */

$stmt = $pdo->query("
    SELECT id, name
    FROM users
    WHERE status = 1
    ORDER BY name ASC
");

$users = $stmt->fetchAll();


?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Lead Details | YourDream
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {

            margin: 0;

            font-family: Arial, sans-serif;

            background: #f5f7f9;

            color: #263746;

        }

        .topbar {

            background: #071a2d;

            color: #ffffff;

            padding: 18px 30px;

            display: flex;

            justify-content: space-between;

            align-items: center;

        }

        .logo {

            font-size: 24px;

            font-weight: 700;

        }

        .logo span {

            color: #c6a15b;

        }

        .topbar a {

            color: #ffffff;

            text-decoration: none;

        }

        .container {

            max-width: 1200px;

            margin: auto;

            padding: 40px 20px;

        }

        .back-link {

            display: inline-block;

            margin-bottom: 25px;

            color: #10253d;

            text-decoration: none;

            font-weight: 700;

        }

        .page-title {

            margin-bottom: 30px;

        }

        .page-title h1 {

            margin-bottom: 8px;

        }

        .page-title p {

            color: #687681;

        }

        .grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 25px;

        }

        .card {

            background: #ffffff;

            padding: 30px;

            border-radius: 12px;

            box-shadow:
                0 5px 25px
                rgba(0,0,0,0.05);

        }

        .card.full {

            grid-column: 1 / -1;

        }

        .card h2 {

            margin-top: 0;

            margin-bottom: 25px;

            font-size: 21px;

        }

        .detail-row {

            display: grid;

            grid-template-columns: 160px 1fr;

            gap: 15px;

            padding: 12px 0;

            border-bottom: 1px solid #edf0f2;

        }

        .detail-label {

            font-weight: 700;

            color: #687681;

        }

        .detail-value {

            color: #263746;

        }

        .requirement-box {

            padding: 18px;

            background: #f7f8fa;

            border-radius: 8px;

            line-height: 1.7;

            white-space: pre-wrap;

        }

        .form-group {

            margin-bottom: 18px;

        }

        label {

            display: block;

            margin-bottom: 7px;

            font-size: 14px;

            font-weight: 700;

        }

        select,
        textarea,
        input {

            width: 100%;

            padding: 12px;

            border: 1px solid #d9e0e6;

            border-radius: 7px;

            font-family: inherit;

        }

        textarea {

            resize: vertical;

        }

        button {

            border: 0;

            padding: 12px 22px;

            background: #10253d;

            color: #ffffff;

            border-radius: 6px;

            font-weight: 700;

            cursor: pointer;

        }

        button:hover {

            background: #173653;

        }

        .history-item {

            padding: 18px 0;

            border-bottom: 1px solid #edf0f2;

        }

        .history-item:last-child {

            border-bottom: 0;

        }

        .history-title {

            font-weight: 700;

            margin-bottom: 6px;

        }

        .history-meta {

            font-size: 13px;

            color: #7a8791;

            margin-bottom: 8px;

        }

        .empty {

            color: #7a8791;

        }

        @media (max-width: 800px) {

            .grid {

                grid-template-columns: 1fr;

            }

            .card.full {

                grid-column: auto;

            }

            .detail-row {

                grid-template-columns: 1fr;

                gap: 5px;

            }

        }

    </style>

</head>

<body>


<div class="topbar">

    <div class="logo">

        Your<span>Dream</span> Admin

    </div>

    <a href="logout.php">
        Logout
    </a>

</div>


<div class="container">


    <a
        href="leads.php"
        class="back-link"
    >
        ← Back to Leads
    </a>


    <div class="page-title">

        <h1>
            Lead #<?php echo $lead['id']; ?>
        </h1>

        <p>
            Lead details and follow-up management
        </p>

    </div>


    <div class="grid">


        <!-- CUSTOMER DETAILS -->

        <div class="card">

            <h2>
                Customer Details
            </h2>


            <div class="detail-row">

                <div class="detail-label">
                    Name
                </div>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $lead['name']
                    );
                    ?>

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Mobile
                </div>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $lead['mobile']
                    );
                    ?>

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Email
                </div>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $lead['email'] ?? '-'
                    );
                    ?>

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Service
                </div>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $lead['service_name'] ?? '-'
                    );
                    ?>

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Source
                </div>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $lead['source_name'] ?? 'Unknown'
                    );
                    ?>

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Preferred Date
                </div>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $lead['preferred_date'] ?? '-'
                    );
                    ?>

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Preferred Time
                </div>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $lead['preferred_time'] ?? '-'
                    );
                    ?>

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Assigned To
                </div>

                <div class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $lead['assigned_user'] ?? 'Not Assigned'
                    );
                    ?>

                </div>

            </div>

        </div>


        <!-- STATUS UPDATE -->

        <div class="card">

            <h2>
                Update Lead
            </h2>


            <form
                method="POST"
                action="update-lead.php"
            >

                <input
                    type="hidden"
                    name="lead_id"
                    value="<?php echo $lead['id']; ?>"
                >


                <div class="form-group">

                    <label>
                        Lead Status
                    </label>

                    <select
                        name="status"
                        required
                    >

                        <?php

                        $statuses = [
                            'New',
                            'Contacted',
                            'Follow-up',
                            'Qualified',
                            'Quotation Sent',
                            'Negotiation',
                            'Won',
                            'Lost',
                            'Closed'
                        ];

                        foreach ($statuses as $status):

                        ?>

                            <option
                                value="<?php echo htmlspecialchars($status); ?>"
                                <?php
                                if (
                                    $lead['status'] == $status
                                ) {
                                    echo 'selected';
                                }
                                ?>
                            >

                                <?php
                                echo htmlspecialchars($status);
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Assign To
                    </label>

                    <select
                        name="assigned_to"
                    >

                        <option value="">
                            Not Assigned
                        </option>

                        <?php foreach ($users as $user): ?>

                            <option
                                value="<?php echo $user['id']; ?>"
                                <?php
                                if (
                                    $lead['assigned_to']
                                    == $user['id']
                                ) {
                                    echo 'selected';
                                }
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $user['name']
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Remarks
                    </label>

                    <textarea
                        name="remarks"
                        rows="5"
                        placeholder="Enter status update remarks..."
                    ></textarea>

                </div>


                <button type="submit">

                    Update Lead

                </button>

            </form>

        </div>


        <!-- REQUIREMENT -->

        <div class="card full">

            <h2>
                Requirement
            </h2>

            <div class="requirement-box">

                <?php

                echo nl2br(
                    htmlspecialchars(
                        $lead['requirement'] ?? 'No requirement provided.'
                    )
                );

                ?>

            </div>

        </div>


        <!-- ADD FOLLOW-UP -->

        <div class="card">

            <h2>
                Add Follow-up
            </h2>


            <form
                method="POST"
                action="add-followup.php"
            >

                <input
                    type="hidden"
                    name="lead_id"
                    value="<?php echo $lead['id']; ?>"
                >


                <div class="form-group">

                    <label>
                        Follow-up Date & Time
                    </label>

                    <input
                        type="datetime-local"
                        name="followup_date"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Follow-up Type
                    </label>

                    <select
                        name="followup_type"
                    >

                        <option value="Call">
                            Call
                        </option>

                        <option value="WhatsApp">
                            WhatsApp
                        </option>

                        <option value="Meeting">
                            Meeting
                        </option>

                        <option value="Email">
                            Email
                        </option>

                        <option value="Other">
                            Other
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Notes
                    </label>

                    <textarea
                        name="notes"
                        rows="5"
                        placeholder="Enter follow-up notes..."
                    ></textarea>

                </div>


                <button type="submit">

                    Save Follow-up

                </button>

            </form>

        </div>


        <!-- FOLLOW-UP HISTORY -->

        <div class="card">

            <h2>
                Follow-up History
            </h2>


            <?php if (count($followups) > 0): ?>

                <?php foreach ($followups as $followup): ?>

                    <div class="history-item">

                        <div class="history-title">

                            <?php
                            echo htmlspecialchars(
                                $followup['followup_type']
                                ?? 'Follow-up'
                            );
                            ?>

                        </div>

                        <div class="history-meta">

                            <?php
                            echo date(
                                'd-m-Y h:i A',
                                strtotime(
                                    $followup['followup_date']
                                )
                            );
                            ?>

                            <?php
                            if (!empty($followup['created_by_name'])) {
                                echo " • " .
                                    htmlspecialchars(
                                        $followup['created_by_name']
                                    );
                            }
                            ?>

                        </div>

                        <div>

                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $followup['notes'] ?? ''
                                )
                            );
                            ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <p class="empty">
                    No follow-ups added yet.
                </p>

            <?php endif; ?>

        </div>


        <!-- STATUS HISTORY -->

        <div class="card full">

            <h2>
                Status History
            </h2>


            <?php if (count($status_history) > 0): ?>

                <?php foreach ($status_history as $history): ?>

                    <div class="history-item">

                        <div class="history-title">

                            <?php
                            echo htmlspecialchars(
                                $history['old_status']
                                ?? 'New'
                            );
                            ?>

                            →

                            <?php
                            echo htmlspecialchars(
                                $history['new_status']
                            );
                            ?>

                        </div>

                        <div class="history-meta">

                            <?php
                            echo date(
                                'd-m-Y h:i A',
                                strtotime(
                                    $history['created_at']
                                )
                            );
                            ?>

                            <?php
                            if (!empty($history['changed_by_name'])) {
                                echo " • " .
                                    htmlspecialchars(
                                        $history['changed_by_name']
                                    );
                            }
                            ?>

                        </div>

                        <div>

                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $history['remarks'] ?? ''
                                )
                            );
                            ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <p class="empty">
                    No status history yet.
                </p>

            <?php endif; ?>

        </div>


    </div>

</div>

</body>

</html>
