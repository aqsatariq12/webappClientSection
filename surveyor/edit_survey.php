<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

if ($_SESSION['role'] !== 'surveyor') {
    header("Location: ../index.php");
    exit;
}

$survey_id = isset($_GET['id']) ? clean($_GET['id']) : null;
if (!$survey_id) {
    die("Survey ID missing.");
}

// $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt = $pdo->prepare("
    SELECT 
        s.*,
        c.name AS client_name,
        c.cnic,
        c.contact_no_1,
        c.address
    FROM surveys s
    JOIN clients c ON s.client_id = c.id
    WHERE s.id = ?
");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$survey)
    die("Survey not found.");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- INVERTERS (keep your existing logic) ---
    $postedInv = $_POST['inverter_details'] ?? ['inverters' => []];
    if (!isset($postedInv['inverters']) || !is_array($postedInv['inverters'])) {
        $postedInv['inverters'] = [];
    }

    if (!empty($_FILES['inverter_pics']) && is_array($_FILES['inverter_pics']['tmp_name'])) {
        foreach ($_FILES['inverter_pics']['tmp_name'] as $idx => $tmpName) {
            if (empty($tmpName))
                continue;
            $origName = $_FILES['inverter_pics']['name'][$idx];
            $ext = pathinfo($origName, PATHINFO_EXTENSION);
            $newName = time() . "_inv_{$survey_id}_{$idx}." . $ext;
            $dest = __DIR__ . '/../uploads/' . $newName;
            if (move_uploaded_file($tmpName, $dest)) {
                $postedInv['inverters'][$idx]['pic'] = $newName;
            }
        }
    }

    // Save inverter JSON
    $invJson = json_encode($postedInv, JSON_UNESCAPED_UNICODE);
    $stmt = $pdo->prepare("UPDATE surveys SET inverter_details = ?, inverter_count = ? WHERE id = ?");
    $stmt->execute([$invJson, count($postedInv['inverters']), $survey_id]);

    // --- BATTERIES ---
    $postedBatteries = $_POST['batteries'] ?? [];
    if (!is_array($postedBatteries))
        $postedBatteries = [];
    $cleanBatteries = array_values(array_map(function ($b) {
        if (!is_array($b))
            return [];
        return [
            'manufacturer' => trim($b['manufacturer'] ?? ''),
            'model' => trim($b['model'] ?? ''),
            'type' => trim($b['type'] ?? ''),
            'serial' => trim($b['serial'] ?? ''),
            'volt' => trim($b['volt'] ?? ''),
            'amp' => trim($b['amp'] ?? ''),
            'cell' => trim($b['cell'] ?? ''),
        ];
    }, $postedBatteries));
    $batteryJson = json_encode($cleanBatteries, JSON_UNESCAPED_UNICODE);
    $stmt = $pdo->prepare("UPDATE surveys SET battery_details = ?, battery_count = ? WHERE id = ?");
    $stmt->execute([$batteryJson, count($cleanBatteries), $survey_id]);

    // --- CABLES (ac, dc, battery) ---

    foreach (['ac', 'dc', 'battery'] as $k) {
        $posted = $_POST[$k . '_cables'] ?? [];
        $clean = [];
        if (is_array($posted)) {
            foreach ($posted as $entry) {
                if (!is_array($entry))
                    continue;
                $clean[] = [
                    'name' => trim($entry['name'] ?? ''),
                    'name_custom' => trim($entry['name_custom'] ?? ''),
                    'core' => trim($entry['core'] ?? ''),
                    'core_custom' => trim($entry['core_custom'] ?? ''),
                    'mm' => trim($entry['mm'] ?? ''),
                    'feet' => trim($entry['feet'] ?? ''),
                    'feet_custom' => trim($entry['feet_custom'] ?? ''),
                    'length' => trim($entry['length'] ?? ''),
                ];
            }
        }
        $json = json_encode(array_values($clean), JSON_UNESCAPED_UNICODE);
        $stmt = $pdo->prepare("UPDATE surveys SET {$k}_cables = ? WHERE id = ?");
        $stmt->execute([$json, $survey_id]);
    }

    // update battery_installed checkbox
    $battery_installed_val = isset($_POST['battery_installed']) ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE surveys SET battery_installed = ? WHERE id = ?");
    $stmt->execute([$battery_installed_val, $survey_id]);
    // redirect to view to avoid resubmission
    header("Location: view_survey.php?id=" . urlencode($survey_id));
    exit;
}




// --- Decode inverter_details (make safe) ---
$invData = json_decode($survey['inverter_details'] ?? '[]', true);
if (!is_array($invData))
    $invData = [];
// ensure keys exist
if (!isset($invData['inverters']) || !is_array($invData['inverters'])) {
    $invData['inverters'] = [];
}
$invData['type'] = $invData['type'] ?? '';
$invData['phase'] = $invData['phase'] ?? '';

