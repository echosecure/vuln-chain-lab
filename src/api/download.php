<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/headers.php';
requireLogin();

$fileId = $_GET['file_id'] ?? null;

if (!$fileId) {
    http_response_code(400);
    echo 'Missing file_id parameter.';
    exit;
}

$stmt = $conn->prepare(
    "SELECT filename, content_type, file_data FROM uploads WHERE id = ?"
);
$stmt->bind_param('i', $fileId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    header('Content-Type: ' . $row['content_type']);
    $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $row['filename']);
    header('Content-Disposition: inline; filename="' . $safeFilename . '"');
    echo $row['file_data'];
} else {
    http_response_code(404);
    echo 'File not found.';
}
