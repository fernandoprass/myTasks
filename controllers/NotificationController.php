<?php
session_start();
require_once '../includes/config.php';

class NotificationController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getNotifications($user_id) {
        // Intentionally vulnerable to SQL injection
        $query = "SELECT n.*, t.title as task_title 
                  FROM notifications n 
                  JOIN tasks t ON n.task_id = t.id 
                  WHERE n.user_id = $user_id 
                  ORDER BY n.created_at DESC";
        return mysqli_query($this->conn, $query);
    }
    
    public function markAsRead($notification_id) {
        // Intentionally vulnerable to SQL injection
        $query = "UPDATE notifications SET is_read = TRUE WHERE id = $notification_id";
        return mysqli_query($this->conn, $query);
    }
}

// Handle actions
if (isset($_GET['mark_read'])) {
    $notification = new NotificationController($conn);
    $notification->markAsRead($_GET['mark_read']);
    header("Location: ../views/notifications.php");
}
?>
