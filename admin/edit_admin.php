<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

if (!isset($_GET['id'])) die("Admin ID required.");
$id = clean($_GET['id']); // Sanitize GET param

$error = '';
$success = '';

// Fetch admin user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
$stmt->execute([$id]);
$admin = $stmt->fetch();

if (!$admin) die("Admin not found.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = clean($_POST['name']);
    $username = clean($_POST['username']);
    $email    = clean($_POST['email']);

    $check = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $check->execute([$username, $email, $id]);

    if ($check->fetch()) {
        $error = "Username or Email already in use.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $username, $email, $id]);
        $success = "Admin updated successfully.";
    }
}
?>



<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h4>Edit Admin</h4>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" class="card p-4 shadow-sm">
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input name="name" value="<?= htmlspecialchars($admin['name']) ?>" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Username</label>
            <input name="username" value="<?= htmlspecialchars($admin['username']) ?>" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input name="email" type="email" value="<?= htmlspecialchars($admin['email']) ?>" class="form-control" required>
        </div>

        <div class="d-grid">
            <button class="btn btn-primary">Update Admin</button>
        </div>
    </form>

    <a href="admins.php" class="btn btn-outline-secondary mt-3">← Back to Admins</a>
</div>
</body>
</html>
