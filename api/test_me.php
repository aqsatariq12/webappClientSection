<?php
// api/test_me.php
$response = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
           . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['REQUEST_URI']), '/\\') . '/auth_api.php?action=me';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Accept: application/json"]);
    $response = curl_exec($ch);
    if (curl_errno($ch)) { $response = 'Curl error: '.curl_error($ch); }
    curl_close($ch);
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>API Me Test</title></head>
<body>
  <h3>Check Current User (API: me)</h3>
  <form method="post">
    <label>Token:</label><br>
    <input type="text" name="token" style="width:600px" value="<?= htmlspecialchars($_POST['token'] ?? '') ?>"><br><br>
    <button type="submit">Check User</button>
  </form>

  <?php if ($response !== null): ?>
    <h4>Response</h4>
    <pre><?= htmlspecialchars($response) ?></pre>
  <?php endif; ?>
</body>
</html>
