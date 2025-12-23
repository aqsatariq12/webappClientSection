<?php
// api/surveys.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/token_auth.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
// $method = $_SERVER['REQUEST_METHOD'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function safe_json_decode($s)
{
    if ($s === null || $s === '') return [];
    $j = json_decode($s, true);
    return json_last_error() === JSON_ERROR_NONE ? $j : [];
}

function to_bool($v)
{
    return (bool) intval($v);
}

function input_json()
{
    $raw = file_get_contents('php://input');
    $j = json_decode($raw, true);
    return is_array($j) ? $j : [];
}

function tryDecode($val)
{
    if ($val === null) return null;
    if (is_array($val) || is_object($val)) return $val;
    $d = json_decode($val, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $d : $val;
}

$allowed = [
    'esa_serial',
    'client_id',
    'connection_type',
    'service_type',
    'bill_no',
    'sanction_load',
    'system_kw',
    'battery_count',
    'panel_details',
    'battery_details',
    'cables_details',
    'other_equipment',
    'net_metering_status',
    'notes',
    'panel_model_no',
    'panel_type',
    'panel_manufacturer',
    'panel_power',
    'panel_count',
    'panel_box_count',
    'panel_boxes',
    'inverter_count',
    'inverter_details',
    'battery_installed',
    'ac_cables',
    'dc_cables',
    'battery_cables',
    'light_arrester',
    'smart_controller',
    'zero_export',
    'light_earthing',
    'delta_hub',
    'ac_earthing',
    'dc_earthing',
    'net_metering_progress',
    'bill_pic',
    'panel_pic'
];

// preview (GET) -> ?action=preview&client_id=123
if ($action === 'preview' && $method === 'GET') {
    // require a valid token and user
    $current = requireAuth($pdo);
    $client_id = isset($_GET['client_id']) ? trim($_GET['client_id']) : null;

    // generate ESA preview using MAX(id)+1 pattern
    try {
        $stmt = $pdo->query("SELECT IFNULL(MAX(id), 0) as last_id FROM surveys");
        $last_id = (int)$stmt->fetchColumn();
        $next = $last_id + 1;
        $esa_serial = "SURV-" . str_pad($next, 3, '0', STR_PAD_LEFT) . "/" . date('m') . "/" . date('Y');
    } catch (Exception $e) {
        $esa_serial = "SURV-###/" . date('m') . "/" . date('Y');
    }

    // surveyor name from current authenticated user (if available)
    $surveyor_name = '';
    if (!empty($current['id'])) {
        try {
            $sstmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $sstmt->execute([$current['id']]);
            $surveyor_name = $sstmt->fetchColumn() ?: '';
        } catch (Exception $e) {
            $surveyor_name = '';
        }
    }

    // optional: fetch client info if you have a clients table
    $client = null;
    if ($client_id !== null) {
        try {
            $cstmt = $pdo->prepare("SELECT id, name, address FROM clients WHERE id = ? LIMIT 1");
            $cstmt->execute([$client_id]);
            $client = $cstmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            $client = null;
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'esa_serial' => $esa_serial,
        'date' => date('Y-m-d'),
        'surveyor_name' => $surveyor_name,
        'client' => $client
    ]);
    exit;
}

// CREATE
if ($action === 'create' && $method === 'POST') {
    $current = requireAuth($pdo);
    $in = input_json();
    // fall back to $_POST if JSON decoding failed (keeps compatibility)
    if (empty($in)) $in = $_POST;

    $surveyToken = bin2hex(random_bytes(16));
    $fields = [];
    $placeholders = [];
    $values = [];

    foreach ($allowed as $f) {
        // treat an explicit empty string as "not provided" (so API will generate defaults)
        if (array_key_exists($f, $in) && $in[$f] !== '') {
            $val = $in[$f];
            if (in_array($f, [
                'inverter_details',
                'battery_details',
                'panel_boxes',
                'ac_cables',
                'dc_cables',
                'battery_cables',
                'other_equipment',
                'notes',
                'panel_details',
                'cables_details'
            ])) {
                if (is_array($val) || is_object($val)) {
                    $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                }
            }
            $fields[] = $f;
            $placeholders[] = '?';
            $values[] = $val;
        }
    }


    $fields[] = 'token';
    $placeholders[] = '?';
    $values[] = $surveyToken;

    $fields[] = 'user_id';
    $placeholders[] = '?';
    $values[] = $current['id'];

    $sql = "INSERT INTO surveys (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    $id = $pdo->lastInsertId();

    // generate esa_serial if it wasn't included in the inserted fields
    // NOTE: we check the fields array we used for INSERT, not the original $in
    if (!in_array('esa_serial', $fields)) {
        $esa_serial = "SURV-" . str_pad($id, 3, '0', STR_PAD_LEFT) . "/" . date('m') . "/" . date('Y');
        $upd = $pdo->prepare("UPDATE surveys SET esa_serial = ? WHERE id = ?");
        $upd->execute([$esa_serial, $id]);
    } else {
        // if the client supplied an esa_serial, use that (sanitize if needed)
        $esa_serial = isset($in['esa_serial']) && $in['esa_serial'] !== '' ? $in['esa_serial'] : null;
        // if supplied but empty string, ensure we have generated one
        if ($esa_serial === null) {
            $esa_serial = "SURV-" . str_pad($id, 3, '0', STR_PAD_LEFT) . "/" . date('m') . "/" . date('Y');
            $upd = $pdo->prepare("UPDATE surveys SET esa_serial = ? WHERE id = ?");
            $upd->execute([$esa_serial, $id]);
        }
    }

    // return helpful data
    echo json_encode([
        'success' => true,
        'survey_id' => $id,
        'token' => $surveyToken,
        'esa_serial' => $esa_serial
    ]);
    exit;
}
// LIST
if ($action === 'list' && $method === 'GET') {
    $current = requireAuth($pdo);

    $page = max(1, intval($_GET['page'] ?? 1));
    $per = max(1, min(100, intval($_GET['per'] ?? 25)));
    $offset = ($page - 1) * $per;

    // Count total surveys for pagination
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM surveys WHERE user_id = :uid");
    $countStmt->execute([':uid' => $current['id']]);
    $total = (int)$countStmt->fetchColumn();
    $totalPages = (int)ceil($total / $per);

    // Fetch surveys for this page
    $stmt = $pdo->prepare("SELECT s.id, s.esa_serial, s.token, s.client_id, s.user_id,
       s.connection_type, s.service_type, s.bill_no, s.bill_pic,
       s.sanction_load, s.system_kw, s.inverter_details, s.inverter_count,
       s.panel_model_no, s.panel_type, s.panel_manufacturer, s.panel_power,
       s.panel_count, s.panel_box_count, s.panel_boxes, s.panel_pic,
       s.battery_installed, s.battery_count, s.battery_details,
       s.cables_details, s.ac_cables, s.dc_cables, s.battery_cables,
       s.other_equipment, s.net_metering_status, s.net_metering_progress,
       s.light_arrester, s.smart_controller, s.zero_export,
       s.light_earthing, s.delta_hub, s.ac_earthing, s.dc_earthing,
       s.notes, s.created_at,
       c.name AS client_name
FROM surveys s
LEFT JOIN clients c ON s.client_id = c.id
WHERE s.user_id = :uid
ORDER BY s.created_at DESC
LIMIT :per OFFSET :offset");


    $stmt->bindValue(':uid', $current['id'], PDO::PARAM_INT);
    $stmt->bindValue(':per', $per, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // decode JSON fields
    foreach ($rows as &$row) {
        $row['inverter_details'] = json_decode($row['inverter_details'], true);
        $row['battery_details']  = json_decode($row['battery_details'], true);
        $row['cables_details']   = json_decode($row['cables_details'], true);
        $row['ac_cables']        = json_decode($row['ac_cables'], true);
        $row['dc_cables']        = json_decode($row['dc_cables'], true);
        $row['battery_cables']   = json_decode($row['battery_cables'], true);
        $row['panel_boxes']      = json_decode($row['panel_boxes'], true);
    }
    unset($row);
    echo json_encode([
        'success' => true,
        'page' => $page,
        'per' => $per,
        'total' => $total,
        'total_pages' => $totalPages,
        'surveys' => $rows
    ], JSON_PRETTY_PRINT);

    exit;
}

// VIEW
if ($action === 'view' && $method === 'GET') {
    // 1) Auth
    $user = requireAuth($pdo); // returns id, name, email, role

    // 2) Validate id
    $idParam = $_GET['id'] ?? '';
    if (!$idParam || !ctype_digit(strval($idParam))) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid id']);
        exit;
    }
    $surveyId = intval($idParam);

    // 3) Fetch survey (single query)
    $stmt = $pdo->prepare("
        SELECT s.*, u.name AS surveyor_name, u.email AS surveyor_email
        FROM surveys s
        LEFT JOIN users u ON u.id = s.user_id
        WHERE s.id = ? LIMIT 1
    ");
    $stmt->execute([$surveyId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Survey not found']);
        exit;
    }

    // 4) Permission: surveyor can only view own surveys
    if (($user['role'] ?? '') !== 'admin' && intval($row['user_id']) !== intval($user['id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden — you do not have access to this survey']);
        exit;
    }

    // 5) Normalize/decode fields for JSON response
    $survey = [
        'id' => intval($row['id']),
        'esa_serial' => $row['esa_serial'] ?? null,
        'client_id' => isset($row['client_id']) ? intval($row['client_id']) : null,
        'user_id' => isset($row['user_id']) ? intval($row['user_id']) : null,
        'surveyor_name' => $row['surveyor_name'] ?? null,
        'surveyor_email' => $row['surveyor_email'] ?? null,

        // Basic/system fields
        'connection_type' => $row['connection_type'] ?? null,
        'service_type' => $row['service_type'] ?? null,
        'bill_no' => $row['bill_no'] ?? null,
        'bill_pic' => $row['bill_pic'] ?? null,
        'sanction_load' => $row['sanction_load'] ?? null,
        'system_kw' => isset($row['system_kw']) ? floatval($row['system_kw']) : null,

        // Panel fields
        'panel_model_no' => $row['panel_model_no'] ?? null,
        'panel_type' => $row['panel_type'] ?? null,
        'panel_manufacturer' => $row['panel_manufacturer'] ?? null,
        'panel_power' => isset($row['panel_power']) ? (float) $row['panel_power'] : null,
        'panel_count' => isset($row['panel_count']) ? intval($row['panel_count']) : 0,
        'panel_box_count' => isset($row['panel_box_count']) ? intval($row['panel_box_count']) : 0,
        'panel_boxes' => safe_json_decode($row['panel_boxes'] ?? ''),
        'panel_pic' => $row['panel_pic'] ?? null,

        // Inverter
        'inverter_count' => isset($row['inverter_count']) ? intval($row['inverter_count']) : 0,
        'inverter_details' => safe_json_decode($row['inverter_details'] ?? ''),

        // Battery
        'battery_installed' => to_bool($row['battery_installed'] ?? 0),
        'battery_count' => isset($row['battery_count']) ? intval($row['battery_count']) : 0,
        'battery_details' => safe_json_decode($row['battery_details'] ?? ''),

        // Cables
        'ac_cables' => safe_json_decode($row['ac_cables'] ?? ''),
        'dc_cables' => safe_json_decode($row['dc_cables'] ?? ''),
        'battery_cables' => safe_json_decode($row['battery_cables'] ?? ''),

        // Other equipment (often stored as JSON or flags)
        'other_equipment' => safe_json_decode($row['other_equipment'] ?? ''),

        // Flags (tinyint -> bool)
        'light_arrester' => to_bool($row['light_arrester'] ?? 0),
        'smart_controller' => to_bool($row['smart_controller'] ?? 0),
        'zero_export' => to_bool($row['zero_export'] ?? 0),
        'light_earthing' => to_bool($row['light_earthing'] ?? 0),
        'delta_hub' => to_bool($row['delta_hub'] ?? 0),
        'ac_earthing' => to_bool($row['ac_earthing'] ?? 0),
        'dc_earthing' => to_bool($row['dc_earthing'] ?? 0),

        // Net metering & notes
        'net_metering_status' => $row['net_metering_status'] ?? null,
        'net_metering_progress' => isset($row['net_metering_progress']) ? intval($row['net_metering_progress']) : null,
        'notes' => $row['notes'] ?? null,

        // Metadata
        'created_at' => $row['created_at'] ?? null,

        // images placeholder (implement images later)
        'images' => []
    ];
    if (!empty($survey['inverter_details'])) {
        $invDetails = $survey['inverter_details'];

        // --- Lookup maps ---
        $invTypeMap = [];
        foreach ($pdo->query("SELECT id, name FROM type_invertor") as $r) {
            $invTypeMap[$r['id']] = $r['name'];
        }

        $invPhaseMap = [];
        foreach ($pdo->query("SELECT id, name FROM phase_type_inverter") as $r) {
            $invPhaseMap[$r['id']] = $r['name'];
        }

        $invManuMap = [];
        foreach ($pdo->query("SELECT id, name FROM manufacturer_invertor") as $r) {
            $invManuMap[$r['id']] = $r['name'];
        }

        $invModelMap = [];
        foreach ($pdo->query("SELECT id, name FROM model_invertor") as $r) {
            $invModelMap[$r['id']] = $r['name'];
        }

        // --- Add type and phase names ---
        if (isset($invDetails['type'])) {
            $invDetails['type_name'] = $invTypeMap[$invDetails['type']] ?? $invDetails['type'];
        }
        if (isset($invDetails['phase'])) {
            $invDetails['phase_name'] = $invPhaseMap[$invDetails['phase']] ?? $invDetails['phase'];
        }

        // --- Add manufacturer_name and model_name for each inverter ---
        if (!empty($invDetails['inverters']) && is_array($invDetails['inverters'])) {
            foreach ($invDetails['inverters'] as $k => $inv) {
                if (isset($inv['manufacturer'])) {
                    $invDetails['inverters'][$k]['manufacturer_name'] =
                        $invManuMap[$inv['manufacturer']] ?? $inv['manufacturer'];
                }
                if (isset($inv['model'])) {
                    $invDetails['inverters'][$k]['model_name'] =
                        $invModelMap[$inv['model']] ?? $inv['model'];
                }
            }
        }

        // --- Save back into survey ---
        $survey['inverter_details'] = $invDetails;
    }

    // --- Decode battery details ---
    $survey['battery_details'] = safe_json_decode($row['battery_details'] ?? '[]');

    // --- Lookup maps for battery fields ---
    $batManuMap = [];
    foreach ($pdo->query("SELECT id, name FROM manufacturer_battery") as $r) {
        $batManuMap[$r['id']] = $r['name'];
    }

    $batTypeMap = [];
    foreach ($pdo->query("SELECT id, name FROM type_battery") as $r) {
        $batTypeMap[$r['id']] = $r['name'];
    }

    $batModelMap = [];
    foreach ($pdo->query("SELECT id, name FROM model_battery") as $r) {
        $batModelMap[$r['id']] = $r['name'];
    }

    foreach ($survey['battery_details'] as $k => $b) {
        $b = (array)$b;

        // Normalize IDs
        $survey['battery_details'][$k]['manufacturer_id'] = $b['manufacturer_id'] ?? $b['manufacturer'] ?? null;
        $survey['battery_details'][$k]['type_id'] = $b['type_id'] ?? $b['type'] ?? null;
        $survey['battery_details'][$k]['model_id'] = $b['model_id'] ?? $b['model'] ?? null;

        // Map names
        $survey['battery_details'][$k]['manufacturer_name'] =
            isset($survey['battery_details'][$k]['manufacturer_id']) ? ($batManuMap[$survey['battery_details'][$k]['manufacturer_id']] ?? $survey['battery_details'][$k]['manufacturer_id']) : '';
        $survey['battery_details'][$k]['type_name'] =
            isset($survey['battery_details'][$k]['type_id']) ? ($batTypeMap[$survey['battery_details'][$k]['type_id']] ?? $survey['battery_details'][$k]['type_id']) : '';
        $survey['battery_details'][$k]['model_name'] =
            isset($survey['battery_details'][$k]['model_id']) ? ($batModelMap[$survey['battery_details'][$k]['model_id']] ?? $survey['battery_details'][$k]['model_id']) : '';
    }

    // --- Add mapped names for each battery ---
    // Normalize every battery entry
    foreach ($survey['battery_details'] as $k => $b) {
        $b = (array)$b; // ensure array
        $survey['battery_details'][$k]['manufacturer_id'] = $b['manufacturer_id'] ?? $b['manufacturer'] ?? null;
        $survey['battery_details'][$k]['type_id'] = $b['type_id'] ?? $b['type'] ?? null;
        $survey['battery_details'][$k]['model_id'] = $b['model_id'] ?? $b['model'] ?? null;

        // Add mapped names
        $survey['battery_details'][$k]['manufacturer_name'] =
            isset($survey['battery_details'][$k]['manufacturer_id']) ? ($batManuMap[$survey['battery_details'][$k]['manufacturer_id']] ?? $survey['battery_details'][$k]['manufacturer_id']) : '';
        $survey['battery_details'][$k]['type_name'] =
            isset($survey['battery_details'][$k]['type_id']) ? ($batTypeMap[$survey['battery_details'][$k]['type_id']] ?? $survey['battery_details'][$k]['type_id']) : '';
        $survey['battery_details'][$k]['model_name'] =
            isset($survey['battery_details'][$k]['model_id']) ? ($batModelMap[$survey['battery_details'][$k]['model_id']] ?? $survey['battery_details'][$k]['model_id']) : '';
    }

    $cableNameMap = [];
    foreach ($pdo->query("SELECT id, category, value FROM cable_names") as $r) {
        $cableNameMap[$r['id']] = [
            'name' => $r['value'],
            'category' => $r['category']
        ];
    }

    // 6) Respond
    echo json_encode([
        'success' => true,
        'survey' => $survey,
        'cable_name_map' => $cableNameMap,
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

// UPDATE
if ($action === 'update' && $method === 'POST') {
    $current = requireAuth($pdo);
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'missing id'], JSON_PRETTY_PRINT);
        exit;
    }

    // Check survey ownership
    $stmt = $pdo->prepare("SELECT user_id FROM surveys WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $s = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$s) {
        http_response_code(404);
        echo json_encode(['error' => 'not found'], JSON_PRETTY_PRINT);
        exit;
    }
    if (intval($s['user_id']) !== intval($current['id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'forbidden'], JSON_PRETTY_PRINT);
        exit;
    }

    // Input JSON or fallback to POST
    $in = input_json();
    if (empty($in)) $in = $_POST;

    $sets = [];
    $vals = [];
    foreach ($allowed as $f) {
        if (array_key_exists($f, $in)) {
            $val = $in[$f];

            // JSON encode structured fields
            if (in_array($f, [
                'inverter_details',
                'panel_boxes',
                'battery_details',
                'ac_cables',
                'dc_cables',
                'battery_cables',
                'other_equipment',
                'net_metering_progress'
            ])) {
                if (is_array($val) || is_object($val)) {
                    $val = json_encode($val, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                }
            }

            $sets[] = "$f = ?";
            $vals[] = $val;
        }
    }

    if (empty($sets)) {
        echo json_encode(['success' => true, 'message' => 'nothing to update'], JSON_PRETTY_PRINT);
        exit;
    }

    $vals[] = $id;
    $sql = "UPDATE surveys SET " . implode(',', $sets) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($vals);

    echo json_encode(['success' => true, 'id' => $id], JSON_PRETTY_PRINT);
    exit;
}


// DELETE
if ($action === 'delete' && $method === 'POST') {
    $current = requireAuth($pdo);
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'missing id']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT user_id FROM surveys WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $s = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$s) {
        http_response_code(404);
        echo json_encode(['error' => 'not found']);
        exit;
    }
    if (intval($s['user_id']) !== intval($current['id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'forbidden']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM surveys WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);
    exit;
}

// If action not matched
http_response_code(400);
echo json_encode(['error' => 'bad request']);
exit;
