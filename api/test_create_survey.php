<?php
// api/test_create_survey.php
// Single page: 1) ask token+client_id (GET) -> 2) show full create form with ESA details (POST sends JSON)
session_start();
require_once __DIR__ . '/../includes/db.php'; // needed to generate ESA serial using DB and to lookup surveyor
$response = null;

// Try to get surveyor info from session (if logged in)
$surveyor_name = '';
$surveyor_id = null;
if (!empty($_SESSION['user_id'])) {
  $surveyor_id = (int) $_SESSION['user_id'];
  try {
    $sstmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $sstmt->execute([$surveyor_id]);
    $surveyor_name = $sstmt->fetchColumn() ?: '';
  } catch (Exception $e) {
    // ignore DB error; leave surveyor blank
    $surveyor_name = '';
    $surveyor_id = null;
  }
}

// Accept token+client_id from the initial small form via GET so page can show full form
$initial_token = trim($_GET['token'] ?? '');
$initial_client_id = trim($_GET['client_id'] ?? '');

// If user submitted the full create form, perform the curl to surveys.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_create'])) {
  $token = trim($_POST['token'] ?? '');

  $payload = [
    'esa_serial'       => $_POST['esa_serial'] ?? '',
    'client_id'        => $_POST['client_id'] ?? '',
    'user_id'          => $_POST['user_id'] ?? '',          // include user_id if provided
    'connection_type'  => $_POST['connection_type'] ?? '',
    'service_type'     => $_POST['service_type'] ?? '',
    'bill_no'          => $_POST['bill_no'] ?? '',
    'bill_pic'         => $_POST['bill_pic'] ?? '',
    'sanction_load'    => $_POST['sanction_load'] ?? '',
    'system_kw'        => $_POST['system_kw'] ?? '',
    'notes'            => $_POST['notes'] ?? '',

    // panel
    'panel_model_no'     => $_POST['panel_model_no'] ?? '',
    'panel_type'         => $_POST['panel_type'] ?? '',
    'panel_manufacturer' => $_POST['panel_manufacturer'] ?? '',
    'panel_power'        => $_POST['panel_power'] ?? '',
    'panel_count'        => $_POST['panel_count'] ?? '',
    'panel_box_count'    => $_POST['panel_box_count'] ?? '',
    'panel_boxes'        => json_decode($_POST['panel_boxes'] ?? '[]', true),
    'panel_pic'          => $_POST['panel_pic'] ?? '',

    // inverter
    'inverter_count'  => $_POST['inverter_count'] ?? '',
    'inverter_details' => json_decode($_POST['inverter_details'] ?? '[]', true),

    // battery
    'battery_installed' => isset($_POST['battery_installed']) ? 1 : 0,
    'battery_count'     => $_POST['battery_count'] ?? 0,
    'battery_details'   => json_decode($_POST['battery_details'] ?? '[]', true),

    // cables
    'ac_cables'      => json_decode($_POST['ac_cables'] ?? '[]', true),
    'dc_cables'      => json_decode($_POST['dc_cables'] ?? '[]', true),
    'battery_cables' => json_decode($_POST['battery_cables'] ?? '[]', true),

    // other equipment
    'light_arrester'   => isset($_POST['light_arrester']) ? 1 : 0,
    'smart_controller' => isset($_POST['smart_controller']) ? 1 : 0,
    'zero_export'      => isset($_POST['zero_export']) ? 1 : 0,
    'light_earthing'   => isset($_POST['light_earthing']) ? 1 : 0,
    'delta_hub'        => isset($_POST['delta_hub']) ? 1 : 0,
    'ac_earthing'      => isset($_POST['ac_earthing']) ? 1 : 0,
    'dc_earthing'      => isset($_POST['dc_earthing']) ? 1 : 0,

    'net_metering_status'   => $_POST['net_metering_status'] ?? '',
    'net_metering_progress' => $_POST['net_metering_progress'] ?? '',

    // date
    'date' => $_POST['date'] ?? date('Y-m-d')
  ];

  $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['REQUEST_URI']), '/\\') . '/surveys.php?action=create';

  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Content-Type: application/json"]);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
  $response = curl_exec($ch);
  if (curl_errno($ch)) $response = 'Curl error: ' . curl_error($ch);
  curl_close($ch);
}

// Helper: generate ESA serial preview using DB max(id)+1 logic
function generate_esa_preview($pdo) {
  try {
    $stmt = $pdo->query("SELECT IFNULL(MAX(id), 0) as last_id FROM surveys");
    $last_id = (int)$stmt->fetchColumn();
    $next = $last_id + 1;
    return "SURV-" . str_pad($next, 3, '0', STR_PAD_LEFT) . "/" . date('m') . "/" . date('Y');
  } catch (Exception $e) {
    // fallback if something goes wrong (e.g. no DB connection)
    return "SURV-###/" . date('m') . "/" . date('Y');
  }
}

