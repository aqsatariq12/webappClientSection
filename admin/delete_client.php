<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Redirect if not admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../surveyor/dashboard.php");
    exit;
}

if (!isset($_GET['id'])) die("Missing client ID.");
$id = $_GET['id'];

// Optional: prevent deletion if client has surveys/assignments
$check = $pdo->prepare("SELECT COUNT(*) FROM surveys WHERE client_id = ?");
$check->execute([$id]);
if ($check->fetchColumn() > 0) {
    die("This client has surveys and cannot be deleted.");
}

// Proceed to delete
$stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
$stmt->execute([$id]);

header("Location: clients.php?deleted=1");
exit;
