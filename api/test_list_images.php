<?php
// api/test_list_images.php
$response = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    $survey_id = intval($_POST['survey_id'] ?? 0);

    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
         . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['REQUEST_URI']), '/\\') . '/images.php?action=list&survey_id=' . $survey_id;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    $response = curl_exec($ch);
    if (curl_errno($ch)) $response = 'Curl error: '.curl_error($ch);
    curl_close($ch);
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>List Images</title></head><body>
<form method="post">
  Token:<br><input style="width:600px" name="token" value="<?=htmlspecialchars($_POST['token'] ?? '')?>"><br>
  Survey ID:<br><input name="survey_id" value="<?=htmlspecialchars($_POST['survey_id'] ?? '')?>"><br><br>
  <button>List Images</button>
</form>

<?php if ($response !== null): ?>
  <h4>Response</h4>
  <pre><?= htmlspecialchars($response) ?></pre>
<?php endif; ?>
</body></html>
