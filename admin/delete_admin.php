<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Redirect if not admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../surveyor/dashboard.php");
    exit;
}

if (!isset($_GET['id'])) die("Missing admin ID.");
$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) die("Admin not found or not deletable.");

// Optional: prevent deleting self
if ($id == $_SESSION['user_id']) {
    die("You cannot delete your own account.");
}

$delete = $pdo->prepare("DELETE FROM users WHERE id = ?");
$delete->execute([$id]);

header("Location: admins.php?deleted=1");
exit;
