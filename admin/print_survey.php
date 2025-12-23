<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Redirect if not admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../surveyor/dashboard.php");
    exit;
}

if (!isset($_GET['survey_id'])) {
    die("Survey ID not specified.");
}

$survey_id = $_GET['survey_id'];

$stmt = $pdo->prepare("
    SELECT s.*, c.name AS client_name, c.cnic, c.address, c.contact_no_1
    FROM surveys s
    JOIN clients c ON s.client_id = c.id
    WHERE s.id = ?
");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

if (!$survey) {
    die("Survey not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Print Survey #<?= $survey_id ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th, td { padding: 10px; text-align: left; vertical-align: top; }
        th { background-color: #f0f0f0; width: 30%; }
        .print-btn { margin-top: 20px; text-align: center; }
        @media print {
            .print-btn { display: none; }
        }
    </style>
</head>
<body>

<div id="survey-content">
    <h2>Survey Summary Report</h2>

    <table border="1">
        <tr><th>Client Name</th><td><?= htmlspecialchars($survey['client_name']) ?></td></tr>
        <tr><th>CNIC</th><td><?= htmlspecialchars($survey['cnic']) ?></td></tr>
        <tr><th>Address</th><td><?= htmlspecialchars($survey['address']) ?></td></tr>
        <tr><th>Contact</th><td><?= htmlspecialchars($survey['contact_no_1']) ?></td></tr>

        <tr><th>System Type</th><td><?= $survey['system_type'] ?></td></tr>
        <tr><th>Connection Type</th><td><?= $survey['connection_type'] ?></td></tr>
        <tr><th>Service Type</th><td><?= $survey['service_type'] ?></td></tr>
        <tr><th>System KW</th><td><?= $survey['system_kw'] ?></td></tr>

        <tr><th>Inverter Details</th><td><?= nl2br($survey['inverter_details']) ?></td></tr>
        <tr><th>Panel Details</th><td><?= nl2br($survey['panel_details']) ?></td></tr>
        <tr><th>Battery Details</th><td><?= nl2br($survey['battery_details']) ?></td></tr>
        <tr><th>Cables Details</th><td><?= nl2br($survey['cables_details']) ?></td></tr>
        <tr><th>Other Equipment</th><td><?= nl2br($survey['other_equipment']) ?></td></tr>
        <tr><th>Net Metering Status</th><td><?= $survey['net_metering_status'] ?></td></tr>
        <tr><th>Notes</th><td><?= nl2br($survey['notes']) ?></td></tr>
        <tr><th>Submitted On</th><td><?= date('d M Y', strtotime($survey['created_at'])) ?></td></tr>
    </table>
</div>

<div class="print-btn">
    <button onclick="window.print()">🖨️ Print</button>
    <button onclick="downloadPDF()">⬇️ Download PDF</button>
    <a href="dashboard.php" style="margin-left:20px;">← Back to Dashboard</a>
</div>

<!-- html2pdf JS CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadPDF() {
    const element = document.getElementById('survey-content');
    html2pdf().from(element).set({
        margin: 0.5,
        filename: 'Survey_Report_<?= $survey_id ?>.pdf',
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
    }).save();
}
</script>

</body>
</html>
