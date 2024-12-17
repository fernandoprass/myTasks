<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Strict'
]);
require_once 'config.php';

// Session security check
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Mark notification as read with CSRF and SQL injection protection
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $_POST['notification_id'], $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch notifications using prepared statement
$stmt = $conn->prepare("SELECT n.*, t.title as task_title 
                       FROM notifications n 
                       JOIN tasks t ON n.task_id = t.id 
                       WHERE n.user_id = ? 
                       ORDER BY n.created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Notifications - Task Manager</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline';">
</head>
<body>
    <div class="container">
        <h1>Notifications</h1>
        
        <div class="notification-list">
            <?php while($notification = $result->fetch_assoc()): ?>
                <div class="notification <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                    <p><?php echo htmlspecialchars($notification['message']); ?></p>
                    <p>Task: <?php echo htmlspecialchars($notification['task_title']); ?></p>
                    <small>Created: <?php echo htmlspecialchars($notification['created_at']); ?></small>
                    
                    <?php if(!$notification['is_read']): ?>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="mark_read" value="1">
                            <input type="hidden" name="notification_id" value="<?php echo (int)$notification['id']; ?>">
                            <button type="submit" class="btn-link">Mark as Read</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
            <?php $stmt->close(); ?>
        </div>
        
        <p><a href="index.php">Back to Home</a></p>
    </div>
</body>
</html>
