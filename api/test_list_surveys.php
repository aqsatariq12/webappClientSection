<?php
// api/test_list_surveys.php
$response = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    $page = intval($_POST['page'] ?? 1);
    $per = intval($_POST['per'] ?? 25);

    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
         . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['REQUEST_URI']), '/\\') . '/surveys.php?action=list&page=' . $page . '&per=' . $per;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Accept: application/json"]);
    $response = curl_exec($ch);
    if (curl_errno($ch)) $response = 'Curl error: '.curl_error($ch);
    curl_close($ch);
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>List Surveys</title></head><body>
  <h3>List My Surveys</h3>
  <form method="post">
    <label>Token:</label><br>
    <input type="text" name="token" style="width:600px" value="<?= htmlspecialchars($_POST['token'] ?? '') ?>"><br><br>

    <label>Page:</label> <input type="text" name="page" value="<?= htmlspecialchars($_POST['page'] ?? '1') ?>">
    <label>Per:</label> <input type="text" name="per" value="<?= htmlspecialchars($_POST['per'] ?? '25') ?>"><br><br>

    <button type="submit">List</button>
  </form>

  <?php if ($response !== null): ?>
    <h4>Response</h4>
    <pre><?= htmlspecialchars($response) ?></pre>
  <?php endif; ?>
</body></html>
