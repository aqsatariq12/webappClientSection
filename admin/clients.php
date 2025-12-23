<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$clients = $pdo->query("SELECT * FROM clients ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clients</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .search-box { max-width: 300px; float: right; }
        .nowrap { white-space: nowrap; }
    </style>
</head>
<body>
<div class="container mt-4">
    <h4 class="mb-3">All Clients</h4>

    <div class="mb-3 d-flex justify-content-between">
        <a href="add_client.php" class="btn btn-success">+ Add New Client</a>
        <input type="text" class="form-control search-box" placeholder="Search by name..." id="searchInput">
    </div>

    <table class="table table-bordered table-hover" id="clientTable">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>CNIC</th>
                <th>Contact No.1</th>
                <th>Address</th>
                <th>Whatsapp</th>
                <th class="nowrap">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clients as $i => $c): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($c['name']) ?></td>
                <td><?= htmlspecialchars($c['cnic']) ?></td>
                <td><?= htmlspecialchars($c['contact_no_1']) ?></td>
                <td><?= htmlspecialchars($c['address']) ?></td>
                <td><?= htmlspecialchars($c['whatsapp']) ?></td>
                <td class="nowrap">
                    <a href="edit_client.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-primary">✏️ Edit</a>
                    <a href="view_client.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-info">🧾 View</a>
                    <a href="delete_client.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this client?');">❌ Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="dashboard.php" class="btn btn-outline-secondary mt-3">← Back to Dashboard</a>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function () {
    const input = this.value.toLowerCase();
    const rows = document.querySelectorAll('#clientTable tbody tr');
    rows.forEach(row => {
        const name = row.cells[1].textContent.toLowerCase();
        row.style.display = name.includes(input) ? '' : 'none';
    });
});
</script>
</body>
</html>
