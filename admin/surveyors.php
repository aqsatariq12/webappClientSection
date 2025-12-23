<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Fetch all surveyors
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'surveyor' ORDER BY created_at DESC");
$surveyors = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Surveyors</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h4>Surveyors</h4>

    <a href="add_surveyor.php" class="btn btn-success mb-3">➕ Add New Surveyor</a>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($surveyors as $i => $s): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($s['name']) ?></td>
                <td><?= htmlspecialchars($s['email']) ?></td>
                <td><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                <td>
                    <a href="edit_surveyor.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
                    <a href="delete_surveyor.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-danger"
                       onclick="return confirm('Are you sure to delete this surveyor?')">🗑️ Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <a href="dashboard.php" class="btn btn-outline-secondary mt-3">← Back to Dashboard</a>
</div>

</body>
</html>
