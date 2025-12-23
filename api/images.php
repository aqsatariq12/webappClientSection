<?php
// api/images.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/token_auth.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// where files are stored (ensure this folder exists and is writable)
$UPLOAD_DIR = __DIR__ . '/../uploads/';
if (!is_dir($UPLOAD_DIR))
    mkdir($UPLOAD_DIR, 0755, true);

// compute protocol
$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443) ? 'https' : 'http';

// script dir, e.g. "/surveyor.isa.muhasibpos/api"
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

// site base is one level up from the api folder, e.g. "/surveyor.isa.muhasibpos"
$siteBase = dirname($scriptDir);
if ($siteBase === '/' || $siteBase === '\\')
    $siteBase = ''; // root app guard

$BASE_URL = $protocol . '://' . $_SERVER['HTTP_HOST'] . rtrim($siteBase, '/') . '/uploads/';


$ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'pdf'];

function input_json_api()
{
    $raw = file_get_contents('php://input');
    $j = json_decode($raw, true);
    return is_array($j) ? $j : [];
}

function json_success($data = [])
{
    echo json_encode(array_merge(['success' => true], $data), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error($msg, $code = 400)
{
    http_response_code($code);
    echo json_encode(['error' => $msg], JSON_PRETTY_PRINT);
    exit;
}

// -------------------------------------------------------------
// UPLOAD -> POST multipart/form-data
// params: survey_id (required), image_type (...)
// file field = survey_image
// -------------------------------------------------------------
if ($action === 'upload' && $method === 'POST') {
    $user = requireAuth($pdo);

    $survey_id = intval($_POST['survey_id'] ?? 0);
    if (!$survey_id)
        json_error('Missing survey_id', 400);

    // check survey exists and permission
    $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ? LIMIT 1");
    $stmt->execute([$survey_id]);
    $survey = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$survey)
        json_error('Survey not found', 404);
    if (($user['role'] ?? '') !== 'admin' && intval($survey['user_id']) !== intval($user['id'])) {
        json_error('Forbidden', 403);
    }

    if (!isset($_FILES['survey_image']))
        json_error('Missing file field survey_image', 400);
    $f = $_FILES['survey_image'];
    if ($f['error'] !== UPLOAD_ERR_OK)
        json_error('File upload error', 400);

    $origName = basename($f['name']);
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, $ALLOWED_EXT))
        json_error('Invalid file type', 400);

    // normalize image type
    $image_type = trim($_POST['image_type'] ?? '');
    $valid_case1 = ['Bill Pic', 'Panel Pic', 'Inverter Pic'];
    $valid_case2 = ['Bill Pic', 'Panel Pic', 'Inverter Pic', 'CNIC Front', 'CNIC Back', 'Other Attachment'];
    if (!in_array($image_type, $valid_case2)) {
        json_error('Invalid image_type', 400);
    }

    $prefix = 'survey';
    if (stripos($image_type, 'bill') !== false) $prefix = 'bill';
    if (stripos($image_type, 'panel') !== false) $prefix = 'panel';
    if (stripos($image_type, 'inverter') !== false) $prefix = 'inv';
    if (stripos($image_type, 'cnic') !== false) $prefix = 'cnic';

    $newName = uniqid($prefix . '_', true) . '.' . $ext;
    $dest = $UPLOAD_DIR . $newName;
    if (!move_uploaded_file($f['tmp_name'], $dest))
        json_error('Failed to move uploaded file', 500);

    $case = ($_POST['case'] ?? 'extra'); // default to extra uploads
    $image_record = null;

    // ---------------- CASE 1: Main Form ----------------
    if ($case === 'form') {
        if (!in_array($image_type, $valid_case1)) {
            json_error('This image type is not allowed in survey form', 400);
        }

        if ($image_type === 'Bill Pic') {
            if (!empty($survey['bill_pic']) && file_exists($UPLOAD_DIR . $survey['bill_pic']))
                @unlink($UPLOAD_DIR . $survey['bill_pic']);
            $stmt = $pdo->prepare("UPDATE surveys SET bill_pic = ? WHERE id = ?");
            $stmt->execute([$newName, $survey_id]);
            $image_record = ['where' => 'surveys.bill_pic', 'file' => $newName];

        } elseif ($image_type === 'Panel Pic') {
            if (!empty($survey['panel_pic']) && file_exists($UPLOAD_DIR . $survey['panel_pic']))
                @unlink($UPLOAD_DIR . $survey['panel_pic']);
            $stmt = $pdo->prepare("UPDATE surveys SET panel_pic = ? WHERE id = ?");
            $stmt->execute([$newName, $survey_id]);
            $image_record = ['where' => 'surveys.panel_pic', 'file' => $newName];

        } elseif ($image_type === 'Inverter Pic') {
            $inverter_index = isset($_POST['inverter_index']) ? intval($_POST['inverter_index']) : -1;

            $inv_json = $survey['inverter_details'] ?? '';
            $inv_arr = json_decode($inv_json, true);
            if (!is_array($inv_arr)) $inv_arr = [];

            // ensure array of inverters
            if (!isset($inv_arr['inverters']) || !is_array($inv_arr['inverters'])) {
                $inv_arr['inverters'] = [];
            }

            // make sure the index exists
            if (!isset($inv_arr['inverters'][$inverter_index])) {
                $inv_arr['inverters'][$inverter_index] = [];
            }

            // remove old pic if exists
            if (!empty($inv_arr['inverters'][$inverter_index]['pic'])) {
                $oldPic = $inv_arr['inverters'][$inverter_index]['pic'];
                if (file_exists($UPLOAD_DIR . $oldPic)) @unlink($UPLOAD_DIR . $oldPic);
            }

            // assign new pic (only one per inverter)
            $inv_arr['inverters'][$inverter_index]['pic'] = $newName;

            // save back
            $newJson = json_encode($inv_arr, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            $stmt = $pdo->prepare("UPDATE surveys SET inverter_details = ? WHERE id = ?");
            $stmt->execute([$newJson, $survey_id]);

            $image_record = [
                'where' => "surveys.inverter_details[$inverter_index].pic",
                'inverter_index' => $inverter_index,
                'file' => $newName
            ];
        }

    // ---------------- CASE 2: Extra Uploads ----------------
    } else {
        $stmt = $pdo->prepare("INSERT INTO survey_images (survey_id, image_type, image_path) VALUES (?, ?, ?)");
        $stmt->execute([$survey_id, $image_type, $newName]);
        $image_record = ['where' => 'survey_images', 'id' => $pdo->lastInsertId(), 'file' => $newName];
    }

    json_success(['image' => $image_record, 'url' => $BASE_URL . $newName]);
}