// --- Lookup maps (same approach as view_survey.php) ---
$invManuMap = [];
$stmt = $pdo->query("SELECT id, name FROM manufacturer_invertor");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $invManuMap[$row['id']] = $row['name'];
}

$invModelMap = [];
$stmt = $pdo->query("SELECT id, name FROM model_invertor");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $invModelMap[$row['id']] = $row['name'];
}

$invTypeMap = [];
$stmt = $pdo->query("SELECT id, name FROM type_invertor");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $invTypeMap[$row['id']] = $row['name'];
}

$invPhaseMap = [];
$stmt = $pdo->query("SELECT id, name FROM phase_type_inverter");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $invPhaseMap[$row['id']] = $row['name'];
}

// Helper to safely echo inverter values
function invVal($inv, $key, $default = '')
{
    return htmlspecialchars($inv[$key] ?? $default);
}

// Render boxes array inputs for an inverter
function renderBoxesArray($namePrefix, $boxes = [])
{
    // $namePrefix like: inverter_details[inverters][0]
    $html = '<div class="dynamic-boxes">';
    $count = 0;
    if (is_array($boxes) && count($boxes) > 0) {
        foreach ($boxes as $idx => $b) {
            $count++;
            $html .= '<input class="form-control mb-1" name="' . $namePrefix . '[boxes][]' . '" value="' . htmlspecialchars($b) . '" placeholder="' . ($idx + 1) . '">';
        }
    }
    // at least one empty input to allow editing
    if ($count === 0) {
        $html .= '<input class="form-control mb-1" name="' . $namePrefix . '[boxes][]' . '" value="" placeholder="1">';
    }
    $html .= '</div>';
    return $html;
}




// Decode battery details (just like we did for inverter)
$batteryData = json_decode($survey['battery_details'] ?? '[]', true);
$batteryManuMap = [];
$stmt = $pdo->query("SELECT id, name FROM manufacturer_battery");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $batteryManuMap[$row['id']] = $row['name'];
}

$batteryTypeMap = [];
$stmt = $pdo->query("SELECT id, name FROM type_battery");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $batteryTypeMap[$row['id']] = $row['name'];
}

$batteryModelMap = [];
$stmt = $pdo->query("SELECT id, name FROM model_battery");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $batteryModelMap[$row['id']] = $row['name'];
}

$cableNameMap = [];
$stmt = $pdo->query("SELECT id, value FROM cable_names");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cableNameMap[$row['id']] = $row['value'];
}

$mmMap = [];
$stmt = $pdo->query("SELECT id, value FROM cable_mms");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $mmMap[$row['id']] = $row['value'];
}

