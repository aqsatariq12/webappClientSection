<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

if (!isset($_GET['id'])) die("Client ID required.");
$id = clean($_GET['id']);

$error = '';
$success = '';

$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$id]);
$client = $stmt->fetch();

if (!$client) die("Client not found.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name               = clean($_POST['name']);
    $address            = clean($_POST['address']);
    $cnic               = clean($_POST['cnic']);
    $contact_no_1       = clean($_POST['contact_no_1']);
    $contact_no_2       = clean($_POST['contact_no_2']);
    $contact_no_3       = clean($_POST['contact_no_3']);
    $client_attendent   = clean($_POST['client_attendent']);
    $attendent_contact_1 = clean($_POST['attendent_contact_1']);
    $attendent_contact_2 = clean($_POST['attendent_contact_2']);
    $email              = clean($_POST['email']);
    $location_map       = clean($_POST['location_map']);
    $whatsapp           = clean($_POST['whatsapp']);

    if (empty($name)) {
        $error = "Client name is required.";
    } else {
        $stmt = $pdo->prepare("UPDATE clients SET 
            name = ?, address = ?, cnic = ?, contact_no_1 = ?, contact_no_2 = ?, contact_no_3 = ?, 
            client_attendent = ?, attendent_contact_1 = ?, attendent_contact_2 = ?, email = ?, location_map = ?, whatsapp = ? 
            WHERE id = ?");
        $stmt->execute([
            $name, $address, $cnic, $contact_no_1, $contact_no_2, $contact_no_3,
            $client_attendent, $attendent_contact_1, $attendent_contact_2,
            $email, $location_map, $whatsapp, $id
        ]);
        $success = "Client updated successfully.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Client</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h4>Edit Client</h4>

    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <form method="POST" class="card p-4 shadow-sm">

        <div class="mb-3">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($client['name']) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control"><?= htmlspecialchars($client['address']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">CNIC No</label>
            <input type="text" name="cnic" class="form-control" value="<?= htmlspecialchars($client['cnic']) ?>">
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Contact No. 1</label>
                <input type="text" name="contact_no_1" class="form-control" value="<?= htmlspecialchars($client['contact_no_1']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Contact No. 2</label>
                <input type="text" name="contact_no_2" class="form-control" value="<?= htmlspecialchars($client['contact_no_2']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Contact No. 3</label>
                <input type="text" name="contact_no_3" class="form-control" value="<?= htmlspecialchars($client['contact_no_3']) ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Client Attendent</label>
            <input type="text" name="client_attendent" class="form-control" value="<?= htmlspecialchars($client['client_attendent']) ?>">
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Attendent Contact No. 1</label>
                <input type="text" name="attendent_contact_1" class="form-control" value="<?= htmlspecialchars($client['attendent_contact_1']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Attendent Contact No. 2</label>
                <input type="text" name="attendent_contact_2" class="form-control" value="<?= htmlspecialchars($client['attendent_contact_2']) ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($client['email']) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Location Map</label>
            <textarea name="location_map" class="form-control"><?= htmlspecialchars($client['location_map']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Whatsapp</label>
            <input type="text" name="whatsapp" class="form-control" value="<?= htmlspecialchars($client['whatsapp']) ?>">
        </div>

        <div class="d-grid">
            <button class="btn btn-primary">Update Client</button>
        </div>
    </form>

    <a href="clients.php" class="btn btn-outline-secondary mt-3">← Back to Clients</a>
</div>
</body>
</html>
