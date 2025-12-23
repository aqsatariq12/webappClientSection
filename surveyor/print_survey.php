<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Only allow admin to view this page
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../surveyor/dashboard.php");
    exit;
}

if (!isset($_GET['survey_id'])) {
    die("Survey ID not specified.");
}

$survey_id = $_GET['survey_id'];

$stmt = $pdo->prepare("
    SELECT s.*, c.name AS client_name, c.cnic, c.address, c.contact_no_1
    FROM surveys s
    JOIN clients c ON s.client_id = c.id
    WHERE s.id = ?
");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

if (!$survey) {
    die("Survey not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Survey #<?= $survey_id ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h4 class="mb-3">Survey Details</h4>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            Client Information
        </div>
        <div class="card-body">
            <p><strong>Name:</strong> <?= htmlspecialchars($survey['client_name']) ?></p>
            <p><strong>CNIC:</strong> <?= htmlspecialchars($survey['cnic']) ?></p>
            <p><strong>Contact:</strong> <?= htmlspecialchars($survey['contact_no_1']) ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($survey['address']) ?></p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            System Information
        </div>
        <div class="card-body">
            <p><strong>System Type:</strong> <?= $survey['system_type'] ?></p>
            <p><strong>Connection Type:</strong> <?= $survey['connection_type'] ?></p>
            <p><strong>Service Type:</strong> <?= $survey['service_type'] ?></p>
            <p><strong>System KW:</strong> <?= $survey['system_kw'] ?></p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            Technical Details
        </div>
        <div class="card-body">
            <p><strong>Inverter Details:</strong><br><?= nl2br(htmlspecialchars($survey['inverter_details'])) ?></p>
            <p><strong>Panel Details:</strong><br><?= nl2br(htmlspecialchars($survey['panel_details'])) ?></p>
            <p><strong>Battery Details:</strong><br><?= nl2br(htmlspecialchars($survey['battery_details'])) ?></p>
            <p><strong>Cables Details:</strong><br><?= nl2br(htmlspecialchars($survey['cables_details'])) ?></p>
            <p><strong>Other Equipment:</strong><br><?= nl2br(htmlspecialchars($survey['other_equipment'])) ?></p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            Status & Notes
        </div>
        <div class="card-body">
            <p><strong>Net Metering Status:</strong> <?= $survey['net_metering_status'] ?></p>
            <p><strong>Notes:</strong><br><?= nl2br(htmlspecialchars($survey['notes'])) ?></p>
            <p><strong>Submitted On:</strong> <?= date('d M Y', strtotime($survey['created_at'])) ?></p>
        </div>
    </div>

    <a href="surveys.php" class="btn btn-outline-secondary">← Back to Surveys</a>
</div>
</body>
</html>
