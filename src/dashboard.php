<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Internal Portal</title>
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
            (<?= htmlspecialchars($_SESSION['role']) ?>)
            | <a href="/logout.php">Logout</a>
        </span>
    </nav>

    <h1>Welcome, <?= htmlspecialchars($_SESSION['display_name']) ?></h1>
    <p>Use the navigation above to access portal features.</p>
</div>
</body>
</html>
