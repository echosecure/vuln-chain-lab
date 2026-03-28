<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/headers.php';
require_once __DIR__ . '/includes/csrf.php';
requireLogin();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    if (!validateCsrfToken()) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    } elseif ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file'];
        $filename = $file['name'];
        $contentType = $file['type'];
        $fileData = file_get_contents($file['tmp_name']);

        $stmt = $conn->prepare(
            "INSERT INTO uploads (user_id, filename, content_type, file_data) VALUES (?, ?, ?, ?)"
        );
        $null = null;
        $stmt->bind_param(
            'issb',
            $_SESSION['user_id'],
            $filename,
            $contentType,
            $null
        );
        $stmt->send_long_data(3, $fileData);

        if ($stmt->execute()) {
            $fileId = $conn->insert_id;
            $message = "File uploaded successfully. File ID: $fileId";
            $messageType = 'success';
        } else {
            $message = "Upload failed: " . $conn->error;
            $messageType = 'error';
        }
    } else {
        $code = $_FILES['file']['error'];
        $message = "Upload error (code: {$code}).";
        $messageType = 'error';
    }
}

$uploads = $conn->prepare(
    "SELECT id, filename, content_type, uploaded_at FROM uploads WHERE user_id = ? ORDER BY uploaded_at DESC"
);
$uploads->bind_param('i', $_SESSION['user_id']);
$uploads->execute();
$result = $uploads->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Files - Internal Portal</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<div class="container">
    <nav>
        <a href="/dashboard.php">Dashboard</a>
        <a href="/upload.php">Upload Files</a>
        <a href="/messages.php">Send Message</a>
        <?php if (isAdmin()): ?>
            <a href="/inbox.php">Inbox</a>
            <a href="/users.php">Users</a>
        <?php endif; ?>
        <span class="user-info">
            <?= htmlspecialchars($_SESSION['display_name']) ?>
            | <a href="/logout.php">Logout</a>
        </span>
    </nav>

    <h2>Upload a File</h2>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <label for="file">Select file (PDF only)</label>
        <input type="file" id="file" name="file" accept=".pdf" required>
        <input type="submit" value="Upload">
    </form>

    <h2>Your Uploads</h2>
    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Filename</th>
                <th>Type</th>
                <th>Uploaded</th>
                <th>Action</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['filename']) ?></td>
                    <td><?= htmlspecialchars($row['content_type']) ?></td>
                    <td><?= $row['uploaded_at'] ?></td>
                    <td><a href="/api/download.php?file_id=<?= $row['id'] ?>">Download</a></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No files uploaded yet.</p>
    <?php endif; ?>
</div>
</body>
</html>
