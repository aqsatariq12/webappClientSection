<?php
// api/assignments.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/token_auth.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($action === 'list' && $method === 'GET') {
    $user = requireAuth($pdo);
    $surveyor_id = $user['id'];

    $stmt = $pdo->prepare("
        SELECT c.id, c.name, c.cnic, c.address, c.contact_no_1 AS contact, a.assigned_date
        FROM clients c
        INNER JOIN assignments a ON c.id = a.client_id
        WHERE a.surveyor_id = ?
        ORDER BY a.assigned_date DESC
    ");
    $stmt->execute([$surveyor_id]);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'clients' => $clients], JSON_PRETTY_PRINT);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Bad request']);
exit;