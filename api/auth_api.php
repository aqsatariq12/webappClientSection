<?php
// api/auth_api.php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

function input_json() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function generate_token() {
    return bin2hex(random_bytes(32));
}

// LOGIN
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Support JSON and normal form
    $in = json_decode(file_get_contents('php://input'), true);
    if (is_array($in) && isset($in['username'])) {
        $username = $in['username'] ?? '';
        $password = $in['password'] ?? '';
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
    }

    if (!$username || !$password) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password required']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid username']);
        exit;
    }

    // ✅ check hashed password stored in `password` column
    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid password']);
        exit;
    }

    // generate token
    $token = generate_token();
    $pdo->prepare("UPDATE users SET api_token = ? WHERE id = ?")
        ->execute([$token, $user['id']]);

    $safe_user = [
        'id' => $user['id'],
        'username' => $user['username'] ?? null,
        'role' => $user['role'] ?? null
    ];

    echo json_encode(['success' => true, 'token' => $token, 'user' => $safe_user]);
    exit;
}

// ME
if ($action === 'me' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    require_once __DIR__ . '/token_auth.php';
    $user = requireAuth($pdo);
    echo json_encode(['user' => $user]);
    exit;
}

// LOGOUT
if ($action === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/token_auth.php';
    $user = requireAuth($pdo);
    $pdo->prepare("UPDATE users SET api_token=NULL WHERE id=?")->execute([$user['id']]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Bad request']);
