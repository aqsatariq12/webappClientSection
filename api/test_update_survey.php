<?php
// api/test_update_survey.php
$response = null;
$surveyData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    $id    = trim($_POST['id'] ?? '');

    // If "Load Survey" was clicked
    if (isset($_POST['load'])) {
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['REQUEST_URI']), '/\\')
            . '/surveys.php?action=view&id=' . urlencode($id);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
        $surveyData = curl_exec($ch);
        curl_close($ch);

        if ($surveyData) {
            $surveyData = json_decode($surveyData, true);
            if (isset($surveyData['survey'])) {
                $surveyData = $surveyData['survey']; // flatten so we can use $surveyData['esa_serial']
            }
        }
    }

    // If "Update Survey" was clicked
    if (isset($_POST['update'])) {
        $payload = $_POST;
        unset($payload['token'], $payload['id'], $payload['load'], $payload['update']);

        // decode JSON fields
        foreach (['panel_boxes', 'inverter_details', 'battery_details', 'ac_cables', 'dc_cables', 'battery_cables'] as $j) {
            if (isset($payload[$j])) {
                $decoded = json_decode($payload[$j], true);
                if ($decoded !== null) $payload[$j] = $decoded;
            }
        }

        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['REQUEST_URI']), '/\\')
            . '/surveys.php?action=update&id=' . urlencode($id);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $response = curl_exec($ch);
        curl_close($ch);
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Update Survey Test</title>
</head>

<body>
    <h3>Update Survey (test)</h3>
    <form method="post">
        <label>Token:</label><br>
        <input type="text" name="token" style="width:600px" value="<?= htmlspecialchars($_POST['token'] ?? '') ?>"><br>
        <label>Survey ID:</label><br>
        <input type="text" name="id" value="<?= htmlspecialchars($_POST['id'] ?? '') ?>"><br><br>
        <button type="submit" name="load">Load Survey</button>
    </form>

    <?php if ($surveyData): ?>
        <h4>Edit Survey</h4>
        <form method="post">
            <input type="hidden" name="token" value="<?= htmlspecialchars($_POST['token']) ?>">
            <input type="hidden" name="id" value="<?= htmlspecialchars($_POST['id']) ?>">

            ESA Serial: <input type="text" name="esa_serial" value="<?= htmlspecialchars($surveyData['esa_serial'] ?? '') ?>"><br>
            Client ID: <input type="text" name="client_id" value="<?= htmlspecialchars($surveyData['client_id'] ?? '') ?>"><br>
            Connection Type: <input type="text" name="connection_type" value="<?= htmlspecialchars($surveyData['connection_type'] ?? '') ?>"><br>
            Service Type: <input type="text" name="service_type" value="<?= htmlspecialchars($surveyData['service_type'] ?? '') ?>"><br>
            Bill No: <input type="text" name="bill_no" value="<?= htmlspecialchars($surveyData['bill_no'] ?? '') ?>"><br>
            Bill Pic: <input type="text" name="bill_pic" value="<?= htmlspecialchars($surveyData['bill_pic'] ?? '') ?>"><br>
            Sanction Load: <input type="text" name="sanction_load" value="<?= htmlspecialchars($surveyData['sanction_load'] ?? '') ?>"><br>
            System KW: <input type="text" name="system_kw" value="<?= htmlspecialchars($surveyData['system_kw'] ?? '') ?>"><br>

            <h4>Panel Boxes (JSON)</h4>
            <textarea name="panel_boxes" rows="4" cols="60"><?= htmlspecialchars(json_encode($surveyData['panel_boxes'] ?? [], JSON_PRETTY_PRINT)) ?></textarea><br>

            <h4>Inverter Details (JSON)</h4>
            <textarea name="inverter_details" rows="4" cols="60"><?= htmlspecialchars(json_encode($surveyData['inverter_details'] ?? [], JSON_PRETTY_PRINT)) ?></textarea><br>

            <h4>Battery Details (JSON)</h4>
            <textarea name="battery_details" rows="4" cols="60"><?= htmlspecialchars(json_encode($surveyData['battery_details'] ?? [], JSON_PRETTY_PRINT)) ?></textarea><br>

            <h4>AC Cables (JSON)</h4>
            <textarea name="ac_cables" rows="3" cols="60"><?= htmlspecialchars(json_encode($surveyData['ac_cables'] ?? [], JSON_PRETTY_PRINT)) ?></textarea><br>
            <h4>DC Cables (JSON)</h4>
            <textarea name="dc_cables" rows="3" cols="60"><?= htmlspecialchars(json_encode($surveyData['dc_cables'] ?? [], JSON_PRETTY_PRINT)) ?></textarea><br>
            <h4>Battery Cables (JSON)</h4>
            <textarea name="battery_cables" rows="3" cols="60"><?= htmlspecialchars(json_encode($surveyData['battery_cables'] ?? [], JSON_PRETTY_PRINT)) ?></textarea><br>

            <h4>Notes</h4>
            <textarea name="notes" rows="4" cols="60"><?= htmlspecialchars($surveyData['notes'] ?? '') ?></textarea><br><br>

            <button type="submit" name="update">Update Survey</button>
        </form>
    <?php endif; ?>

    <?php if ($response): ?>
        <h4>Response</h4>
        <pre><?= htmlspecialchars($response) ?></pre>
    <?php endif; ?>
</body>

</html>