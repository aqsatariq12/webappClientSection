<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Redirect if not admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../surveyor/dashboard.php");
    exit;
}

if (!isset($_GET['client_id'])) {
    die("Client ID not specified.");
}
$client_id = $_GET['client_id'];

$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();

if (!$client) die("Client not found.");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Client Profile #<?= $client_id ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th, td { padding: 10px; text-align: left; vertical-align: top; }
        th { background-color: #f0f0f0; width: 30%; }
        .print-btn { margin-top: 20px; text-align: center; }
        @media print { .print-btn { display: none; } }
    </style>
</head>
<body>

<div id="client-content">
    <h2>Client Profile</h2>
    <table border="1">
        <tr><th>Name</th><td><?= htmlspecialchars($client['name']) ?></td></tr>
        <tr><th>CNIC</th><td><?= htmlspecialchars($client['cnic']) ?></td></tr>
        <tr><th>Address</th><td><?= htmlspecialchars($client['address']) ?></td></tr>
        <tr><th>Contact #1</th><td><?= htmlspecialchars($client['contact_no_1']) ?></td></tr>
        <tr><th>Contact #2</th><td><?= htmlspecialchars($client['contact_no_2']) ?></td></tr>
        <tr><th>Email</th><td><?= htmlspecialchars($client['email']) ?></td></tr>
        <tr><th>WhatsApp</th><td><?= htmlspecialchars($client['whatsapp']) ?></td></tr>
        <tr><th>Bill No</th><td><?= htmlspecialchars($client['bill_no']) ?></td></tr>
        <tr><th>Created At</th><td><?= date('d M Y', strtotime($client['created_at'])) ?></td></tr>
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
    const element = document.getElementById('client-content');
    html2pdf().from(element).set({
        margin: 0.5,
        filename: 'Client_Profile_<?= $client_id ?>.pdf',
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
    }).save();
}
</script>
</body>
</html>