function checked($val)
{
    return $val ? 'checked' : '';
}
function e($key)
{
    global $survey;
    return htmlspecialchars($survey[$key] ?? '');
}
function renderBoxes($prefix, $json)
{
    $boxes = json_decode($json, true) ?? [];
    $html = '<div class="dynamic-boxes">';
    foreach ($boxes as $i => $value) {
        $html .= '<input class="form-control" name="' . $prefix . '_box_' . ($i + 1) . '" value="' . htmlspecialchars($value) . '" placeholder="' . ($i + 1) . '">';
    }
    $html .= '</div>';
    return $html;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Survey</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-section {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .form-section h5 {
            margin-bottom: 15px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
        }

        .dynamic-boxes {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .dynamic-boxes input {
            width: 70px;
        }



        .nm-timeline {
            display: flex;
            align-items: flex-start;
            gap: 0;
            margin: 14px 0 22px;
            padding: 14px 16px;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            background: #fff;
        }

        .nm-step {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            min-width: 0;
        }

        .nm-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 3px solid #6c757d;
            background: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            color: #fff;
            z-index: 2;
        }

        .nm-label {
            margin-top: 6px;
            font-size: 12px;
            text-align: center;
            line-height: 1.2;
            color: #495057;
            padding: 0 6px;
        }

        .nm-bar {
            position: absolute;
            top: 16px;
            left: 50%;
            right: -50%;
            height: 4px;
            background: #6c757d;
            z-index: 1;
        }

        .nm-step:last-child .nm-bar {
            display: none;
        }

        /* states */
        .nm-step.completed .nm-dot {
            background: #28a745;
            border-color: #28a745;
        }

        .nm-step.current .nm-dot {
            background: #ffc107;
            border-color: #ffc107;
            color: #212529;
        }

        .nm-step.upcoming .nm-dot {
            background: #6c757d;
            border-color: #6c757d;
        }

        .nm-bar.completed {
            background: #28a745;
        }

        .nm-bar.upcoming {
            background: #6c757d;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <div class="text-center mb-3">
            <img src="../assets/logo.png" alt="Company Logo" class="logo mb-2" style="max-height: 50px;">
        </div>
        <h4 class="mb-4">Edit Survey</h4>
        <form action="update_survey.php?id=<?= $survey_id ?>" method="POST" enctype="multipart/form-data">

            <!-- ESA Details -->
            <div class="form-section">
                <h5>ESA Details</h5>
                <div class="row mb-2">
                    <div class="col-md-4">
                        <label>ESA Serial No</label>
                        <input type="text" name="esa_serial" value="<?= e('esa_serial') ?>" class="form-control"
                            readonly>
                    </div>
                    <div class="col-md-4">
                        <label>Surveyor</label>
                        <input type="text" class="form-control" value="<?= $_SESSION['user_id'] ?>" readonly>
                        <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label>Date</label>
                        <input type="text" name="date" value="<?= date('d/m/Y', strtotime($survey['created_at'])) ?>"
                            class="form-control" readonly>
                    </div>
                </div>
            </div>

            <!-- Client Details -->
            <div class="form-section">
                <h5>Client Details</h5>
                <div class="row mb-2">

                    <div class="col-md-4">
                        <label>Client</label>
                        <input type="text" class="form-control"
                            value="<?= htmlspecialchars($survey['client_name']) ?>" readonly>
                    </div>

                    <div class="col-md-4">
                        <label>CNIC</label>
                        <input type="text" class="form-control"
                            value="<?= htmlspecialchars($survey['cnic']) ?>" readonly>
                    </div>

                    <div class="col-md-4">
                        <label>Contact</label>
                        <input type="text" class="form-control"
                            value="<?= htmlspecialchars($survey['contact_no_1']) ?>" readonly>
                    </div>

                    <div class="col-md-8">
                        <label>Address</label>
                        <input type="text" class="form-control"
                            value="<?= htmlspecialchars($survey['address']) ?>" readonly>
                    </div>

                </div>
            </div>


            <!-- System Info -->
            <div class="form-section">
                <h5>System & Bill Info</h5>
                <div class="row mb-3">
                    <div class="col-md-4"><label>System (KW)</label><input name="system_kw" class="form-control"
                            value="<?= e('system_kw') ?>"></div>
                    <div class="col-md-4">
                        <label>Connection Type</label>
                        <select name="connection_type" class="form-select">
                            <?php foreach (['Residential', 'Commercial', 'Industry', 'Agriculture'] as $type): ?>
                                <option <?= e('connection_type') === $type ? 'selected' : '' ?>><?= $type ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Service Type</label>
                        <select name="service_type" class="form-select">
                            <?php foreach (['Net Metering', 'Enhancement', 'Others'] as $type): ?>
                                <option <?= e('service_type') === $type ? 'selected' : '' ?>><?= $type ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4"><label>Bill No</label><input name="bill_no" class="form-control"
                            value="<?= e('bill_no') ?>"></div>
                    <div class="col-md-4">
                        <label>Bill Picture</label>
                        <input type="file" name="bill_pic" class="form-control">
                        <?php if ($survey['bill_pic']): ?>
                            <a href="../uploads/<?= $survey['bill_pic'] ?>" target="_blank">View</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mb-3"><label>Sanction Load</label><input name="sanction_load" class="form-control"
                        value="<?= e('sanction_load') ?>"></div>
            </div>

            <!-- Panels
        <div class="form-section">
            <h5>Solar Panel Details</h5>
            <div class="row mb-3">
                <div class="col-md-4"><label>Panel Model No</label><input name="panel_model_no" class="form-control" value="<?= e('panel_model_no') ?>"></div>
                <div class="col-md-4"><label>Panel Type</label><input name="panel_type" class="form-control" value="<?= e('panel_type') ?>"></div>
                <div class="col-md-4"><label>Manufacturer</label><input name="panel_manufacturer" class="form-control" value="<?= e('panel_manufacturer') ?>"></div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4"><label>Panel Power (KW)</label><input name="panel_power" class="form-control" value="<?= e('panel_power') ?>"></div>
                <div class="col-md-4"><label>No. of Panels</label><input name="panel_count" class="form-control" value="<?= e('panel_count') ?>"></div>
                <div class="col-md-4"><label>No. of Boxes</label><input name="panel_box_count" class="form-control" disabled value="<?= e('panel_box_count') ?>"></div>
            </div>
            <div class="mb-3"><label>Panel Box Details</label><?= renderBoxes('panel', $survey['panel_boxes']) ?></div>
            <div class="mb-3">
                <label>Panel Picture</label>
                <input type="file" name="panel_pic" class="form-control">
                <?php if ($survey['panel_pic']): ?>
                    <a href="../uploads/<?= $survey['panel_pic'] ?>" target="_blank">View</a>
                <?php endif; ?>
            </div>
        </div> -->


            <!-- Panels -->
            <div class="form-section">
                <h5>Solar Panel Details</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Panel Model No</label>
                        <input name="panel_model_no" class="form-control" value="<?= e('panel_model_no') ?>">
                    </div>
                    <div class="col-md-4">
                        <label>Panel Type</label>
                        <select name="panel_type" class="form-select">
                            <?php
                            $types = ["Poly Cristalline", "Mono Cristalline", "Thin film", "Bifacial", "Others"];
                            foreach ($types as $t) {
                                $sel = ($survey['panel_type'] ?? '') === $t ? 'selected' : '';
                                echo "<option value=\"$t\" $sel>$t</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Manufacturer</label>
                        <select name="panel_manufacturer" class="form-select">
                            <?php
                            $manufacturers = ["Longi", "Jinko", "Canadian", "Phono", "JA"];
                            foreach ($manufacturers as $m) {
                                $sel = ($survey['panel_manufacturer'] ?? '') === $m ? 'selected' : '';
                                echo "<option value=\"$m\" $sel>$m</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Panel Power (KW)</label>
                        <input name="panel_power" id="panelPower" class="form-control" value="<?= e('panel_power') ?>">
                    </div>
                    <div class="col-md-4">
                        <label>No. of Panels</label>
                        <input name="panel_count" id="panelCount" class="form-control" value="<?= e('panel_count') ?>">
                    </div>
                    <div class="col-md-4">
                        <label>Total PV (KW)</label>
                        <input class="form-control" id="totalPV" name="total_pv" value="<?=
                                                                                        (e('panel_power') && e('panel_count'))
                                                                                            ? number_format(e('panel_power') * e('panel_count'), 2)
                                                                                            : '' ?>" readonly>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>No. of Boxes</label>
                        <!-- Visible disabled field -->
                        <input class="form-control" value="<?= e('panel_box_count') ?>" disabled>
                        <!-- Hidden field to submit actual value -->
                        <input type="hidden" name="panel_box_count" value="<?= e('panel_box_count') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label>Panel Box Details</label>
                    <?= renderBoxes('panel', $survey['panel_boxes']) ?>
                </div>

                <div class="mb-3">
                    <label>Panel Picture</label>
                    <input type="file" name="panel_pic" class="form-control">
                    <?php if ($survey['panel_pic']): ?>
                        <a href="../uploads/<?= $survey['panel_pic'] ?>" target="_blank">View</a>
                    <?php endif; ?>
                </div>
            </div>


            <!-- Inverter Details (edit) -->
            <div class="form-section">
                <h5>Inverter Details</h5>

                <?php if (!empty($invData['inverters'])): ?>
                    <!-- Global: Type & Phase (one-time selectors) -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label>Type</label>
                            <select name="inverter_details[type]" class="form-control">
                                <option value="">-- Select Type --</option>
                                <?php foreach ($invTypeMap as $id => $label): ?>
                                    <option value="<?= $id ?>" <?= ($invData['type'] == $id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Phase</label>
                            <select name="inverter_details[phase]" class="form-control">
                                <option value="">-- Select Phase --</option>
                                <?php foreach ($invPhaseMap as $id => $label): ?>
                                    <option value="<?= $id ?>" <?= ($invData['phase'] == $id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label>Total No. of Inverters</label>
                            <input type="number" class="form-control"
                                value="<?= htmlspecialchars($invData['count'] ?? count($invData['inverters'] ?? [])) ?>"
                                disabled>
                        </div>
                    </div>

                    <?php foreach ($invData['inverters'] as $i => $inv):
                        $prefixName = "inverter_details[inverters][{$i}]";
                    ?>
                        <div class="card mb-3 p-3">
                            <div class="card-header">Inverter <?= $i + 1 ?></div>
                            <div class="card-body">
                                <div class="row g-2 mb-3">
                                    <div class="col-md-2">
                                        <label>KW</label>
                                        <input name="<?= $prefixName ?>[kw]" class="form-control"
                                            value="<?= invVal($inv, 'kw') ?>">
                                    </div>

                                    <div class="col-md-3">
                                        <label>Manufacturer</label>
                                        <select name="<?= $prefixName ?>[manufacturer]" class="form-control">
                                            <option value="">-- Select Manufacturer --</option>
                                            <?php foreach ($invManuMap as $mid => $mname): ?>
                                                <option value="<?= $mid ?>" <?= (isset($inv['manufacturer']) && $inv['manufacturer'] == $mid) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($mname) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label>Model</label>
                                        <select name="<?= $prefixName ?>[model]" class="form-control model-select"
                                            data-selected="<?= $inv['model'] ?? '' ?>">
                                            <option value="">-- Select Model --</option>
                                            <?php
                                            // Filter models based on the selected manufacturer
                                            if (!empty($inv['manufacturer'])) {
                                                $stmt = $pdo->prepare("SELECT id, name FROM model_invertor WHERE manufacturer_id = ? ORDER BY name");
                                                $stmt->execute([$inv['manufacturer']]);
                                                $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                foreach ($models as $model) {
                                                    $selected = (isset($inv['model']) && $inv['model'] == $model['id']) ? 'selected' : '';
                                                    echo "<option value='{$model['id']}' {$selected}>" . htmlspecialchars($model['name']) . "</option>";
                                                }
                                            }
                                            ?>
                                        </select>

                                    </div>

                                    <div class="col-md-2">
                                        <label>ID</label>
                                        <input name="<?= $prefixName ?>[id]" class="form-control"
                                            value="<?= invVal($inv, 'id') ?>">
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <label>Password</label>
                                        <input name="<?= $prefixName ?>[password]" class="form-control"
                                            value="<?= invVal($inv, 'password') ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label>Picture</label>
                                        <input type="file" name="inverter_pics[<?= $i ?>]" class="form-control">
                                        <?php if (!empty($inv['pic'])): ?>
                                            <div class="mt-1">
                                                <a href="../uploads/<?= htmlspecialchars($inv['pic']) ?>" target="_blank">View
                                                    existing</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-md-3">
                                        <label>No. of Panels</label>
                                        <input name="<?= $prefixName ?>[panel_count]" class="form-control"
                                            value="<?= invVal($inv, 'panel_count') ?>">
                                    </div>

                                    <div class="col-md-3">
                                        <label>No. of Boxes</label>
                                        <input class="form-control" disabled value="<?= invVal($inv, 'box_count') ?>">
                                        <input type="hidden" name="<?= $prefixName ?>[box_count]"
                                            value="<?= invVal($inv, 'box_count') ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label>Box Details</label>
                                    <?= renderBoxesArray($prefixName, $inv['boxes'] ?? []) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No inverter data found for this survey.</p>
                <?php endif; ?>
            </div>



            <!--Battery-->

            <div class="form-section">
                <h5>Battery Details</h5>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="battery_installed" id="batteryToggle"
                        value="1" <?= checked($survey['battery_installed']) ?>>
                    <label class="form-check-label" for="batteryToggle">Battery Installed</label>
                </div>

                <?php if (!empty($batteryData)): ?>
                    <?php foreach ($batteryData as $i => $battery): ?>
                        <div class="card mb-3">
                            <div class="card-header">Battery <?= $i + 1 ?></div>
                            <div class="card-body row g-3">

                                <!-- Manufacturer Dropdown -->
                                <div class="col-md-3">
                                    <label class="form-label">Manufacturer</label>
                                    <select name="batteries[<?= $i ?>][manufacturer]" class="form-control battery-manufacturer">
                                        <option value="">Select Manufacturer</option>
                                        <?php foreach ($batteryManuMap as $id => $name): ?>
                                            <option value="<?= $id ?>" <?= ($battery['manufacturer'] == $id) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Type Dropdown -->
                                <div class="col-md-3">
                                    <label class="form-label">Type</label>
                                    <select name="batteries[<?= $i ?>][type]" class="form-control battery-type"
                                        data-selected="<?= $battery['type'] ?? '' ?>">
                                        <option value="">Select Type</option>
                                        <?php
                                        if (!empty($battery['manufacturer'])) {
                                            $stmt = $pdo->prepare("SELECT id, name FROM type_battery WHERE manufacturer_id = ? ORDER BY name");
                                            $stmt->execute([$battery['manufacturer']]);
                                            $types = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                            foreach ($types as $t) {
                                                $selected = ($battery['type'] == $t['id']) ? 'selected' : '';
                                                echo "<option value='{$t['id']}' {$selected}>" . htmlspecialchars($t['name']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Model Dropdown -->
                                <div class="col-md-3">
                                    <label class="form-label">Model</label>
                                    <select name="batteries[<?= $i ?>][model]" class="form-control battery-model"
                                        data-selected="<?= $battery['model'] ?? '' ?>">
                                        <option value="">Select Model</option>
                                        <?php
                                        if (!empty($battery['manufacturer']) && !empty($battery['type'])) {
                                            $stmt = $pdo->prepare("SELECT id, name FROM model_battery WHERE manufacturer_id = ? AND type_id = ? ORDER BY name");
                                            $stmt->execute([$battery['manufacturer'], $battery['type']]);
                                            $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                            foreach ($models as $m) {
                                                $selected = ($battery['model'] == $m['id']) ? 'selected' : '';
                                                echo "<option value='{$m['id']}' {$selected}>" . htmlspecialchars($m['name']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Serial -->
                                <div class="col-md-3">
                                    <label class="form-label">Serial</label>
                                    <input name="batteries[<?= $i ?>][serial]" class="form-control" placeholder="Serial"
                                        value="<?= htmlspecialchars($battery['serial'] ?? '') ?>">
                                </div>

                                <!-- Volt -->
                                <div class="col-md-3">
                                    <label class="form-label">Volt</label>
                                    <input name="batteries[<?= $i ?>][volt]" class="form-control" placeholder="Volt"
                                        value="<?= htmlspecialchars($battery['volt'] ?? '') ?>">
                                </div>

                                <!-- Amp -->
                                <div class="col-md-3">
                                    <label class="form-label">Amp</label>
                                    <input name="batteries[<?= $i ?>][amp]" class="form-control" placeholder="Amp"
                                        value="<?= htmlspecialchars($battery['amp'] ?? '') ?>">
                                </div>

                                <!-- Cell -->
                                <div class="col-md-3">
                                    <label class="form-label">Cell</label>
                                    <input name="batteries[<?= $i ?>][cell]" class="form-control" placeholder="Cell"
                                        value="<?= htmlspecialchars($battery['cell'] ?? '') ?>">
                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No battery data found for this survey.</p>
                <?php endif; ?>
            </div>


            <!-- Cable Details (editable) -->
            <div class="form-section">
                <h5>Cable Details</h5>

                <?php
                $hasCableData = false; // flag to check if we have any cable data at all

                foreach (['ac' => 'AC', 'dc' => 'DC', 'battery' => 'Battery'] as $key => $label):
                    $cables = json_decode($survey[$key . '_cables'] ?? '[]', true);

                    if (empty($cables))
                        continue;

                    $hasCableData = true; // we have at least one set of cables
                ?>
                    <h6><?= $label ?> Cables</h6>

                    <?php foreach ($cables as $i => $cable): ?>
                        <div class="row mb-3 cable-row">

                            <div class="col-md-2">
                                <label class="form-label">Name</label>
                                <select name="<?= $key ?>_cables[<?= $i ?>][name]" class="form-control name-select">
                                    <option value="">Select</option>
                                    <?php
                                    // fetch only names allowed for this cable type ($key = ac/dc/battery)
                                    $stmt = $pdo->prepare("SELECT id, value FROM cable_names WHERE category IN ('all', ?) ORDER BY value");
                                    $stmt->execute([$key]);
                                    $names = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($names as $n) {
                                        $selected = ((string) ($cable['name'] ?? '') === (string) $n['id']) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($n['id']) . "' $selected>" . htmlspecialchars($n['value']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>


                            <!-- Core -->
                            <div class="col-md-2">
                                <label class="form-label">Core</label>
                                <select name="<?= $key ?>_cables[<?= $i ?>][core]" class="form-control core-select">
                                    <option value="">Select</option>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT id, value FROM cable_cores WHERE category IN ('all', ?) AND value <> 'custom' ORDER BY value");
                                    $stmt->execute([$key]); // $key = ac/dc/battery
                                    $cores = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($cores as $c) {
                                        $selected = ((string) ($cable['core'] ?? '') === (string) $c['id']) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($c['id']) . "' $selected>" . htmlspecialchars($c['value']) . "</option>";
                                    }
                                    ?>
                                    <!-- Only one Custom option -->
                                    <option value="custom" <?= ($cable['core'] ?? '') === 'custom' ? 'selected' : '' ?>>Custom
                                    </option>
                                </select>

                                <input type="text" name="<?= $key ?>_cables[<?= $i ?>][core_custom]"
                                    class="form-control core-custom mt-1" placeholder="Custom core"
                                    value="<?= htmlspecialchars($cable['core_custom'] ?? '') ?>">

                            </div>

                            <!-- MM -->
                            <!-- <div class="col-md-2">
                                <label class="form-label">MM</label>
                                <input name="<?= $key ?>_cables[<?= $i ?>][mm]" class="form-control"
                                    value="<?= htmlspecialchars($mmMap[$cable['mm']] ?? '') ?>">
                            </div> -->
                            <div class="col-md-2">
                                <label class="form-label">MM</label>
                                <select name="<?= $key ?>_cables[<?= $i ?>][mm]" class="form-control">
                                    <option value="">-- Select MM --</option>
                                    <?php
                                    // Fetch only MM values allowed for this cable type (ac/dc/battery)
                                    $stmt = $pdo->prepare("SELECT id, value FROM cable_mms WHERE category IN ('all', ?) ORDER BY value");
                                    $stmt->execute([$key]);
                                    $mms = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($mms as $mm) {
                                        $selected = ((string) ($cable['mm'] ?? '') === (string) $mm['id']) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($mm['id']) . "' $selected>" . htmlspecialchars($mm['value']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>



                            <!-- Feet -->
                            <div class="col-md-2">
                                <label class="form-label">Feet</label>
                                <select name="<?= $key ?>_cables[<?= $i ?>][feet]" class="form-control feet-select">
                                    <option value="">Select</option>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT id, value FROM cable_feet WHERE category IN ('all', ?) AND value <> 'custom' ORDER BY value");
                                    $stmt->execute([$key]); // $key = ac/dc/battery
                                    $feetRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($feetRows as $f) {
                                        $selected = ((string) ($cable['feet'] ?? '') === (string) $f['id']) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($f['id']) . "' $selected>" . htmlspecialchars($f['value']) . "</option>";
                                    }
                                    ?>
                                    <!-- Only one Custom option -->
                                    <option value="custom" <?= ($cable['feet'] ?? '') === 'custom' ? 'selected' : '' ?>>Custom
                                    </option>
                                </select>

                                <input type="text" name="<?= $key ?>_cables[<?= $i ?>][feet_custom]"
                                    class="form-control feet-custom mt-1" placeholder="Custom feet"
                                    value="<?= htmlspecialchars($cable['feet_custom'] ?? '') ?>">

                            </div>

                            <!-- Length -->
                            <div class="col-md-2">
                                <label class="form-label">Length</label>
                                <input name="<?= $key ?>_cables[<?= $i ?>][length]" class="form-control" placeholder="Length"
                                    value="<?= htmlspecialchars($cable['length'] ?? '') ?>">
                            </div>

                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>

                <?php if (!$hasCableData): ?>
                    <p class="text-muted">No cable data found for this survey.</p>
                <?php endif; ?>
            </div>


            <!-- Other -->
            <div class="form-section">
                <h5>Other Equipment</h5>
                <div class="row">
                    <?php foreach (['light_arrester', 'smart_controller', 'zero_export', 'light_earthing', 'delta_hub', 'ac_earthing', 'dc_earthing'] as $key): ?>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input type="checkbox" name="<?= $key ?>" value="1" <?= checked($survey[$key]) ?>
                                    class="form-check-input" id="<?= $key ?>">
                                <label for="<?= $key ?>"
                                    class="form-check-label text-capitalize"><?= str_replace('_', ' ', $key) ?></label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Final -->
            <div class="form-section">
                <h5>Net Metering Status</h5>
                <?php
                $nm_steps = [
                    'Application Submission',
                    'Survey & Drawing',
                    'Estimate Issuance',
                    'Estimate & SD Payment Recieved',
                    'Test Form Submission (Except Net Metering)',
                    'Material & Execution',
                    'Energisation',
                    'Case Completed'
                ];

                // $stored is now an integer index
                $stored = (int) e('net_metering_progress');
                $currentStep = ($stored >= 0 && $stored < count($nm_steps)) ? $stored : -1;
                ?>

                <!-- Dropdown -->
                <select name="net_metering_progress" id="net_metering_progress" class="form-select mb-3">
                    <?php foreach ($nm_steps as $i => $status): ?>
                        <option value="<?= $i ?>" <?= ($stored === $i ? 'selected' : '') ?>>
                            <?= htmlspecialchars($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Timeline Preview -->
                <div id="nm-timeline-edit" class="nm-timeline">
                    <?php foreach ($nm_steps as $i => $label):
                        $state = ($i < $currentStep) ? 'completed' : (($i === $currentStep) ? 'current' : 'upcoming');
                        $barCls = ($i < $currentStep) ? 'completed' : 'upcoming';
                    ?>
                        <div class="nm-step <?= $state ?>">
                            <div class="nm-dot">✓</div>
                            <div class="nm-label"><?= htmlspecialchars($label) ?></div>
                            <?php if ($i < count($nm_steps) - 1): ?>
                                <div class="nm-bar <?= $barCls ?>"></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Notes -->
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="3"><?= e('notes') ?></textarea>
            </div>

            <div class="d-grid">
                <button class="btn btn-primary">Update Survey</button>
            </div>
        </form>
        <a href="dashboard.php" class="btn btn-outline-secondary mt-3">← Back to Dashboard</a>
    </div>
    <script>
        function calculateTotalPV() {
            const power = parseFloat(document.getElementById('panelPower').value) || 0;
            const count = parseFloat(document.getElementById('panelCount').value) || 0;
            document.getElementById('totalPV').value = (power * count).toFixed(2);
        }

        document.getElementById('panelPower').addEventListener('input', calculateTotalPV);
        document.getElementById('panelCount').addEventListener('input', calculateTotalPV);

        // Run calculation once when page loads
        calculateTotalPV();
        document.addEventListener("DOMContentLoaded", function() {
            const toggle = document.getElementById("batteryToggle");
            const hasBatteryData = <?= !empty($batteryData) ? 'true' : 'false' ?>;

            if (hasBatteryData) {
                // If there is battery data, lock toggle to ON and disable it
                toggle.checked = true;
                toggle.disabled = true;
            } else {
                // If there is no battery data, lock toggle to OFF and disable it
                toggle.checked = false;
                toggle.disabled = true;
            }
        });

        document.querySelectorAll("select[name$='[manufacturer]']").forEach(manufacturerSelect => {
            manufacturerSelect.addEventListener("change", function() {
                const manufacturerId = this.value;
                const modelSelect = this.closest(".row").querySelector("select[name$='[model]']");
                modelSelect.innerHTML = '<option value="">-- Select Model --</option>'; // reset models

                if (!manufacturerId) return;

                fetch(`get_inverter_data.php?type=models&manufacturer_id=${manufacturerId}`)
                    .then(res => res.json())
                    .then(data => {
                        data.forEach(model => {
                            const option = document.createElement("option");
                            option.value = model.id;
                            option.textContent = model.name;
                            modelSelect.appendChild(option);
                        });
                    })
                    .catch(console.error);
            });
        });
        document.querySelectorAll(".battery-manufacturer").forEach(manufacturerSelect => {
            manufacturerSelect.addEventListener("change", function() {
                const manufacturerId = this.value;
                const card = this.closest(".card-body");
                const typeSelect = card.querySelector(".battery-type");
                const modelSelect = card.querySelector(".battery-model");

                typeSelect.innerHTML = '<option value="">Select Type</option>';
                modelSelect.innerHTML = '<option value="">Select Model</option>';

                if (!manufacturerId) return;

                fetch(`get_battery_data.php?type=types&manufacturer_id=${manufacturerId}`)
                    .then(res => res.json())
                    .then(types => {
                        types.forEach(t => {
                            const option = document.createElement("option");
                            option.value = t.id;
                            option.textContent = t.name;
                            typeSelect.appendChild(option);
                        });
                    });
            });
        });

        document.querySelectorAll(".battery-type").forEach(typeSelect => {
            typeSelect.addEventListener("change", function() {
                const typeId = this.value;
                const card = this.closest(".card-body");
                const manufacturerId = card.querySelector(".battery-manufacturer").value;
                const modelSelect = card.querySelector(".battery-model");

                modelSelect.innerHTML = '<option value="">Select Model</option>';

                if (!manufacturerId || !typeId) return;

                fetch(`get_battery_data.php?type=models&manufacturer_id=${manufacturerId}&type_id=${typeId}`)
                    .then(res => res.json())
                    .then(models => {
                        models.forEach(m => {
                            const option = document.createElement("option");
                            option.value = m.id;
                            option.textContent = m.name;
                            modelSelect.appendChild(option);
                        });
                    });
            });
        });
        (function() {
            function toggleCustom(select, customSelector) {
                var row = select.closest('.row');
                var custom = row ? row.querySelector(customSelector) : null;
                if (!custom) return;
                custom.style.display = (select.value === 'custom') ? '' : 'none';
            }

            // initial toggle for core and feet selects
            document.querySelectorAll('.core-select').forEach(function(s) {
                toggleCustom(s, '.core-custom');
                s.addEventListener('change', function() {
                    toggleCustom(s, '.core-custom');
                });
            });

            document.querySelectorAll('.feet-select').forEach(function(s) {
                toggleCustom(s, '.feet-custom');
                s.addEventListener('change', function() {
                    toggleCustom(s, '.feet-custom');
                });
            });

            // name custom toggle (name-select -> name-custom)
            document.querySelectorAll('.name-select').forEach(function(s) {
                toggleCustom(s, '.name-custom');
                s.addEventListener('change', function() {
                    toggleCustom(s, '.name-custom');
                });
            });
        })();



        const initialStep = <?= $currentStep ?>; // DB value
        document.addEventListener("DOMContentLoaded", function() {
            const select = document.getElementById("net_metering_progress");
            const timeline = document.getElementById("nm-timeline-edit");
            const stepDivs = timeline.querySelectorAll(".nm-step");
            const bars = timeline.querySelectorAll(".nm-bar");

            let previewStep = initialStep; // for UI preview only

            function updateTimeline(stepIndex) {
                stepDivs.forEach((step, i) => {
                    step.classList.remove("completed", "current", "upcoming");
                    const state = (i < stepIndex) ? "completed" : (i === stepIndex ? "current" : "upcoming");
                    step.classList.add(state);
                    step.querySelector(".nm-dot").textContent = "✓";
                });

                bars.forEach((bar, i) => {
                    bar.classList.remove("completed", "upcoming");
                    bar.classList.add((i < stepIndex) ? "completed" : "upcoming");
                });
            }

            // Initial render from DB
            updateTimeline(initialStep);

            // Sequential restriction for preview
            select.addEventListener("change", function() {
                const newStep = parseInt(this.value, 10);

                if (!isNaN(newStep) && newStep === initialStep + 1) {
                    // allow preview forward by 1
                    previewStep = newStep;
                    updateTimeline(previewStep);
                } else {
                    alert("You must complete the previous step before moving ahead. Save progress step by step.");
                    this.value = initialStep; // reset dropdown back to DB step
                    updateTimeline(initialStep); // reset timeline as well
                }
            });
        });
    </script>

</body>

</html>