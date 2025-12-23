<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

function uploadFile($field, $prefix = '') {
    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === 0) {
        $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
        $filename = $prefix . uniqid() . '.' . $ext;
        $dest = '../uploads/' . $filename;
        move_uploaded_file($_FILES[$field]['tmp_name'], $dest);
        return $filename;
    }
    return null;
}

/**
 * Build cable rows for a given prefix (ac|dc|battery) as an ARRAY.
 * We’ll combine them into one JSON later for cables_details.
 */
function buildCableRows($prefix) {
    $count = (int)($_POST["{$prefix}_cable_count"] ?? 0);
    $rows = [];
    for ($i = 1; $i <= $count; $i++) {
        $row = [
            'name'        => clean($_POST["{$prefix}_cable_{$i}_name"] ?? ''),
            'name_custom' => clean($_POST["{$prefix}_cable_{$i}_name_custom"] ?? ''),
            'core'        => clean($_POST["{$prefix}_cable_{$i}_core"] ?? ''),
            'core_custom' => clean($_POST["{$prefix}_cable_{$i}_core_custom"] ?? ''),
            'mm'          => clean($_POST["{$prefix}_cable_{$i}_mm"] ?? ''),
            'mm_custom'   => clean($_POST["{$prefix}_cable_{$i}_mm_custom"] ?? ''),
            'feet'        => clean($_POST["{$prefix}_cable_{$i}_feet"] ?? ''),
            'feet_custom' => clean($_POST["{$prefix}_cable_{$i}_feet_custom"] ?? ''),
            'length'      => clean($_POST["{$prefix}_cable_{$i}_length"] ?? '')
        ];
        // keep only non-empty rows
        if (array_filter($row, fn($v) => $v !== '' && $v !== null)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

/** Inverters -> JSON */
function buildInverterJson() {
    // We'll detect inverter inputs by reading inverter_* fields in POST.
    $data = [];

    // If you know maximum possible, you could loop to some large N.
    // Here we detect by incrementing until no fields are found for that index.
    $i = 1;
    while (true) {
        // decide a key that must exist for this inverter if present:
        $kwKey = "inverter_{$i}_kw";
        $idKey = "inverter_{$i}_id";

        // Break when we don't find the main required field (adjust as needed)
        if (!isset($_POST[$kwKey]) && !isset($_POST[$idKey])) {
            break;
        }

        $box_count = (int)($_POST["inverter_{$i}_box_count"] ?? 0);
        $boxes = [];
        for ($j = 1; $j <= $box_count; $j++) {
            $key = "inv{$i}_box_{$j}";
            if (!empty($_POST[$key])) $boxes[] = clean($_POST[$key]);
        }

        $row = [
            'kw'           => clean($_POST["inverter_{$i}_kw"] ?? ''),
            'manufacturer' => clean($_POST["inverter_{$i}_manufacturer"] ?? ''),
            'model'        => clean($_POST["inverter_{$i}_model"] ?? ''),
            'id'           => clean($_POST["inverter_{$i}_id"] ?? ''),
            'password'     => clean($_POST["inverter_{$i}_password"] ?? ''),
            'panel_count'  => clean($_POST["inverter_{$i}_panel_count"] ?? ''),
            'box_count'    => $box_count,
            'boxes'        => $boxes,
            'pic'          => uploadFile("inverter_{$i}_pic", "inv{$i}_")
        ];

        if (array_filter($row, fn($v) => $v !== '' && $v !== null && $v !== [])) {
            $data[] = $row;
        }

        $i++;
    }

    // Now count actual inverters found
    $count = count($data);

    // Include global type & phase in JSON
    $result = [
        'type'      => clean($_POST['inverter_type'] ?? ''),
        'phase'     => clean($_POST['inverter_phase'] ?? ''),
        'count'     => $count,
        'inverters' => $data
    ];

    return json_encode($result);
}


/** Batteries -> JSON */
function buildBatteryJson() {
    $count = (int)($_POST['battery_count'] ?? 0);
    $data = [];
    for ($i = 1; $i <= $count; $i++) {
        $row = [
            'manufacturer' => clean($_POST["battery_{$i}_manufacturer"] ?? ''),
            'model'        => clean($_POST["battery_{$i}_model"] ?? ''),
            'type'         => clean($_POST["battery_{$i}_type"] ?? ''),
            'serial'       => clean($_POST["battery_{$i}_serial"] ?? ''),
            'volt'         => clean($_POST["battery_{$i}_volt"] ?? ''),
            'amp'          => clean($_POST["battery_{$i}_amp"] ?? ''),
            'cell'         => clean($_POST["battery_{$i}_cell"] ?? '')
        ];
        if (array_filter($row, fn($v) => $v !== '' && $v !== null)) {
            $data[] = $row;
        }
    }
    return json_encode($data);
}

/** Optional: Panels -> JSON (keeps your existing columns too) */
function buildPanelJson($panel_boxes, $panel_pic) {
    return json_encode([
        'model_no'    => clean($_POST['panel_model_no'] ?? ''),
        'type'        => clean($_POST['panel_type'] ?? ''),
        'manufacturer'=> clean($_POST['panel_manufacturer'] ?? ''),
        'power'       => clean($_POST['panel_power'] ?? ''),
        'count'       => (int)($_POST['panel_count'] ?? 0),
        'box_count'   => (int)($_POST['panel_box_count'] ?? 0),
        'boxes'       => $panel_boxes,
        'pic'         => $panel_pic
    ]);
}

/** ---------- Build panel boxes ---------- */
$panel_box_count = (int) ($_POST['panel_box_count'] ?? 0);
$panel_boxes = [];
for ($i = 1; $i <= $panel_box_count; $i++) {
    $key = 'panel_box_' . $i;
    if (!empty($_POST[$key])) $panel_boxes[] = clean($_POST[$key]);
}

/** ---------- File uploads ---------- */
$bill_pic  = uploadFile('bill_pic', 'bill_');
$panel_pic = uploadFile('panel_pic', 'panel_');

/** ---------- Other equipment flags ---------- */
$checkboxes = ['light_arrester', 'smart_controller', 'zero_export', 'light_earthing', 'delta_hub', 'ac_earthing', 'dc_earthing'];
$flags = [];
foreach ($checkboxes as $key) $flags[$key] = isset($_POST[$key]) ? 1 : 0;

/** ---------- Combined cables JSON ---------- */
$cables_details_json = json_encode([
    'ac'      => buildCableRows('ac'),
    'dc'      => buildCableRows('dc'),
    'battery' => buildCableRows('battery'),
]);

/** ---------- Insert (named params = less error-prone) ---------- */
$sql = "
INSERT INTO surveys (
  esa_serial, token, client_id, user_id, created_at,
  system_kw, connection_type, service_type, bill_no, bill_pic, sanction_load,
  panel_model_no, panel_type, panel_manufacturer, panel_power, panel_count, panel_box_count, panel_boxes, panel_pic, panel_details,
  inverter_count, inverter_details,
  battery_installed, battery_count, battery_details,
  cables_details, ac_cables, dc_cables, battery_cables,
  light_arrester, smart_controller, zero_export, light_earthing, delta_hub, ac_earthing, dc_earthing,
  net_metering_progress, notes
) VALUES (
  :esa_serial, :token, :client_id, :user_id, NOW(),
  :system_kw, :connection_type, :service_type, :bill_no, :bill_pic, :sanction_load,
  :panel_model_no, :panel_type, :panel_manufacturer, :panel_power, :panel_count, :panel_box_count, :panel_boxes, :panel_pic, :panel_details,
  :inverter_count, :inverter_details,
  :battery_installed, :battery_count, :battery_details,
  :cables_details,:ac_cables, :dc_cables, :battery_cables,
  :light_arrester, :smart_controller, :zero_export, :light_earthing, :delta_hub, :ac_earthing, :dc_earthing,
  :net_metering_progress, :notes
)";
$stmt = $pdo->prepare($sql);

$params = [
  ':esa_serial' => clean($_POST['esa_serial'] ?? ''),
  ':token' => bin2hex(random_bytes(32)),
  ':client_id' => clean($_GET['client_id'] ?? ($_POST['client_id'] ?? null)),
  ':user_id' => $_SESSION['user_id'],

  ':system_kw' => clean($_POST['system_kw'] ?? ''),
  ':connection_type' => clean($_POST['connection_type'] ?? ''),
  ':service_type' => clean($_POST['service_type'] ?? ''),
  ':bill_no' => clean($_POST['bill_no'] ?? ''),
  ':bill_pic' => $bill_pic,
  ':sanction_load' => clean($_POST['sanction_load'] ?? ''),

  ':panel_model_no' => clean($_POST['panel_model_no'] ?? ''),
  ':panel_type' => clean($_POST['panel_type'] ?? ''),
  ':panel_manufacturer' => clean($_POST['panel_manufacturer'] ?? ''),
  ':panel_power' => clean($_POST['panel_power'] ?? ''),
  ':panel_count' => (int)($_POST['panel_count'] ?? 0),
  ':panel_box_count' => $panel_box_count,
  ':panel_boxes' => json_encode($panel_boxes),
  ':panel_pic' => $panel_pic,
  ':panel_details' => buildPanelJson($panel_boxes, $panel_pic),

  ':inverter_count' => (int)($_POST['inverter_count'] ?? 0),
  ':inverter_details' => buildInverterJson(),

  ':battery_installed' => isset($_POST['battery_installed']) ? 1 : 0,
  ':battery_count' => (int)($_POST['battery_count'] ?? 0),
  ':battery_details' => buildBatteryJson(),

  ':cables_details' => $cables_details_json,
  ':ac_cables'      => json_encode(buildCableRows('ac')),
  ':dc_cables'      => json_encode(buildCableRows('dc')),
  ':battery_cables' => json_encode(buildCableRows('battery')),

  ':light_arrester' => $flags['light_arrester'],
  ':smart_controller' => $flags['smart_controller'],
  ':zero_export' => $flags['zero_export'],
  ':light_earthing' => $flags['light_earthing'],
  ':delta_hub' => $flags['delta_hub'],
  ':ac_earthing' => $flags['ac_earthing'],
  ':dc_earthing' => $flags['dc_earthing'],

  ':net_metering_progress' => ($_POST['net_metering_progress'] !== '' ? (int)$_POST['net_metering_progress'] : null),
  ':notes' => clean($_POST['notes'] ?? ''),
];

$stmt->execute($params);

$survey_id = $pdo->lastInsertId();
header("Location: upload_images.php?survey_id={$survey_id}");
exit;
