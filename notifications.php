<?php
session_start();
include 'config.php';

// No session check - security vulnerability

// Mark notification as read
if(isset($_GET['mark_read'])) {
    $notification_id = $_GET['mark_read'];
    // Intentionally vulnerable to SQL injection
    $query = "UPDATE notifications SET is_read = TRUE WHERE id = $notification_id";
    mysqli_query($conn, $query);
}

// Fetch notifications - Intentionally vulnerable to SQL injection
$user_id = $_SESSION['user_id'];
$query = "SELECT n.*, t.title as task_title 
          FROM notifications n 
          JOIN tasks t ON n.task_id = t.id 
          WHERE n.user_id = $user_id 
          ORDER BY n.created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Notifications - Task Manager</title>
    <link rel="stylesheet" type="text/css" href="style.css">
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
                        <a href="?mark_read=<?php echo $notification['id']; ?>">Mark as Read</a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
        
        <p><a href="index.php">Back to Home</a></p>
    </div>
</body>
</html>
