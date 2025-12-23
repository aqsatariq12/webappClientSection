<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Redirect surveyors
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../surveyor/dashboard.php");
    exit;
}

$stmt = $pdo->query("
    SELECT q.*, c.name AS client_name, c.contact_no_1
    FROM quotations q
    JOIN clients c ON q.client_id = c.id
    ORDER BY q.quotation_date DESC
");
$quotations = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Quotations</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .search-box { max-width: 300px; float: right; }
    </style>
</head>
<body>

<div class="container mt-4">
    <h4 class="mb-3">All Quotations</h4>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Quotation deleted successfully.</div>
    <?php endif; ?>

    <div class="d-flex justify-content-between mb-3">
        <input type="text" class="form-control search-box" placeholder="Search by client..." id="searchInput">
        <a href="add_quotation.php" class="btn btn-success">➕ Add New Quotation</a>
    </div>

    <table class="table table-bordered table-hover" id="quotationTable">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Client</th>
                <th>Contact</th>
                <th>Amount</th>
                <th>GST</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($quotations as $i => $q): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($q['client_name']) ?></td>
                <td><?= htmlspecialchars($q['contact_no_1']) ?></td>
                <td>Rs <?= number_format($q['total_amount'], 2) ?></td>
                <td><?= $q['gst_included'] ? 'Yes' : 'No' ?></td>
                <td><?= date('d M Y', strtotime($q['quotation_date'])) ?></td>
                <td>
                    <a href="print_quotation.php?quotation_id=<?= $q['id'] ?>" target="_blank" class="btn btn-sm btn-secondary">🖨️ Print</a>
                    <a href="edit_quotation.php?quotation_id=<?= $q['id'] ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
                    <a href="delete_quotation.php?quotation_id=<?= $q['id'] ?>"
                       onclick="return confirm('Are you sure you want to delete this quotation?')"
                       class="btn btn-sm btn-danger">🗑️ Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="dashboard.php" class="btn btn-outline-secondary mt-3">← Back to Dashboard</a>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function () {
    let input = this.value.toLowerCase();
    let rows = document.querySelectorAll('#quotationTable tbody tr');
    rows.forEach(function (row) {
        let name = row.cells[1].textContent.toLowerCase();
        row.style.display = name.includes(input) ? '' : 'none';
    });
});
</script>

</body>
</html>
