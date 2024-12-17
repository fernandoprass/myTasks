<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Strict'
]);
require_once '../includes/config.php';

class NotificationController {
    private $conn;
    private $user_id;
    private const MAX_NOTIFICATIONS_PER_PAGE = 50;
    
    public function __construct($conn) {
        $this->conn = $conn;
        
        // Ensure user is authenticated
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../views/login.php');
            exit();
        }
        $this->user_id = $_SESSION['user_id'];
    }
    
    /**
     * Get paginated notifications for the current user
     * @param int $page Page number (1-based)
     * @param bool $unread_only Filter for unread notifications only
     * @return array Array containing notifications and pagination info
     */
    public function getNotifications($page = 1, $unread_only = false) {
        try {
            $page = max(1, (int)$page);
            $offset = ($page - 1) * self::MAX_NOTIFICATIONS_PER_PAGE;
            
            // Build base query with prepared statement
            $query = "SELECT n.*, t.title as task_title 
                     FROM notifications n 
                     JOIN tasks t ON n.task_id = t.id 
                     WHERE n.user_id = ?";
            
            $params = [$this->user_id];
            $types = "i";
            
            if ($unread_only) {
                $query .= " AND n.is_read = FALSE";
            }
            
            // Get total count for pagination
            $count_stmt = $this->conn->prepare(str_replace("n.*, t.title as task_title", "COUNT(*) as total", $query));
            $count_stmt->bind_param($types, ...$params);
            $count_stmt->execute();
            $total = $count_stmt->get_result()->fetch_assoc()['total'];
            $count_stmt->close();
            
            // Get paginated results
            $query .= " ORDER BY n.created_at DESC LIMIT ? OFFSET ?";
            $params[] = self::MAX_NOTIFICATIONS_PER_PAGE;
            $params[] = $offset;
            $types .= "ii";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $notifications = [];
            while ($row = $result->fetch_assoc()) {
                // Sanitize data before returning
                $notifications[] = array_map('htmlspecialchars', $row);
            }
            
            $stmt->close();
            
            return [
                'notifications' => $notifications,
                'total' => $total,
                'pages' => ceil($total / self::MAX_NOTIFICATIONS_PER_PAGE),
                'current_page' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Error fetching notifications: " . $e->getMessage());
            throw new Exception("Failed to fetch notifications. Please try again.");
        }
    }
    
    /**
     * Mark a notification as read
     * @param int $notification_id ID of the notification to mark as read
     * @return bool True if successful, false otherwise
     * @throws Exception if unauthorized or operation fails
     */
    public function markAsRead($notification_id) {
        try {
            // Validate notification_id
            $notification_id = filter_var($notification_id, FILTER_VALIDATE_INT);
            if (!$notification_id) {
                throw new Exception("Invalid notification ID");
            }
            
            // Verify ownership and update status
            $stmt = $this->conn->prepare(
                "UPDATE notifications 
                 SET is_read = TRUE, 
                     updated_at = CURRENT_TIMESTAMP 
                 WHERE id = ? 
                 AND user_id = ? 
                 AND is_read = FALSE"
            );
            
            $stmt->bind_param("ii", $notification_id, $this->user_id);
            $success = $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            if (!$success) {
                throw new Exception("Failed to update notification");
            }
            
            if ($affected === 0) {
                // Either notification doesn't exist, isn't owned by user, or already read
                throw new Exception("Notification not found or already read");
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            throw new Exception("Failed to mark notification as read. Please try again.");
        }
    }
    
    /**
     * Create a new notification
     * @param int $task_id Associated task ID
     * @param string $message Notification message
     * @param int $target_user_id User to notify
     * @return bool True if successful
     * @throws Exception if creation fails
     */
    public function createNotification($task_id, $message, $target_user_id) {
        try {
            // Validate inputs
            $task_id = filter_var($task_id, FILTER_VALIDATE_INT);
            $target_user_id = filter_var($target_user_id, FILTER_VALIDATE_INT);
            if (!$task_id || !$target_user_id || empty(trim($message))) {
                throw new Exception("Invalid notification parameters");
            }
            
            // Verify task exists and user has access to it
            $stmt = $this->conn->prepare(
                "SELECT 1 FROM tasks WHERE id = ? AND (user_id = ? OR user_id = ?)"
            );
            $stmt->bind_param("iii", $task_id, $this->user_id, $target_user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                $stmt->close();
                throw new Exception("Invalid task or unauthorized access");
            }
            $stmt->close();
            
            // Create notification
            $stmt = $this->conn->prepare(
                "INSERT INTO notifications (task_id, user_id, message, created_by) 
                 VALUES (?, ?, ?, ?)"
            );
            
            $stmt->bind_param("iisi", 
                $task_id,
                $target_user_id,
                $message,
                $this->user_id
            );
            
            $success = $stmt->execute();
            $stmt->close();
            
            if (!$success) {
                throw new Exception("Failed to create notification");
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error creating notification: " . $e->getMessage());
            throw new Exception("Failed to create notification. Please try again.");
        }
    }
}

// Handle POST actions with CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Invalid security token");
        }
        
        $notification = new NotificationController($conn);
        
        if (isset($_POST['mark_read'])) {
            $notification->markAsRead($_POST['notification_id']);
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }
        
        if (isset($_POST['create_notification'])) {
            $notification->createNotification(
                $_POST['task_id'],
                $_POST['message'],
                $_POST['target_user_id']
            );
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}
?>
