<?php
// api/test_delete_image.php
$response = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    $payload = [];

    if (!empty($_POST['image_id'])) $payload['image_id'] = intval($_POST['image_id']);
    if (!empty($_POST['survey_id'])) $payload['survey_id'] = intval($_POST['survey_id']);
    if (!empty($_POST['field'])) $payload['field'] = $_POST['field'];
    if (isset($_POST['inverter_index']) && $_POST['inverter_index'] !== '') $payload['inverter_index'] = intval($_POST['inverter_index']);
    if (!empty($_POST['inverter_id'])) $payload['inverter_id'] = $_POST['inverter_id'];

    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
         . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['REQUEST_URI']), '/\\') . '/images.php?action=delete';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);
    if (curl_errno($ch)) $response = 'Curl error: '.curl_error($ch);
    curl_close($ch);
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Delete Image</title></head><body>
<form method="post">
  Token:<br><input style="width:600px" name="token" value="<?=htmlspecialchars($_POST['token'] ?? '')?>"><br>
  Image ID (survey_images.id) to delete (optional):<br><input name="image_id" value="<?=htmlspecialchars($_POST['image_id'] ?? '')?>"><br>
  OR Survey ID (for field/inverter delete):<br><input name="survey_id" value="<?=htmlspecialchars($_POST['survey_id'] ?? '')?>"><br>
  Field to clear (bill_pic or panel_pic):<br><input name="field" value="<?=htmlspecialchars($_POST['field'] ?? '')?>"><br>
  Inverter Index (optional):<br><input name="inverter_index" value="<?=htmlspecialchars($_POST['inverter_index'] ?? '')?>"><br>
  Inverter ID (optional):<br><input name="inverter_id" value="<?=htmlspecialchars($_POST['inverter_id'] ?? '')?>"><br><br>
  <button>Delete</button>
</form>

<?php if ($response !== null): ?>
  <h4>Response</h4><pre><?=htmlspecialchars($response)?></pre>
<?php endif; ?>
</body></html>
