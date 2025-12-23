<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Allow only admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../surveyor/dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $id = (int) $_POST['id'];

    // Delete survey (and maybe related files/rows if needed)
    $stmt = $pdo->prepare("DELETE FROM surveys WHERE id = ?");
    $stmt->execute([$id]);

    // Redirect back to surveys list
    header("Location: surveys.php?msg=deleted");
    exit;
} else {
    header("Location: surveys.php?error=invalid");
    exit;
}
