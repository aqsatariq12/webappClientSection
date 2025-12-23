<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

if (!isset($_GET['survey_id'])) {
    die("Survey not specified.");
}

$survey_id = $_GET['survey_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $image_type = $_POST['image_type'];

    if (isset($_FILES['survey_image']) && $_FILES['survey_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['survey_image']['tmp_name'];
        $fileName = basename($_FILES['survey_image']['name']);
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            $error = "Only JPG, PNG or PDF files allowed.";
        } else {
            $newFileName = uniqid('survey_', true) . '.' . $fileExtension;
            $destination = "../uploads/" . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destination)) {
                $stmt = $pdo->prepare("INSERT INTO survey_images (survey_id, image_type, image_path) VALUES (?, ?, ?)");
                $stmt->execute([$survey_id, $image_type, $newFileName]);

                $success = "File uploaded successfully.";
            } else {
                $error = "Error moving the file.";
            }
        }
    } else {
        $error = "Please select a valid file.";
    }
}

// Fetch previous uploads
$stmt = $pdo->prepare("SELECT * FROM survey_images WHERE survey_id = ?");
$stmt->execute([$survey_id]);
$uploads = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Survey Files</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h4 class="mb-4">Upload Photos or Attachments</h4>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php elseif (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="card p-4 shadow-sm mb-4">
        <div class="mb-3">
            <label class="form-label">File Type</label>
            <select name="image_type" class="form-select" required>
                <option value="">-- Select --</option>
                <option value="Bill Pic">Bill Pic</option>
                <option value="CNIC Front">CNIC Front</option>
                <option value="CNIC Back">CNIC Back</option>
                <option value="Panel Pic">Panel Pic</option>
                <option value="Inverter Pic">Inverter Pic</option>
                <option value="Other Attachment">Other Attachment</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Choose File</label>
            <input type="file" name="survey_image" class="form-control" required>
        </div>

        <div class="d-grid">
            <button class="btn btn-primary" type="submit">Upload File</button>
        </div>
    </form>

    <h5>Uploaded Files</h5>
    <ul class="list-group">
        <?php foreach ($uploads as $upload): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <?= $upload['image_type'] ?>
                <a href="../uploads/<?= $upload['image_path'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">View</a>
            </li>
        <?php endforeach; ?>
    </ul>

    <a href="dashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
