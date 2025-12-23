<?php
require_once '../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

$type = $_GET['type'] ?? '';

if ($type === 'manufacturers') {
    $stmt = $pdo->query("SELECT id, name FROM manufacturer_invertor ORDER BY name");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($type === 'models' && isset($_GET['manufacturer_id'])) {
    $stmt = $pdo->prepare("SELECT id, name FROM model_invertor WHERE manufacturer_id = ? ORDER BY name");
    $stmt->execute([$_GET['manufacturer_id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/* NEW: inverter types */
if ($type === 'inv_types') {
    $stmt = $pdo->query("SELECT id, name FROM type_invertor ORDER BY name");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/* NEW: inverter phase types */
if ($type === 'inv_phases') {
    $stmt = $pdo->query("SELECT id, name FROM phase_type_inverter ORDER BY name");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/* fallback */
echo json_encode([]);
