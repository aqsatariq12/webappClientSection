<?php
// api/token_auth.php
require_once __DIR__ . '/../includes/db.php';

// api/token_auth.php (replace getBearerToken() with this)
function getBearerToken() {
    // Preferred: try common server variables first
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            return $matches[1];
        }
    }
    // Some servers use REDIRECT_HTTP_AUTHORIZATION
    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s(\S+)/', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches)) {
            return $matches[1];
        }
    }
    // Fallback: getallheaders() but handle lowercase keys
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        // normalize keys to lowercase
        $normalized = [];
        foreach ($headers as $k => $v) {
            $normalized[strtolower($k)] = $v;
        }
        if (!empty($normalized['authorization'])) {
            if (preg_match('/Bearer\s(\S+)/', $normalized['authorization'], $matches)) {
                return $matches[1];
            }
        }
    }
    // Last resort: look in POST for 'token' or GET for 'token'
    if (!empty($_POST['token'])) {
        return trim($_POST['token']);
    }
    if (!empty($_GET['token'])) {
        return trim($_GET['token']);
    }

    return null;
}

function requireAuth($pdo) {
    $token = getBearerToken();

    // Allow ?token=XYZ for browser testing
    if (!$token && isset($_GET['token'])) {
        $token = $_GET['token'];
    }

    header('Content-Type: application/json; charset=utf-8');

    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Missing Authorization token']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE api_token = ? LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }

    return $user; // return current user row
}

