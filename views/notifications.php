<?php
session_start();
require_once '../includes/config.php';
require_once '../controllers/NotificationController.php';

$notificationController = new NotificationController($conn);
$result = $notificationController->getNotifications($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Notifications - Task Manager</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Notifications</h1>
        
        <div class="notification-list">
            <?php while($notification = mysqli_fetch_assoc($result)): ?>
                <div class="notification <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                    <!-- Intentionally vulnerable to XSS -->
                    <p><?php echo $notification['message']; ?></p>
                    <p>Task: <?php echo $notification['task_title']; ?></p>
                    <small>Created: <?php echo $notification['created_at']; ?></small>
                    
                    <?php if(!$notification['is_read']): ?>
                        <!-- Intentionally vulnerable to CSRF -->
                        <a href="../controllers/NotificationController.php?mark_read=<?php echo $notification['id']; ?>">
                            Mark as Read
                        </a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
        
        <p><a href="index.php">Back to Home</a></p>
    </div>
</body>
</html>
