<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Redirect if not admin
if ($_SESSION['role'] !== 'surveyor') {
    header("Location: ../admin/dashboard.php");
    exit;
}

header("Location: dashboard.php");
exit;

?>