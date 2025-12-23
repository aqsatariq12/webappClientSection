<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../surveyor/dashboard.php");
    exit;
}

if (!isset($_GET['survey_id'])) {
    die("Survey ID missing.");
}

$survey_id = $_GET['survey_id'];

// Get survey & client
$stmt = $pdo->prepare("
    SELECT s.*, c.name AS client_name, c.cnic, c.address, c.contact_no_1
    FROM surveys s
    JOIN clients c ON s.client_id = c.id
    WHERE s.id = ?
");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

if (!$survey) die("Survey not found.");

// Fetch attachments
$attachments = $pdo->prepare("SELECT * FROM survey_images WHERE survey_id = ?");
$attachments->execute([$survey_id]);
$files = $attachments->fetchAll();

// Public link
$public_link = isset($survey['token']) && $survey['token']
    ? "../public/view_survey.php?token=" . $survey['token']
    : null;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>View Survey</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .file-preview { max-width: 150px; max-height: 150px; object-fit: cover; }
    </style>
</head>
<body>
<div class="container mt-4">
    <h4 class="mb-3">Survey Details</h4>

    <?php if ($public_link): ?>
        <div class="alert alert-info">
            🔗 <strong>Public Link:</strong> <a href="<?= $public_link ?>" target="_blank"><?= $public_link ?></a>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Client Information</div>
        <div class="card-body">
            <p><strong>Name:</strong> <?= htmlspecialchars($survey['client_name']) ?></p>
            <p><strong>CNIC:</strong> <?= htmlspecialchars($survey['cnic']) ?></p>
            <p><strong>Contact:</strong> <?= htmlspecialchars($survey['contact_no_1']) ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($survey['address']) ?></p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-info text-white">System Info</div>
        <div class="card-body">
            <p><strong>System Type:</strong> <?= $survey['system_type'] ?></p>
            <p><strong>Connection Type:</strong> <?= $survey['connection_type'] ?></p>
            <p><strong>Service Type:</strong> <?= $survey['service_type'] ?></p>
            <p><strong>System KW:</strong> <?= $survey['system_kw'] ?></p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">Technical Details</div>
        <div class="card-body">
            <p><strong>Inverter:</strong> <?= nl2br(htmlspecialchars($survey['inverter_details'])) ?></p>
            <p><strong>Panels:</strong> <?= nl2br(htmlspecialchars($survey['panel_details'])) ?></p>
            <p><strong>Battery:</strong> <?= nl2br(htmlspecialchars($survey['battery_details'])) ?></p>
            <p><strong>Cables:</strong> <?= nl2br(htmlspecialchars($survey['cables_details'])) ?></p>
            <p><strong>Other Equipment:</strong> <?= nl2br(htmlspecialchars($survey['other_equipment'])) ?></p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-dark text-white">Status & Notes</div>
        <div class="card-body">
            <p><strong>Net Metering:</strong> <?= $survey['net_metering_status'] ?></p>
            <p><strong>Notes:</strong> <?= nl2br(htmlspecialchars($survey['notes'])) ?></p>
            <p><strong>Submitted:</strong> <?= date('d M Y', strtotime($survey['created_at'])) ?></p>
        </div>
    </div>

    <?php if ($files): ?>
    <div class="card mb-4">
        <div class="card-header bg-success text-white">Attachments</div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($files as $f): ?>
                <div class="col-md-3 text-center mb-3">
                    <p class="mb-1"><strong><?= htmlspecialchars($f['image_type']) ?></strong></p>
                    <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $f['image_path'])): ?>
                        <a href="../uploads/<?= $f['image_path'] ?>" target="_blank">
                            <img src="../uploads/<?= $f['image_path'] ?>" class="img-fluid file-preview">
                        </a>
                    <?php else: ?>
                        <a href="../uploads/<?= $f['image_path'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm">📄 View File</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <a href="surveys.php" class="btn btn-outline-secondary mt-3">← Back to Surveys</a>
</div>
</body>
</html>
