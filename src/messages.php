<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $_POST['subject'] ?? '';
    $body = $_POST['body'] ?? '';
    $priority = isset($_POST['priority']) ? 1 : 0;

    $stmt = $conn->prepare(
        "INSERT INTO messages (sender_id, subject, body, priority) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param(
        'issi',
        $_SESSION['user_id'],
        $subject,
        $body,
        $priority
    );

    if ($stmt->execute()) {
        $message = 'Message sent successfully.';
    } else {
        $message = 'Failed to send message.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Message - Internal Portal</title>
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

    <h2>Send a Message</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="subject">Subject</label>
        <input type="text" id="subject" name="subject" required>

        <label for="body">Message Body</label>
        <textarea id="body" name="body"></textarea>

        <div class="checkbox-row">
            <input type="checkbox" id="priority" name="priority">
            <label for="priority">Mark as priority</label>
        </div>

        <input type="submit" value="Send Message">
    </form>
</div>
</body>
</html>
