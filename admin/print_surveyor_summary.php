<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Redirect if not admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../surveyor/dashboard.php");
    exit;
}

if (!isset($_GET['surveyor_id'])) {
    die("Surveyor ID not specified.");
}
$surveyor_id = $_GET['surveyor_id'];

$surveyor = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'surveyor'");
$surveyor->execute([$surveyor_id]);
$user = $surveyor->fetch();

if (!$user) die("Surveyor not found.");

$stmt = $pdo->prepare("
    SELECT c.name, c.address, c.contact_no_1, a.assigned_date
    FROM assignments a
    JOIN clients c ON c.id = a.client_id
    WHERE a.surveyor_id = ?
    ORDER BY a.assigned_date DESC
");
$stmt->execute([$surveyor_id]);
$clients = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Surveyor Summary - <?= htmlspecialchars($user['name']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h2, h3 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #999; }
        th { background-color: #f0f0f0; }
        .print-btn { margin-top: 20px; text-align: center; }
        @media print { .print-btn { display: none; } }
    </style>
</head>
<body>

<div id="surveyor-summary">
    <h2>Surveyor Assignment Summary</h2>
    <h3><?= htmlspecialchars($user['name']) ?> (<?= $user['email'] ?>)</h3>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Client Name</th>
                <th>Address</th>
                <th>Contact</th>
                <th>Assigned Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clients as $i => $c): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($c['name']) ?></td>
                <td><?= htmlspecialchars($c['address']) ?></td>
                <td><?= htmlspecialchars($c['contact_no_1']) ?></td>
                <td><?= $c['assigned_date'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="print-btn">
    <button onclick="window.print()">🖨️ Print</button>
    <button onclick="downloadPDF()">⬇️ Download PDF</button>
    <a href="dashboard.php" style="margin-left:20px;">← Back</a>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadPDF() {
    const element = document.getElementById('surveyor-summary');
    html2pdf().from(element).set({
        margin: 0.5,
        filename: 'Surveyor_Assignment_<?= $user['id'] ?>.pdf',
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
    }).save();
}
</script>
</body>
</html>
