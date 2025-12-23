<?php
// api/test_view_survey.php
$response = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = trim($_POST['token'] ?? '');
  $id = intval($_POST['id'] ?? 0);
  $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
       . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['REQUEST_URI']), '/\\') . '/surveys.php?action=view&id=' . $id;

  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Accept: application/json"]);
  $response = curl_exec($ch);
  if (curl_errno($ch)) $response = 'Curl error: '.curl_error($ch);
  curl_close($ch);
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Test View Survey</title></head><body>
<form method="post">
  Token:<br><input style="width:600px" name="token" value="<?=htmlspecialchars($_POST['token'] ?? '')?>"><br>
  Survey ID:<br><input name="id" value="<?=htmlspecialchars($_POST['id'] ?? '')?>"><br><br>
  <button>View Survey</button>
</form>
<?php if ($response !== null): ?>
  <h4>Response</h4>
  <pre><?= htmlspecialchars($response) ?></pre>
<?php endif; ?>
</body></html>
