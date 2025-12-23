<?php
require_once '../includes/db.php';

$type = $_GET['type'] ?? '';

if ($type === 'manufacturers') {
    // Get all manufacturers
    $stmt = $pdo->query("SELECT id, name FROM manufacturer_battery ORDER BY name");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} elseif ($type === 'types' && isset($_GET['manufacturer_id'])) {
    // Get types for selected manufacturer
    $stmt = $pdo->prepare("SELECT id, name FROM type_battery WHERE manufacturer_id = ? ORDER BY name");
    $stmt->execute([$_GET['manufacturer_id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} elseif ($type === 'models' && isset($_GET['manufacturer_id']) && isset($_GET['type_id'])) {
    // Get models for selected manufacturer + type
    $stmt = $pdo->prepare("SELECT id, name FROM model_battery WHERE manufacturer_id = ? AND type_id = ? ORDER BY name");
    $stmt->execute([$_GET['manufacturer_id'], $_GET['type_id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
