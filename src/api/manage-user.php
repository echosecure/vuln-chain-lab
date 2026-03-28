<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/headers.php';
// NOTE: No CSRF validation on this API endpoint. This is a deliberate
// vulnerability — the other form endpoints validate CSRF tokens, but
// this one was missed. Separate finding in the pen test report.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$displayName = $_POST['display_name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'user';

header('Content-Type: application/json');

if (!$displayName || !$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$validRoles = ['user', 'admin'];
if (!in_array($role, $validRoles, true)) {
    $role = 'user';
}

$stmt = $conn->prepare(
    "INSERT INTO users (display_name, email, password, role) VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE display_name = VALUES(display_name),
     password = VALUES(password), role = VALUES(role)"
);
$stmt->bind_param('ssss', $displayName, $email, $password, $role);

if ($stmt->execute()) {
    $userId = $conn->insert_id;
    if (!$userId) {
        $lookup = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $lookup->bind_param('s', $email);
        $lookup->execute();
        $userId = $lookup->get_result()->fetch_assoc()['id'];
    }
    echo json_encode([
        'success' => true,
        'user_id' => $userId,
        'message' => 'User created'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create user: ' . $conn->error]);
}
