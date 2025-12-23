<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$error = '';
$success = '';

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
        $stmt = $pdo->prepare("INSERT INTO clients 
            (name, address, cnic, contact_no_1, contact_no_2, contact_no_3, client_attendent, attendent_contact_1, attendent_contact_2, email, location_map, whatsapp) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $name, $address, $cnic, $contact_no_1, $contact_no_2, $contact_no_3,
            $client_attendent, $attendent_contact_1, $attendent_contact_2,
            $email, $location_map, $whatsapp
        ]);
        $success = "Client added successfully.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Add Client</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h4>Add New Client</h4>

    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <form method="POST" class="card p-4 shadow-sm">

        <div class="mb-3">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">CNIC No</label>
            <input type="text" name="cnic" class="form-control">
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Contact No. 1</label>
                <input type="text" name="contact_no_1" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Contact No. 2</label>
                <input type="text" name="contact_no_2" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Contact No. 3</label>
                <input type="text" name="contact_no_3" class="form-control">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Client Attendent</label>
            <input type="text" name="client_attendent" class="form-control">
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Attendent Contact No. 1</label>
                <input type="text" name="attendent_contact_1" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">Attendent Contact No. 2</label>
                <input type="text" name="attendent_contact_2" class="form-control">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Location Map</label>
            <textarea name="location_map" class="form-control"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Whatsapp</label>
            <input type="text" name="whatsapp" class="form-control">
        </div>

        <div class="d-grid">
            <button class="btn btn-success">Save Client</button>
        </div>
    </form>

    <a href="clients.php" class="btn btn-outline-secondary mt-3">← Back to Clients</a>
</div>
</body>
</html>
