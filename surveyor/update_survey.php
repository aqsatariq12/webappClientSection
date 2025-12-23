<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

if ($_SESSION['role'] !== 'surveyor') {
    header("Location: ../index.php");
    exit;
}

function uploadFile($field, $existing = null) {
    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === 0) {
        $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
        $newName = uniqid($field . '_') . '.' . $ext;
        move_uploaded_file($_FILES[$field]['tmp_name'], '../uploads/' . $newName);
        return $newName;
    }
    return $existing;
}

function jsonCable($prefix) {
    $result = [];
    if (!isset($_POST[$prefix . '_cables']) || !is_array($_POST[$prefix . '_cables'])) return json_encode([]);
    foreach ($_POST[$prefix . '_cables'] as $cable) {
        $row = [
            'name'        => clean($cable['name'] ?? ''),
            'name_custom' => clean($cable['name_custom'] ?? ''),
            'core'        => clean($cable['core'] ?? ''),
            'core_custom' => clean($cable['core_custom'] ?? ''),
            'mm'          => clean($cable['mm'] ?? ''),
            'feet'        => clean($cable['feet'] ?? ''),
            'feet_custom' => clean($cable['feet_custom'] ?? ''),
            'length'      => clean($cable['length'] ?? '')
        ];
        if (array_filter($row)) $result[] = $row;
    }
    return json_encode($result, JSON_UNESCAPED_UNICODE);
}

function jsonBoxes($prefix) {
    $result = [];
    foreach ($_POST as $key => $val) {
        if (strpos($key, "{$prefix}_box_") === 0) {
            $index = substr($key, strlen($prefix . '_box_'));
            if (!ctype_digit((string)$index)) continue;
            $result[(int)$index] = clean($val);
        }
    }
    if (empty($result)) return json_encode([], JSON_UNESCAPED_UNICODE);
    ksort($result, SORT_NUMERIC);
    return json_encode(array_values($result), JSON_UNESCAPED_UNICODE);
}

$survey_id = clean($_GET['id']);

// Fetch old survey
$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->execute([$survey_id]);
$old = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$old) die("Survey not found");

// Panel boxes & picture
$panel_box_count = (int) ($_POST['panel_box_count'] ?? $old['panel_box_count']);
$panel_boxes = [];
for ($i = 1; $i <= $panel_box_count; $i++) {
    $key = 'panel_box_' . $i;
    if (!empty($_POST[$key])) $panel_boxes[] = clean($_POST[$key]);
}
$panel_pic = uploadFile('panel_pic', $old['panel_pic']);
$panelBoxesJson = jsonBoxes('panel');

// Panel details JSON
$panel_details_json = json_encode([
    'model_no'     => clean($_POST['panel_model_no']),
    'type'         => clean($_POST['panel_type']),
    'manufacturer' => clean($_POST['panel_manufacturer']),
    'power'        => clean($_POST['panel_power']),
    'count'        => clean($_POST['panel_count']),
    'box_count'    => $panel_box_count,
    'boxes'        => json_decode($panelBoxesJson, true),
    'pic'          => $panel_pic
], JSON_UNESCAPED_UNICODE);

// Checkbox handling
$checkboxes = ['light_arrester','smart_controller','zero_export','light_earthing','delta_hub','ac_earthing','dc_earthing'];
foreach ($checkboxes as $cb) $_POST[$cb] = isset($_POST[$cb]) ? 1 : 0;

// Inverters
$inverterDetails = $_POST['inverter_details'] ?? ['inverters' => []];
if (!isset($inverterDetails['inverters']) || !is_array($inverterDetails['inverters'])) $inverterDetails['inverters'] = [];
$inverterJson = json_encode($inverterDetails, JSON_UNESCAPED_UNICODE);

// Batteries
$batteryData = $_POST['batteries'] ?? [];
$batteryJson = json_encode($batteryData, JSON_UNESCAPED_UNICODE);
$battery_installed = isset($_POST['battery_installed']) ? 1 : 0;

// Update survey
$update = $pdo->prepare("UPDATE surveys SET
    esa_serial=?, user_id=?, system_kw=?, connection_type=?, service_type=?, bill_no=?, bill_pic=?, sanction_load=?,
    panel_model_no=?, panel_type=?, panel_manufacturer=?, panel_power=?, panel_count=?, panel_box_count=?, panel_boxes=?, panel_pic=?, panel_details=?,
    inverter_details=?, inverter_count=?,
    battery_details=?, battery_installed=?,
    ac_cables=?, dc_cables=?, battery_cables=?,
    light_arrester=?, smart_controller=?, zero_export=?, light_earthing=?, delta_hub=?, ac_earthing=?, dc_earthing=?,
    net_metering_progress=?, notes=?
WHERE id=?");

$update->execute([
    clean($_POST['esa_serial']),
    clean($_POST['user_id']),
    clean($_POST['system_kw']),
    clean($_POST['connection_type']),
    clean($_POST['service_type']),
    clean($_POST['bill_no']),
    uploadFile('bill_pic', $old['bill_pic']),
    clean($_POST['sanction_load']),

    clean($_POST['panel_model_no']),
    clean($_POST['panel_type']),
    clean($_POST['panel_manufacturer']),
    clean($_POST['panel_power']),
    clean($_POST['panel_count']),
    $panel_box_count,
    $panelBoxesJson,
    $panel_pic,
    $panel_details_json,

    $inverterJson,
    count($inverterDetails['inverters']),

    $batteryJson,
    $battery_installed,

    jsonCable('ac'),
    jsonCable('dc'),
    jsonCable('battery'),

    $_POST['light_arrester'],
    $_POST['smart_controller'],
    $_POST['zero_export'],
    $_POST['light_earthing'],
    $_POST['delta_hub'],
    $_POST['ac_earthing'],
    $_POST['dc_earthing'],

    (int)$_POST['net_metering_progress'],
    clean($_POST['notes']),
    $survey_id
]);

header("Location: edit_survey.php?id=$survey_id&updated=1");
exit;
?>
