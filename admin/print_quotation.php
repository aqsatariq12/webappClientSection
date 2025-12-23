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

$stmt = $pdo->prepare("
    SELECT q.*, c.name AS client_name, c.cnic, c.contact_no_1, c.address
    FROM quotations q
    JOIN clients c ON q.client_id = c.id
    WHERE q.id = ?
");
$stmt->execute([$quotation_id]);
$q = $stmt->fetch();

if (!$q) die("Quotation not found.");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Quotation #<?= $quotation_id ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h2, h4 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; vertical-align: top; }
        th { background-color: #f8f8f8; width: 30%; }
        .print-btn { margin-top: 30px; text-align: center; }
        @media print { .print-btn { display: none; } }
    </style>
</head>
<body>

<div id="quotation-content">
    <h2>Official Quotation</h2>
    <h4>Quotation ID: <?= $quotation_id ?></h4>

    <table>
        <tr><th>Client Name</th><td><?= htmlspecialchars($q['client_name']) ?></td></tr>
        <tr><th>CNIC</th><td><?= htmlspecialchars($q['cnic']) ?></td></tr>
        <tr><th>Contact</th><td><?= htmlspecialchars($q['contact_no_1']) ?></td></tr>
        <tr><th>Address</th><td><?= htmlspecialchars($q['address']) ?></td></tr>
        <tr><th>Quotation Date</th><td><?= date('d M Y', strtotime($q['quotation_date'])) ?></td></tr>
        <tr><th>Total Amount</th><td><strong>Rs <?= number_format($q['total_amount'], 2) ?></strong></td></tr>
        <tr><th>GST Included</th><td><?= $q['gst_included'] ? 'Yes' : 'No' ?></td></tr>
        <tr><th>Quotation Details</th><td><?= nl2br($q['details']) ?></td></tr>
    </table>
</div>

<div class="print-btn">
    <button onclick="window.print()">🖨️ Print</button>
    <button onclick="downloadPDF()">⬇️ Download PDF</button>
    <a href="quotations.php" style="margin-left:20px;">← Back to Quotations</a>
</div>

<!-- html2pdf.js CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadPDF() {
    const element = document.getElementById('quotation-content');
    html2pdf().from(element).set({
        margin: 0.5,
        filename: 'Quotation_<?= $quotation_id ?>.pdf',
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
    }).save();
}
</script>

</body>
</html>