// -------------------------------------------------------------
// LIST -> GET ?action=list&survey_id=123
// returns all images (surveys fields + survey_images rows)
// -------------------------------------------------------------
if ($action === 'list' && $method === 'GET') {
    $user = requireAuth($pdo);
    $survey_id = intval($_GET['survey_id'] ?? 0);
    if (!$survey_id)
        json_error('Missing survey_id', 400);

    $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ? LIMIT 1");
    $stmt->execute([$survey_id]);
    $survey = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$survey)
        json_error('Survey not found', 404);
    if (($user['role'] ?? '') !== 'admin' && intval($survey['user_id']) !== intval($user['id'])) {
        json_error('Forbidden', 403);
    }

    $images = [];

    // bill_pic
    if (!empty($survey['bill_pic'])) {
        $images[] = [
            'source' => 'surveys',
            'field' => 'bill_pic',
            'path' => $survey['bill_pic'],
            'url' => $BASE_URL . $survey['bill_pic']
        ];
    }

    // panel_pic
    if (!empty($survey['panel_pic'])) {
        $images[] = [
            'source' => 'surveys',
            'field' => 'panel_pic',
            'path' => $survey['panel_pic'],
            'url' => $BASE_URL . $survey['panel_pic']
        ];
    }

    // inverter pictures inside JSON
    $inv_json = $survey['inverter_details'] ?? '';
    $inv_arr = json_decode($inv_json, true);
    if (is_array($inv_arr)) {
        // find inverters array
        if (isset($inv_arr['inverters']) && is_array($inv_arr['inverters'])) {
            $inverters = $inv_arr['inverters'];
        } elseif (array_values($inv_arr) === $inv_arr) {
            $inverters = $inv_arr;
        } else {
            $inverters = [];
        }

        foreach ($inverters as $idx => $inv) {
            if (!empty($inv['pic'])) {
                $images[] = [
                    'source' => 'surveys',
                    'field' => 'inverter_pic',
                    'index' => $idx,
                    'inv_id' => $inv['id'] ?? null,
                    'path' => $inv['pic'],
                    'url' => $BASE_URL . $inv['pic']
                ];
            }
        }
    }

    // generic images from survey_images table
    $stmt = $pdo->prepare("SELECT id, image_type, image_path FROM survey_images WHERE survey_id = ? ORDER BY id DESC");
    $stmt->execute([$survey_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $images[] = [
            'source' => 'survey_images',
            'id' => intval($r['id']),
            'image_type' => $r['image_type'],
            'path' => $r['image_path'],
            'url' => $BASE_URL . $r['image_path']
        ];
    }

    json_success(['images' => $images]);
}

