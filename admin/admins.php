<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Redirect if not admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../surveyor/dashboard.php");
    exit;
}

$admins = $pdo->query("SELECT * FROM users WHERE role = 'admin' ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Users</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h4>Admin Users</h4>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Admin user deleted successfully.</div>
    <?php endif; ?>

    <a href="add_admin.php" class="btn btn-success mb-3">➕ Add New Admin</a>

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
        <?php foreach ($admins as $i => $admin): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($admin['name']) ?></td>
                <td><?= htmlspecialchars($admin['email']) ?></td>
                <td><?= date('d M Y', strtotime($admin['created_at'])) ?></td>
                <td>
                    <a href="edit_admin.php?id=<?= $admin['id'] ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
                    <a href="delete_admin.php?id=<?= $admin['id'] ?>" class="btn btn-sm btn-danger"
                       onclick="return confirm('Are you sure you want to delete this admin?')">🗑️ Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <a href="dashboard.php" class="btn btn-outline-secondary mt-3">← Back to Dashboard</a>
</div>
</body>
</html>
