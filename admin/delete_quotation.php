<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Redirect if not admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../surveyor/dashboard.php");
    exit;
}

if (!isset($_GET['quotation_id'])) {
    die("Quotation ID not provided.");
}

$quotation_id = $_GET['quotation_id'];

// Optionally confirm existence
$stmt = $pdo->prepare("SELECT id FROM quotations WHERE id = ?");
$stmt->execute([$quotation_id]);
if (!$stmt->fetch()) {
    die("Quotation not found or already deleted.");
}

// Delete the quotation
$delete = $pdo->prepare("DELETE FROM quotations WHERE id = ?");
$delete->execute([$quotation_id]);

header("Location: quotations.php?deleted=1");
exit;