// Decide whether to show the small initial form or the full create form
$show_full_form = false;
$client_id_for_form = '';

// prefer GET client_id (from initial small form), but also allow prefill from POST if user navigated back
if (!empty($initial_client_id)) {
  $show_full_form = true;
  $client_id_for_form = $initial_client_id;
} elseif (!empty($_POST['client_id'])) {
  $show_full_form = true;
  $client_id_for_form = $_POST['client_id'];
}
// If session provided a surveyor_id but client didn't fill initial GET token, allow token from session too
$token_for_form = $initial_token ?: ($_POST['token'] ?? '');
$esa_preview = $show_full_form ? generate_esa_preview($pdo) : '';

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Create Survey Test</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 18px; max-width: 980px; }
    .card { border: 1px solid #ddd; padding: 12px; margin-bottom: 14px; border-radius: 6px; }
    label { display:block; margin-top:8px; }
    input[type=text], textarea { width: 100%; max-width: 800px; padding:6px; }
  </style>
</head>
<body>
<?php if (! $show_full_form): ?>
  <div class="card">
    <h3>Enter Token & Client ID</h3>
    <form method="get">
      <label>Token (API user token):</label>
      <input type="text" name="token" value="<?= htmlspecialchars($initial_token) ?>">
      <label>Client ID:</label>
      <input type="text" name="client_id" value="<?= htmlspecialchars($initial_client_id) ?>">
      <div style="margin-top:10px;">
        <button type="submit">Open Create Survey Form</button>
      </div>
    </form>
    <p style="color:#666;margin-top:10px">This step pre-fills client_id and token for the full test form.</p>
  </div>
<?php else: ?>
  <div class="card">
    <h3>ESA Details (preview)</h3>
    <form method="post">
      <!-- preserve token & client_id for the real POST -->
      <label>Token (Authorization):</label>
      <input type="text" name="token" value="<?= htmlspecialchars($token_for_form) ?>">

      <label>Client ID:</label>
      <input type="text" name="client_id" value="<?= htmlspecialchars($client_id_for_form) ?>">
      <input type="hidden" name="client_id" value="<?= htmlspecialchars($client_id_for_form) ?>">

      <label>ESA Serial (preview — server will re-generate if left blank):</label>
      <input type="text" name="esa_serial" value="<?= htmlspecialchars($esa_preview) ?>">

      <label>Date (YYYY-MM-DD):</label>
      <input type="text" name="date" value="<?= htmlspecialchars(date('Y-m-d')) ?>">

      <!-- Surveyor: if we have session user info show readonly + hidden user_id; otherwise show editable field -->
      <?php if (!empty($surveyor_name) && !empty($surveyor_id)): ?>
        <label>Surveyor:</label>
        <input type="text" name="surveyor_name_display" value="<?= htmlspecialchars($surveyor_name) ?>" readonly>
        <input type="hidden" name="user_id" value="<?= (int)$surveyor_id ?>">
      <?php else: ?>
        <label>Surveyor (type name):</label>
        <input type="text" name="surveyor_name_display" value="<?= htmlspecialchars($_POST['surveyor_name_display'] ?? '') ?>">
        <!-- leave user_id empty so API stores only name if you later process it -->
        <input type="hidden" name="user_id" value="">
      <?php endif; ?>

      <p style="color:#666;margin-top:8px">
        If you leave ESA Serial empty the actual API will generate and return it.
      </p>
      <hr>
      <!-- The rest of the form (system, panels, etc.) continues below -->
      <h4>System & Bill Info</h4>
      <label>Connection Type:
        <input type="text" name="connection_type" value="<?= htmlspecialchars($_POST['connection_type'] ?? '') ?>">
      </label>
      <label>Service Type:
        <input type="text" name="service_type" value="<?= htmlspecialchars($_POST['service_type'] ?? '') ?>">
      </label>
      <label>Bill No:
        <input type="text" name="bill_no" value="<?= htmlspecialchars($_POST['bill_no'] ?? '') ?>">
      </label>
      <label>Bill Picture (filename):
        <input type="text" name="bill_pic" value="<?= htmlspecialchars($_POST['bill_pic'] ?? '') ?>">
      </label>
      <label>Sanction Load:
        <input type="text" name="sanction_load" value="<?= htmlspecialchars($_POST['sanction_load'] ?? '') ?>">
      </label>
      <label>System KW:
        <input type="text" name="system_kw" value="<?= htmlspecialchars($_POST['system_kw'] ?? '') ?>">
      </label>

      <h4>Solar Panel Details</h4>
      <label>Model No: <input type="text" name="panel_model_no" value="<?= htmlspecialchars($_POST['panel_model_no'] ?? '') ?>"></label>
      <label>Type: <input type="text" name="panel_type" value="<?= htmlspecialchars($_POST['panel_type'] ?? '') ?>"></label>
      <label>Manufacturer: <input type="text" name="panel_manufacturer" value="<?= htmlspecialchars($_POST['panel_manufacturer'] ?? '') ?>"></label>
      <label>Power (KW): <input type="text" id="panel_power" name="panel_power" value="<?= htmlspecialchars($_POST['panel_power'] ?? '') ?>"></label>
      <label>No. of Panels: <input type="text" id="panel_count" name="panel_count" value="<?= htmlspecialchars($_POST['panel_count'] ?? '') ?>"></label>
      <label>Total PV (KW): <input type="text" id="total_pv" readonly></label>
      <label>No. of Boxes: <input type="text" name="panel_box_count" value="<?= htmlspecialchars($_POST['panel_box_count'] ?? '') ?>"></label>
      <label>Box Values (JSON): <textarea name="panel_boxes"><?= htmlspecialchars($_POST['panel_boxes'] ?? '[]') ?></textarea></label>
      <label>Panel Picture (filename): <input type="text" name="panel_pic" value="<?= htmlspecialchars($_POST['panel_pic'] ?? '') ?>"></label>

      <h4>Inverter Details</h4>
      <label>No. of Inverters: <input type="text" name="inverter_count" value="<?= htmlspecialchars($_POST['inverter_count'] ?? '') ?>"></label>
      <label>Inverter Details (JSON): <textarea name="inverter_details"><?= htmlspecialchars($_POST['inverter_details'] ?? '[]') ?></textarea></label>

      <h4>Battery Details</h4>
      <label>Installed? <input type="checkbox" name="battery_installed" <?= isset($_POST['battery_installed']) ? 'checked' : '' ?>></label>
      <label>No. of Batteries: <input type="text" name="battery_count" value="<?= htmlspecialchars($_POST['battery_count'] ?? '') ?>"></label>
      <label>Battery Details (JSON): <textarea name="battery_details"><?= htmlspecialchars($_POST['battery_details'] ?? '[]') ?></textarea></label>

      <h4>Cable Details</h4>
      <label>AC Cables (JSON): <textarea name="ac_cables"><?= htmlspecialchars($_POST['ac_cables'] ?? '[]') ?></textarea></label>
      <label>DC Cables (JSON): <textarea name="dc_cables"><?= htmlspecialchars($_POST['dc_cables'] ?? '[]') ?></textarea></label>
      <label>Battery Cables (JSON): <textarea name="battery_cables"><?= htmlspecialchars($_POST['battery_cables'] ?? '[]') ?></textarea></label>

      <h4>Other Equipment</h4>
      <label><input type="checkbox" name="light_arrester" <?= isset($_POST['light_arrester']) ? 'checked' : '' ?>> Light Arrester</label>
      <label><input type="checkbox" name="smart_controller" <?= isset($_POST['smart_controller']) ? 'checked' : '' ?>> Smart Controller</label>
      <label><input type="checkbox" name="zero_export" <?= isset($_POST['zero_export']) ? 'checked' : '' ?>> Zero Export</label>
      <label><input type="checkbox" name="light_earthing" <?= isset($_POST['light_earthing']) ? 'checked' : '' ?>> Light Earthing</label>
      <label><input type="checkbox" name="delta_hub" <?= isset($_POST['delta_hub']) ? 'checked' : '' ?>> Delta Hub</label>
      <label><input type="checkbox" name="ac_earthing" <?= isset($_POST['ac_earthing']) ? 'checked' : '' ?>> AC Earthing</label>
      <label><input type="checkbox" name="dc_earthing" <?= isset($_POST['dc_earthing']) ? 'checked' : '' ?>> DC Earthing</label>

      <h4>Net Metering</h4>
      <label>Status: <input type="text" name="net_metering_status" value="<?= htmlspecialchars($_POST['net_metering_status'] ?? '') ?>"></label>
      <label>Progress (JSON or step): <input type="text" name="net_metering_progress" value="<?= htmlspecialchars($_POST['net_metering_progress'] ?? '') ?>"></label>

      <h4>Notes</h4>
      <label><textarea name="notes" rows="4"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea></label>

      <input type="hidden" name="do_create" value="1">
      <div style="margin-top:12px;">
        <button type="submit">Create Survey (call API)</button>
      </div>
    </form>
  </div>
<?php endif; ?>

<?php if ($response !== null): ?>
  <div class="card">
    <h4>API Response</h4>
    <pre><?= htmlspecialchars($response) ?></pre>
  </div>
<?php endif; ?>

<script>
  function updateTotalPV() {
    const pw = parseFloat(document.getElementById('panel_power')?.value || 0);
    const ct = parseFloat(document.getElementById('panel_count')?.value || 0);
    const el = document.getElementById('total_pv');
    if (el) el.value = (pw * ct) || '';
  }
  const pPower = document.getElementById('panel_power');
  const pCount = document.getElementById('panel_count');
  if (pPower) pPower.addEventListener('input', updateTotalPV);
  if (pCount) pCount.addEventListener('input', updateTotalPV);
  updateTotalPV();
</script>
</body>
</html>
