<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireAdmin();

$result = $conn->query("SELECT id, display_name, email, role, created_at FROM users ORDER BY id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Internal Portal</title>
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

    <h2>Users</h2>

    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Created</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['display_name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td>
                    <span class="badge badge-<?= htmlspecialchars($row['role']) ?>">
                        <?= htmlspecialchars($row['role']) ?>
                    </span>
                </td>
                <td><?= $row['created_at'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
