<?php
require_once '../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

$type = $_GET['type'] ?? '';   // accepted: name, core, mm, feet
$cable = $_GET['cable'] ?? ''; // accepted: ac, dc, battery or 'all' or empty -> return all

$map = [
  'name' => 'cable_names',
  'core' => 'cable_cores',
  'mm'   => 'cable_mms',
  'feet' => 'cable_feet'
];

if (!isset($map[$type])) {
  echo json_encode([], JSON_UNESCAPED_UNICODE);
  exit;
}

$table = $map[$type];

// allow only known cable values
$allowed = ['ac','dc','battery','all',''];
if (!in_array($cable, $allowed)) {
  echo json_encode([], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  if ($cable === '' || $cable === 'all') {
    $stmt = $pdo->query("SELECT id, value FROM {$table} ORDER BY value");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } else {
    $stmt = $pdo->prepare("SELECT id, value FROM {$table} WHERE category IN ('all', ?) ORDER BY value");
    $stmt->execute([$cable]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
  echo json_encode($rows, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
  // return empty on error
  echo json_encode([], JSON_UNESCAPED_UNICODE);
}
