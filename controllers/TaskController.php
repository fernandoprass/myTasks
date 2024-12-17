<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Strict'
]);
require_once '../includes/config.php';
require_once 'NotificationController.php';

class TaskController {
    private $conn;
    private $user_id;
    private $notification;
    private const MAX_TASKS_PER_PAGE = 20;
    private const ALLOWED_STATUSES = ['pending', 'in_progress', 'completed'];
    
    public function __construct($conn) {
        $this->conn = $conn;
        
        // Ensure user is authenticated
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../views/login.php');
            exit();
        }
        
        $this->user_id = $_SESSION['user_id'];
        $this->notification = new NotificationController($conn);
    }
    
    /**
     * Create a new task
     * @param string $title Task title
     * @param string $description Task description
     * @param string $due_date Due date in Y-m-d format
     * @return int New task ID
     * @throws Exception if validation fails or creation fails
     */
    public function createTask($title, $description, $due_date) {
        try {
            // Validate inputs
            $title = trim($title);
            $description = trim($description);
            
            if (empty($title) || strlen($title) > 255) {
                throw new Exception("Title must be between 1 and 255 characters");
            }
            
            if (strlen($description) > 1000) {
                throw new Exception("Description must not exceed 1000 characters");
            }
            
            // Validate due date
            $due_date_obj = DateTime::createFromFormat('Y-m-d', $due_date);
            if (!$due_date_obj || $due_date_obj->format('Y-m-d') !== $due_date) {
                throw new Exception("Invalid due date format");
            }
            
            // Insert task with prepared statement
            $stmt = $this->conn->prepare(
                "INSERT INTO tasks (title, description, user_id, due_date, status, created_at) 
                 VALUES (?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)"
            );
            
            $stmt->bind_param("ssis", $title, $description, $this->user_id, $due_date);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create task");
            }
            
            $task_id = $stmt->insert_id;
            $stmt->close();
            
            // Create notification
            $this->notification->createNotification(
                $task_id,
                "New task created: " . htmlspecialchars($title),
                $this->user_id
            );
            
            return $task_id;
            
        } catch (Exception $e) {
            error_log("Error creating task: " . $e->getMessage());
            throw new Exception("Failed to create task. Please try again.");
        }
    }
    
    /**
     * Update task status
     * @param int $task_id Task ID
     * @param string $status New status
     * @return bool True if successful
     * @throws Exception if validation fails or update fails
     */
    public function updateTask($task_id, $status) {
        try {
            // Validate task_id
            $task_id = filter_var($task_id, FILTER_VALIDATE_INT);
            if (!$task_id) {
                throw new Exception("Invalid task ID");
            }
            
            // Validate status
            if (!in_array($status, self::ALLOWED_STATUSES)) {
                throw new Exception("Invalid status");
            }
            
            // Update with prepared statement and ownership check
            $stmt = $this->conn->prepare(
                "UPDATE tasks 
                 SET status = ?, 
                     updated_at = CURRENT_TIMESTAMP 
                 WHERE id = ? AND user_id = ?"
            );
            
            $stmt->bind_param("sii", $status, $task_id, $this->user_id);
            $success = $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            if (!$success) {
                throw new Exception("Failed to update task");
            }
            
            if ($affected === 0) {
                throw new Exception("Task not found or unauthorized");
            }
            
            // Create status change notification
            $this->notification->createNotification(
                $task_id,
                "Task status updated to: $status",
                $this->user_id
            );
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error updating task: " . $e->getMessage());
            throw new Exception("Failed to update task. Please try again.");
        }
    }
    
    /**
     * Delete a task
     * @param int $task_id Task ID to delete
     * @return bool True if successful
     * @throws Exception if validation fails or deletion fails
     */
    public function deleteTask($task_id) {
        try {
            // Validate task_id
            $task_id = filter_var($task_id, FILTER_VALIDATE_INT);
            if (!$task_id) {
                throw new Exception("Invalid task ID");
            }
            
            // Start transaction
            $this->conn->begin_transaction();
            
            try {
                // Delete notifications first
                $stmt = $this->conn->prepare("DELETE FROM notifications WHERE task_id = ? AND user_id = ?");
                $stmt->bind_param("ii", $task_id, $this->user_id);
                $stmt->execute();
                $stmt->close();
                
                // Delete task with ownership check
                $stmt = $this->conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $task_id, $this->user_id);
                $success = $stmt->execute();
                $affected = $stmt->affected_rows;
                $stmt->close();
                
                if (!$success || $affected === 0) {
                    throw new Exception("Task not found or unauthorized");
                }
                
                $this->conn->commit();
                return true;
                
            } catch (Exception $e) {
                $this->conn->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Error deleting task: " . $e->getMessage());
            throw new Exception("Failed to delete task. Please try again.");
        }
    }
    
    /**
     * Get paginated tasks with optional search
     * @param int $page Page number (1-based)
     * @param string $search Optional search term
     * @return array Array containing tasks and pagination info
     */
    public function getTasks($page = 1, $search = '') {
        try {
            $page = max(1, (int)$page);
            $offset = ($page - 1) * self::MAX_TASKS_PER_PAGE;
            
            // Build base query
            $query = "SELECT * FROM tasks WHERE user_id = ?";
            $params = [$this->user_id];
            $types = "i";
            
            // Add search condition if provided
            if ($search) {
                $search = "%" . $search . "%";
                $query .= " AND (title LIKE ? OR description LIKE ?)";
                $params[] = $search;
                $params[] = $search;
                $types .= "ss";
            }
            
            // Get total count for pagination
            $count_stmt = $this->conn->prepare(str_replace("*", "COUNT(*) as total", $query));
            $count_stmt->bind_param($types, ...$params);
            $count_stmt->execute();
            $total = $count_stmt->get_result()->fetch_assoc()['total'];
            $count_stmt->close();
            
            // Get paginated results
            $query .= " ORDER BY due_date ASC, created_at DESC LIMIT ? OFFSET ?";
            $params[] = self::MAX_TASKS_PER_PAGE;
            $params[] = $offset;
            $types .= "ii";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $tasks = [];
            while ($row = $result->fetch_assoc()) {
                // Sanitize data before returning
                $tasks[] = array_map('htmlspecialchars', $row);
            }
            
            $stmt->close();
            
            return [
                'tasks' => $tasks,
                'total' => $total,
                'pages' => ceil($total / self::MAX_TASKS_PER_PAGE),
                'current_page' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Error fetching tasks: " . $e->getMessage());
            throw new Exception("Failed to fetch tasks. Please try again.");
        }
    }
}

// Handle form submissions with CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Invalid security token");
        }
        
        $task = new TaskController($conn);
        
        switch ($_POST['action'] ?? '') {
            case 'create':
                $task_id = $task->createTask(
                    $_POST['title'],
                    $_POST['description'],
                    $_POST['due_date']
                );
                $_SESSION['success'] = "Task created successfully";
                break;
                
            case 'update':
                $task->updateTask($_POST['task_id'], $_POST['status']);
                $_SESSION['success'] = "Task updated successfully";
                break;
                
            case 'delete':
                $task->deleteTask($_POST['task_id']);
                $_SESSION['success'] = "Task deleted successfully";
                break;
                
            default:
                throw new Exception("Invalid action");
        }
        
        header("Location: ../views/tasks.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}
?>
