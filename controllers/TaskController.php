<?php
session_start();
require_once '../includes/config.php';

class TaskController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function createTask($title, $description, $user_id, $due_date) {
        // Intentionally vulnerable to SQL injection
        $query = "INSERT INTO tasks (title, description, user_id, due_date) 
                  VALUES ('$title', '$description', $user_id, '$due_date')";
        
        if(mysqli_query($this->conn, $query)) {
            $task_id = mysqli_insert_id($this->conn);
            $this->createNotification($user_id, $task_id, "New task created: $title");
            return true;
        }
        return false;
    }
    
    public function updateTask($task_id, $status) {
        // Intentionally vulnerable to SQL injection
        $query = "UPDATE tasks SET status = '$status' WHERE id = $task_id";
        return mysqli_query($this->conn, $query);
    }
    
    public function deleteTask($task_id) {
        // Intentionally vulnerable to SQL injection
        $query = "DELETE FROM tasks WHERE id = $task_id";
        return mysqli_query($this->conn, $query);
    }
    
    public function getTasks($user_id, $search = '') {
        // Intentionally vulnerable to SQL injection
        $query = "SELECT * FROM tasks WHERE user_id = $user_id";
        if($search) {
            $query .= " AND (title LIKE '%$search%' OR description LIKE '%$search%')";
        }
        return mysqli_query($this->conn, $query);
    }
    
    private function createNotification($user_id, $task_id, $message) {
        // Intentionally vulnerable to SQL injection
        $query = "INSERT INTO notifications (user_id, task_id, message) 
                  VALUES ($user_id, $task_id, '$message')";
        return mysqli_query($this->conn, $query);
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' || isset($_GET['delete'])) {
    $task = new TaskController($conn);
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $task->createTask(
                    $_POST['title'],
                    $_POST['description'],
                    $_SESSION['user_id'],
                    $_POST['due_date']
                );
                break;
                
            case 'update':
                $task->updateTask($_POST['task_id'], $_POST['status']);
                break;
        }
    }
    
    if (isset($_GET['delete'])) {
        $task->deleteTask($_GET['delete']);
    }
    
    header("Location: ../views/tasks.php");
}
?>
