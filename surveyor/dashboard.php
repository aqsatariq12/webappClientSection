<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

if ($_SESSION['role'] !== 'surveyor') {
    header("Location: ../admin/dashboard.php");
    exit;
}

$surveyor_id = $_SESSION['user_id'];

$user_stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$user_stmt->execute([$surveyor_id]);
$surveyor_name = $user_stmt->fetchColumn();

$assignedStmt = $pdo->prepare("
    SELECT c.id, c.name, c.cnic, c.address, c.contact_no_1, a.assigned_date 
    FROM clients c
    INNER JOIN assignments a ON c.id = a.client_id
    WHERE a.surveyor_id = ?
    ORDER BY a.assigned_date DESC
");
$assignedStmt->execute([$surveyor_id]);
$clients = $assignedStmt->fetchAll();

$surveyStmt = $pdo->prepare("
    SELECT s.*, c.name AS client_name
    FROM surveys s
    JOIN clients c ON s.client_id = c.id
    WHERE s.client_id IN (
        SELECT client_id FROM assignments WHERE surveyor_id = ?
    )
    ORDER BY s.created_at DESC
");
$surveyStmt->execute([$surveyor_id]);
$surveys = $surveyStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Surveyor Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        footer {
            background-color: #f8f9fa;
            padding: 1rem 0;
            text-align: center;
            margin-top: 50px;
        }
        .logo {
            max-height: 60px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand">Surveyor Panel</span>
        <a href="../logout.php" class="btn btn-light">Logout</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="text-center mb-3">
        <img src="../assets/logo.png" alt="Company Logo" class="logo mb-2">
        <h4>Welcome, <?= htmlspecialchars($surveyor_name) ?></h4>
    </div>

    <h5 class="mb-3">Assigned Clients</h5>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Client</th>
                <th>CNIC</th>
                <th>Contact</th>
                <th>Address</th>
                <th>Assigned</th>
                <th>Survey</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clients as $i => $client): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($client['name']) ?></td>
                <td><?= htmlspecialchars($client['cnic']) ?></td>
                <td><?= htmlspecialchars($client['contact_no_1']) ?></td>
                <td><?= htmlspecialchars($client['address']) ?></td>
                <td><?= $client['assigned_date'] ?></td>
                <td>
                    <a href="survey_form.php?client_id=<?= $client['id'] ?>" class="btn btn-sm btn-primary">Fill Survey</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h5 class="mt-5 mb-3">My Submitted Surveys</h5>
    <table class="table table-bordered table-striped">
        <thead class="table-secondary">
            <tr>
                <th>#</th>
                <th>Client</th>
                <th>System KW</th>
                <th>Connection</th>
                <th>Submitted</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($surveys as $i => $s): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($s['client_name']) ?></td>
                <td><?= htmlspecialchars($s['system_kw']) ?></td>
                <td><?= htmlspecialchars($s['connection_type']) ?></td>
                <td><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                <td>
                    <a href="edit_survey.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
                    <a href="delete_survey.php?survey_id=<?= $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure to delete this survey?')">🗑️ Delete</a>
                    <a href="upload_images.php?survey_id=<?= $s['id'] ?>" class="btn btn-sm btn-info">📎 Upload</a>
					<a href="view_survey.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-secondary">🧾 View</a>

                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<footer>
    <div class="container">
        <small>&copy; <?= date('Y') ?> Websol Technologies. All rights reserved.</small>
    </div>
</footer>

</body>
</html>
