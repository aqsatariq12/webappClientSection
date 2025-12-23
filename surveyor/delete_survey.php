<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

if ($_SESSION['role'] !== 'surveyor') {
    header("Location: ../admin/dashboard.php");
    exit;
}

if (!isset($_GET['survey_id'])) {
    die("Survey ID not specified.");
}

$survey_id = clean($_GET['survey_id']); // Sanitize ID

// Verify ownership before deletion
$verify = $pdo->prepare("
    SELECT s.id
    FROM surveys s
    JOIN assignments a ON s.client_id = a.client_id
    WHERE s.id = ? AND a.surveyor_id = ?
");
$verify->execute([$survey_id, $_SESSION['user_id']]);

if (!$verify->fetch()) {
    die("You are not authorized to delete this survey.");
}

// Delete survey
$stmt = $pdo->prepare("DELETE FROM surveys WHERE id = ?");
$stmt->execute([$survey_id]);

header("Location: dashboard.php?deleted=1");
exit;
