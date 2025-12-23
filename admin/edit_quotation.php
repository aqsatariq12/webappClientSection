<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Redirect if not admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../surveyor/dashboard.php");
    exit;
}

if (!isset($_GET['quotation_id'])) {
    die("Quotation ID not provided.");
}
$quotation_id = $_GET['quotation_id'];

// Fetch existing quotation
$stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ?");
$stmt->execute([$quotation_id]);
$quotation = $stmt->fetch();

if (!$quotation) {
    die("Quotation not found.");
}

// Fetch client list for dropdown
$clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $quotation_date = $_POST['quotation_date'];
    $total_amount = $_POST['total_amount'];
    $gst_included = isset($_POST['gst_included']) ? 1 : 0;
    $details = $_POST['details'];

    $update = $pdo->prepare("UPDATE quotations SET client_id=?, quotation_date=?, total_amount=?, gst_included=?, details=? WHERE id=?");
    $update->execute([$client_id, $quotation_date, $total_amount, $gst_included, $details, $quotation_id]);

    $success = "Quotation updated successfully.";
    $quotation = array_merge($quotation, $_POST);
    $quotation['gst_included'] = $gst_included;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Quotation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h4>Edit Quotation #<?= $quotation_id ?></h4>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" class="card p-4 shadow-sm">
        <div class="mb-3">
            <label class="form-label">Client</label>
            <select name="client_id" class="form-select" required>
                <?php foreach ($clients as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $quotation['client_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Quotation Date</label>
            <input type="date" name="quotation_date" class="form-control"
                   value="<?= $quotation['quotation_date'] ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Total Amount</label>
            <input type="number" name="total_amount" step="0.01" class="form-control"
                   value="<?= $quotation['total_amount'] ?>" required>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" name="gst_included" class="form-check-input" id="gstCheck"
                   <?= $quotation['gst_included'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="gstCheck">GST Included</label>
        </div>

        <div class="mb-3">
            <label class="form-label">Quotation Details</label>
            <textarea name="details" class="form-control" rows="5" required><?= $quotation['details'] ?></textarea>
        </div>

        <div class="d-grid">
            <button class="btn btn-primary">Update Quotation</button>
        </div>
    </form>

    <a href="quotations.php" class="btn btn-secondary mt-3">← Back to Quotations</a>
</div>
</body>
</html>
