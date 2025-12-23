<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Redirect if not admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../surveyor/dashboard.php");
    exit;
}

// Fetch all surveys with client info
$stmt = $pdo->query("
    SELECT s.*, c.name AS client_name, c.cnic, c.contact_no_1
    FROM surveys s
    JOIN clients c ON s.client_id = c.id
    ORDER BY s.created_at DESC
");
$surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>All Surveys</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .search-box {
            max-width: 300px;
            float: right;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h4 class="mb-3">All Submitted Surveys</h4>

        <input type="text" class="form-control mb-3 search-box" placeholder="Search by client..." id="searchInput">

        <table class="table table-bordered table-hover" id="surveyTable">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>CNIC</th>
                    <th>Contact</th>
                    <th>System KW</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($surveys as $i => $s): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($s['client_name']) ?></td>
                        <td><?= htmlspecialchars($s['cnic']) ?></td>
                        <td><?= htmlspecialchars($s['contact_no_1']) ?></td>
                        <td><?= htmlspecialchars($s['system_kw']) ?></td>
                        <td><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                        <td>
                            <a href="view_survey.php?id=<?= htmlspecialchars($s['id']) ?>" class="btn btn-sm btn-info">
                                🧾 View
                            </a>
                            <a href="edit_survey.php?id=<?= htmlspecialchars($s['id']) ?>" class="btn btn-sm btn-warning">
                                ✏️ Edit
                            </a>
                            <form action="delete_survey.php" method="POST" style="display:inline;"
                                onsubmit="return confirm('Are you sure you want to delete this survey? This action cannot be undone.');">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($s['id']) ?>">
                                <button type="submit" class="btn btn-sm btn-danger">🗑️ Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a href="dashboard.php" class="btn btn-outline-secondary mt-3">← Back to Dashboard</a>
    </div>

    <script>
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const input = this.value.toLowerCase();
            document.querySelectorAll('#surveyTable tbody tr').forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                row.style.display = name.includes(input) ? '' : 'none';
            });
        });
    </script>
</body>

</html>