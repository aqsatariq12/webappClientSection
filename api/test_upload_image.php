<?php
// api/test_upload_image.php
$response = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['survey_image'])) {
    $token = trim($_POST['token'] ?? '');
    $survey_id = trim($_POST['survey_id'] ?? '');
    $image_type = trim($_POST['image_type'] ?? '');
    $case = trim($_POST['case'] ?? ''); // form or extra
    $inverter_index = trim($_POST['inverter_index'] ?? '');

    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
         . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['REQUEST_URI']), '/\\') . '/images.php?action=upload';

    $cfile = new CURLFile($_FILES['survey_image']['tmp_name'], $_FILES['survey_image']['type'], $_FILES['survey_image']['name']);
    $post = [
        'survey_image'   => $cfile,
        'survey_id'      => $survey_id,
        'image_type'     => $image_type,
        'case'           => $case
    ];

    // Only send inverter_index if relevant
    if ($case === 'form' && $image_type === 'Inverter Pic' && $inverter_index !== '') {
        $post['inverter_index'] = $inverter_index;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    $response = curl_exec($ch);
    if (curl_errno($ch)) $response = 'Curl error: '.curl_error($ch);
    curl_close($ch);
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Upload Image (test)</title>
</head>
<body>
<h3>Upload Image (test)</h3>
<form method="post" enctype="multipart/form-data">
  Token:<br>
  <input type="text" name="token" style="width:500px" value="<?=htmlspecialchars($_POST['token'] ?? '')?>"><br>

  Survey ID:<br>
  <input type="text" name="survey_id" value="<?=htmlspecialchars($_POST['survey_id'] ?? '')?>"><br>

  Case:<br>
  <select name="case" id="case" onchange="updateTypes()">
    <option value="form" <?= (($_POST['case'] ?? '') === 'form') ? 'selected' : '' ?>>Main Form (Case 1)</option>
    <option value="extra" <?= (($_POST['case'] ?? '') === 'extra') ? 'selected' : '' ?>>Extra Upload (Case 2)</option>
  </select><br>

  Image Type:<br>
  <select name="image_type" id="image_type" onchange="toggleIndexField()"></select><br>

  <div id="inverterIndexField" style="display:none;">
      Inverter Index (starts from 0):<br>
      <input type="number" name="inverter_index" min="0" value="<?=htmlspecialchars($_POST['inverter_index'] ?? '')?>"><br>
  </div>

  File:<br>
  <input type="file" name="survey_image" required><br><br>

  <button>Upload</button>
</form>

<script>
function updateTypes() {
    let caseVal = document.getElementById('case').value;
    let sel = document.getElementById('image_type');
    sel.innerHTML = '';
    let opts = [];
    if (caseVal === 'form') {
        opts = ['Bill Pic','Panel Pic','Inverter Pic'];
    } else {
        opts = ['Bill Pic','Panel Pic','Inverter Pic','CNIC Front','CNIC Back','Other Attachment'];
    }
    let selected = "<?= htmlspecialchars($_POST['image_type'] ?? '') ?>";
    opts.forEach(t => {
        let o = document.createElement('option');
        o.value = t;
        o.textContent = t;
        if (t === selected) o.selected = true;
        sel.appendChild(o);
    });
    toggleIndexField();
}

function toggleIndexField() {
    let caseVal = document.getElementById('case').value;
    let typeVal = document.getElementById('image_type').value;
    let div = document.getElementById('inverterIndexField');
    if (caseVal === 'form' && typeVal === 'Inverter Pic') {
        div.style.display = 'block';
    } else {
        div.style.display = 'none';
    }
}

updateTypes();
</script>

<?php if ($response !== null): ?>
  <h4>Response</h4>
  <pre><?= htmlspecialchars($response) ?></pre>
<?php endif; ?>
</body>
</html>
