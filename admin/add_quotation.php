<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Redirect if not admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../surveyor/dashboard.php");
    exit;
}

$success = '';
$error = '';

// Fetch client list
$clients = $pdo->query("SELECT id, name FROM clients ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $quotation_date = $_POST['quotation_date'];
    $total_amount = $_POST['total_amount'];
    $gst_included = isset($_POST['gst_included']) ? 1 : 0;
    $details = $_POST['details'];

    if (!$client_id || !$quotation_date || !$total_amount || !$details) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO quotations (client_id, quotation_date, total_amount, gst_included, details) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$client_id, $quotation_date, $total_amount, $gst_included, $details]);
        $success = "Quotation added successfully!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Add Quotation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h4>Add New Quotation</h4>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" class="card p-4 shadow-sm">
        <div class="mb-3">
            <label class="form-label">Select Client</label>
            <select name="client_id" class="form-select" required>
                <option value="">-- Choose Client --</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Quotation Date</label>
            <input type="date" name="quotation_date" value="<?= date('Y-m-d') ?>" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Total Amount (PKR)</label>
            <input type="number" step="0.01" name="total_amount" class="form-control" required>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" name="gst_included" class="form-check-input" id="gst">
            <label class="form-check-label" for="gst">GST Included</label>
        </div>

        <div class="mb-3">
            <label class="form-label">Quotation Details</label>
            <textarea name="details" rows="5" class="form-control" required placeholder="Include specs, materials, terms..."></textarea>
        </div>

        <div class="d-grid">
            <button class="btn btn-primary">Save Quotation</button>
        </div>
    </form>

    <a href="quotations.php" class="btn btn-outline-secondary mt-3">← Back to Quotations</a>
</div>
</body>
</html>
