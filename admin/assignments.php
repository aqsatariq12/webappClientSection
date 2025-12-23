<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$error = '';
$success = '';

// Assign surveyor to client
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id   = clean($_POST['client_id']);
    $surveyor_id = clean($_POST['surveyor_id']);

    // Prevent duplicate assignments
    $check = $pdo->prepare("SELECT id FROM assignments WHERE client_id = ?");
    $check->execute([$client_id]);

    if ($check->fetch()) {
        $error = "This client is already assigned.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO assignments (client_id, surveyor_id, assigned_date) VALUES (?, ?, NOW())");
        $stmt->execute([$client_id, $surveyor_id]);
        $success = "Surveyor assigned successfully.";
    }
}

// Load all clients and surveyors
$clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll();
$surveyors = $pdo->query("SELECT id, name FROM users WHERE role = 'surveyor' ORDER BY name")->fetchAll();

// View assignments
$assignments = $pdo->query("
    SELECT a.id, c.name AS client_name, u.name AS surveyor_name, a.assigned_date
    FROM assignments a
    JOIN clients c ON a.client_id = c.id
    JOIN users u ON a.surveyor_id = u.id
    ORDER BY a.assigned_date DESC
")->fetchAll();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Surveyor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h4 class="mb-4">Assign Surveyor to Client</h4>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" class="card p-4 shadow-sm mb-4">
        <div class="mb-3">
            <label class="form-label">Select Client</label>
            <select name="client_id" class="form-select" required>
                <option value="">-- Select Client --</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Select Surveyor</label>
            <select name="surveyor_id" class="form-select" required>
                <option value="">-- Select Surveyor --</option>
                <?php foreach ($surveyors as $surveyor): ?>
                    <option value="<?= $surveyor['id'] ?>"><?= htmlspecialchars($surveyor['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Assign Surveyor</button>
        </div>
    </form>

    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
