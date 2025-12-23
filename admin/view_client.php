<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

if (!isset($_GET['id'])) die("Client ID required.");
$client_id = clean($_GET['id']);

// Fetch client
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();
if (!$client) die("Client not found.");

// Fetch surveys
$surveys = $pdo->prepare("SELECT * FROM surveys WHERE client_id = ? ORDER BY created_at DESC");
$surveys->execute([$client_id]);
$survey_list = $surveys->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Client Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Client Profile</h4>
        <button onclick="window.print()" class="btn btn-outline-dark no-print">🖨️ Print</button>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <p><strong>Full Name:</strong> <?= htmlspecialchars($client['name']) ?></p>
            <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($client['address'])) ?></p>
            <p><strong>CNIC:</strong> <?= htmlspecialchars($client['cnic']) ?></p>
            <p><strong>Contact No. 1:</strong> <?= htmlspecialchars($client['contact_no_1']) ?></p>
            <p><strong>Contact No. 2:</strong> <?= htmlspecialchars($client['contact_no_2']) ?></p>
            <p><strong>Contact No. 3:</strong> <?= htmlspecialchars($client['contact_no_3']) ?></p>
            <p><strong>Client Attendent:</strong> <?= htmlspecialchars($client['client_attendent']) ?></p>
            <p><strong>Attendent Contact No. 1:</strong> <?= htmlspecialchars($client['attendent_contact_1']) ?></p>
            <p><strong>Attendent Contact No. 2:</strong> <?= htmlspecialchars($client['attendent_contact_2']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($client['email']) ?></p>
            <p><strong>Whatsapp:</strong> <?= htmlspecialchars($client['whatsapp']) ?></p>
            <p><strong>Location Map:</strong><br><?= nl2br(htmlspecialchars($client['location_map'])) ?></p>
        </div>
    </div>

    <?php if ($survey_list): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light"><strong>Linked Surveys</strong></div>
        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>System KW</th>
                        <th>System Type</th>
                        <th>Submitted</th>
                        <th class="no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($survey_list as $i => $s): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= $s['system_kw'] ?></td>
                        <td><?= $s['system_type'] ?></td>
                        <td><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                        <td class="no-print">
                            <a href="view_survey.php?survey_id=<?= $s['id'] ?>" class="btn btn-sm btn-info">🧾 View</a>
                            <a href="print_survey.php?survey_id=<?= $s['id'] ?>" class="btn btn-sm btn-secondary" target="_blank">🖨️</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <a href="clients.php" class="btn btn-outline-secondary no-print">← Back to Clients</a>
</div>

</body>
</html>