// -------------------------------------------------------------
// DELETE -> POST (json or form)
// prefer body: { "image_id": 123 }  to delete from survey_images
// OR { "survey_id":123, "field":"bill_pic" } to delete special fields
// OR { "survey_id":123, "inverter_index":0 } or inverter_id to clear an inverter pic
// -------------------------------------------------------------
if ($action === 'delete' && $method === 'POST') {
    $user = requireAuth($pdo);
    // permit json body
    $in = input_json_api();
    if (empty($in))
        $in = $_POST;

    $image_id = isset($in['image_id']) ? intval($in['image_id']) : 0;
    $survey_id = isset($in['survey_id']) ? intval($in['survey_id']) : intval($in['id'] ?? 0);

    if ($image_id) {
        // delete from survey_images
        $stmt = $pdo->prepare("SELECT survey_id, image_path FROM survey_images WHERE id = ? LIMIT 1");
        $stmt->execute([$image_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row)
            json_error('Image not found', 404);

        // check ownership
        $stmt = $pdo->prepare("SELECT user_id FROM surveys WHERE id = ? LIMIT 1");
        $stmt->execute([$row['survey_id']]);
        $s = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$s)
            json_error('Survey not found', 404);
        if (($user['role'] ?? '') !== 'admin' && intval($s['user_id']) !== intval($user['id']))
            json_error('Forbidden', 403);

        // delete file
        $path = $UPLOAD_DIR . $row['image_path'];
        if (file_exists($path))
            @unlink($path);

        // delete db row
        $stmt = $pdo->prepare("DELETE FROM survey_images WHERE id = ?");
        $stmt->execute([$image_id]);

        json_success(['deleted' => $image_id]);
    }

    // delete a surveys field (bill_pic / panel_pic)
    $field = $in['field'] ?? '';
    if ($survey_id && in_array($field, ['bill_pic', 'panel_pic'])) {
        $stmt = $pdo->prepare("SELECT user_id, {$field} FROM surveys WHERE id = ? LIMIT 1");
        $stmt->execute([$survey_id]);
        $s = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$s)
            json_error('Survey not found', 404);
        if (($user['role'] ?? '') !== 'admin' && intval($s['user_id']) !== intval($user['id']))
            json_error('Forbidden', 403);

        $old = $s[$field] ?? '';
        if ($old && file_exists($UPLOAD_DIR . $old))
            @unlink($UPLOAD_DIR . $old);

        $stmt = $pdo->prepare("UPDATE surveys SET {$field} = NULL WHERE id = ?");
        $stmt->execute([$survey_id]);

        json_success(['deleted_field' => $field, 'survey_id' => $survey_id]);
    }

    // delete inverter pic by index or id
    if ($survey_id && (isset($in['inverter_index']) || isset($in['inverter_id']))) {
        $stmt = $pdo->prepare("SELECT user_id, inverter_details FROM surveys WHERE id = ? LIMIT 1");
        $stmt->execute([$survey_id]);
        $s = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$s)
            json_error('Survey not found', 404);
        if (($user['role'] ?? '') !== 'admin' && intval($s['user_id']) !== intval($user['id']))
            json_error('Forbidden', 403);

        $inv_arr = json_decode($s['inverter_details'] ?? '', true);
        if (!is_array($inv_arr))
            $inv_arr = [];

        if (isset($inv_arr['inverters']) && is_array($inv_arr['inverters'])) {
            $inverters =& $inv_arr['inverters'];
        } elseif (is_array($inv_arr) && array_values($inv_arr) === $inv_arr) {
            $inverters =& $inv_arr;
        } else {
            $inverters = null;
        }

        if (!is_array($inverters))
            json_error('No inverter data to modify', 400);

        $deletedFile = null;
        $ok = false;
        if (isset($in['inverter_index'])) {
            $idx = intval($in['inverter_index']);
            if (isset($inverters[$idx]['pic'])) {
                $deletedFile = $inverters[$idx]['pic'];
                unset($inverters[$idx]['pic']);
                $ok = true;
            }
        } elseif (isset($in['inverter_id'])) {
            $iid = (string) $in['inverter_id'];
            foreach ($inverters as &$inv) {
                if ((string) ($inv['id'] ?? '') === $iid) {
                    if (!empty($inv['pic'])) {
                        $deletedFile = $inv['pic'];
                        unset($inv['pic']);
                        $ok = true;
                    }
                    break;
                }
            }
            unset($inv);
        }

        if (!$ok)
            json_error('Inverter entry not found', 404);

        // save back
        $newJson = json_encode($inv_arr, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $stmt = $pdo->prepare("UPDATE surveys SET inverter_details = ? WHERE id = ?");
        $stmt->execute([$newJson, $survey_id]);

        if ($deletedFile && file_exists($UPLOAD_DIR . $deletedFile))
            @unlink($UPLOAD_DIR . $deletedFile);

        json_success(['deleted_inverter_pic' => $deletedFile ?? null]);
    }

    json_error('Nothing to delete: provide image_id OR (survey_id & field) OR (survey_id & inverter_index/inverter_id)', 400);
    
}


// default
json_error('Bad request', 400);
