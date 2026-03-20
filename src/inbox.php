<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireAdmin();

$result = $conn->query(
    "SELECT m.*, u.display_name AS sender_name
     FROM messages m
     JOIN users u ON m.sender_id = u.id
     ORDER BY m.priority DESC, m.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox - Internal Portal</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<div class="container">
    <nav>
        <a href="/dashboard.php">Dashboard</a>
        <a href="/upload.php">Upload Files</a>
        <a href="/messages.php">Send Message</a>
        <a href="/inbox.php">Inbox</a>
        <a href="/users.php">Users</a>
        <span class="user-info">
            <?= htmlspecialchars($_SESSION['display_name']) ?>
            | <a href="/logout.php">Logout</a>
        </span>
    </nav>

    <h2>Inbox</h2>

    <?php if ($result->num_rows === 0): ?>
        <p>No messages.</p>
    <?php else: ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="message-card <?= $row['priority'] ? 'priority' : '' ?>">
                <div>
                    <?php if ($row['priority']): ?>
                        <span class="badge badge-priority">PRIORITY</span>
                    <?php endif; ?>
                    <strong><?= $row['subject'] ?></strong>
                </div>
                <?php if ($row['body']): ?>
                    <p style="margin-top: 8px;"><?= nl2br(htmlspecialchars($row['body'])) ?></p>
                <?php endif; ?>
                <div class="message-meta">
                    From: <?= htmlspecialchars($row['sender_name']) ?>
                    | <?= $row['created_at'] ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>
</body>
</html>
