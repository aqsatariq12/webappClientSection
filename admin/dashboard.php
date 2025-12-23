<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../surveyor/dashboard.php");
    exit;
}

$admin_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$name = $stmt->fetchColumn();

$total_clients    = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$total_surveys    = $pdo->query("SELECT COUNT(*) FROM surveys")->fetchColumn();
$total_surveyors  = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'surveyor'")->fetchColumn();
$total_admins     = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        footer {
            background-color: #f8f9fa;
            padding: 1rem 0;
            text-align: center;
            margin-top: 50px;
        }
        .logo {
            max-height: 60px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Admin Panel</a>
        <div class="d-flex">
            <a href="../logout.php" class="btn btn-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4 text-center">
    <img src="../assets/logo.png" alt="Company Logo" class="logo mb-2">
    <h4 class="mb-4">Welcome, <?= htmlspecialchars($name) ?></h4>

    <div class="row mb-4 g-3">
        <div class="col-md-3"><div class="card text-white bg-success"><div class="card-body"><h6>Clients</h6><p class="fs-4"><?= $total_clients ?></p></div></div></div>
        <div class="col-md-3"><div class="card text-white bg-info"><div class="card-body"><h6>Surveys</h6><p class="fs-4"><?= $total_surveys ?></p></div></div></div>
        <div class="col-md-3"><div class="card text-white bg-secondary"><div class="card-body"><h6>Surveyors</h6><p class="fs-4"><?= $total_surveyors ?></p></div></div></div>
        <div class="col-md-3"><div class="card text-white bg-dark"><div class="card-body"><h6>Admins</h6><p class="fs-4"><?= $total_admins ?></p></div></div></div>
    </div>

    <canvas id="chartSummary" height="100"></canvas>

    <div class="mt-5 d-grid gap-2 d-md-block">
        <a href="clients.php" class="btn btn-outline-primary">Manage Clients</a>
        <a href="assignments.php" class="btn btn-outline-primary">Assign Surveyors</a>
        <a href="surveys.php" class="btn btn-outline-primary">Manage Surveys</a>
        <a href="surveyors.php" class="btn btn-outline-primary">Manage Surveyors</a>
        <a href="admins.php" class="btn btn-outline-primary">Manage Admins</a>
    </div>
</div>

<footer>
    <div class="container">
        <small>&copy; <?= date('Y') ?> Websol Technologies. All rights reserved.</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('chartSummary').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Clients', 'Surveys', 'Surveyors', 'Admins'],
        datasets: [{
            label: 'Total Count',
            data: [<?= $total_clients ?>, <?= $total_surveys ?>, <?= $total_surveyors ?>, <?= $total_admins ?>],
            backgroundColor: ['#198754', '#0dcaf0', '#6c757d', '#212529']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            title: { display: true, text: 'System Summary (Live Counts)' }
        }
    }
});
</script>

</body>
</html>
