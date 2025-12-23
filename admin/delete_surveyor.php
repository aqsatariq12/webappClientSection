<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Redirect if not admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../surveyor/dashboard.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Surveyor ID is missing.");
}
$id = $_GET['id'];

// Validate role before deletion
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'surveyor'");
$stmt->execute([$id]);
$surveyor = $stmt->fetch();

if (!$surveyor) {
    die("Surveyor not found or cannot be deleted.");
}

// Optionally: check if this surveyor is assigned to any client
$assignmentCheck = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE surveyor_id = ?");
$assignmentCheck->execute([$id]);
$assigned = $assignmentCheck->fetchColumn();

if ($assigned > 0) {
    die("This surveyor is assigned to clients and cannot be deleted.");
}

// Perform delete
$delete = $pdo->prepare("DELETE FROM users WHERE id = ?");
$delete->execute([$id]);

header("Location: surveyors.php?deleted=1");
exit;
