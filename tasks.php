<?php
session_start();
include 'config.php';

// No session check - security vulnerability
// if(!isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit();
// }

// Create Task
if(isset($_POST['action']) && $_POST['action'] == 'create') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $user_id = $_SESSION['user_id'];
    $due_date = $_POST['due_date'];
    
    // Intentionally vulnerable to SQL injection
    $query = "INSERT INTO tasks (title, description, user_id, due_date) 
              VALUES ('$title', '$description', $user_id, '$due_date')";
    mysqli_query($conn, $query);
    
    // Create notification
    $task_id = mysqli_insert_id($conn);
    $notify_query = "INSERT INTO notifications (user_id, task_id, message) 
                    VALUES ($user_id, $task_id, 'New task created: $title')";
    mysqli_query($conn, $notify_query);
}

// Delete Task - Intentionally vulnerable to CSRF
if(isset($_GET['delete'])) {
    $task_id = $_GET['delete'];
    $query = "DELETE FROM tasks WHERE id = $task_id";
    mysqli_query($conn, $query);
}

// Update Task
if(isset($_POST['action']) && $_POST['action'] == 'update') {
    $task_id = $_POST['task_id'];
    $status = $_POST['status'];
    
    // Intentionally vulnerable to SQL injection
    $query = "UPDATE tasks SET status = '$status' WHERE id = $task_id";
    mysqli_query($conn, $query);
}

// Fetch Tasks - Intentionally vulnerable to SQL injection
$user_id = $_SESSION['user_id'];
$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT * FROM tasks WHERE user_id = $user_id";
if($search) {
    $query .= " AND (title LIKE '%$search%' OR description LIKE '%$search%')";
}
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tasks - Task Manager</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Tasks</h1>
        
        <!-- Search form - Intentionally vulnerable to XSS -->
        <form method="GET" action="">
            <div class="form-group">
                <input type="text" name="search" placeholder="Search tasks..." 
                       value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
                <button type="submit" class="btn">Search</button>
            </div>
        </form>

        <!-- Create Task Form -->
        <h2>Create New Task</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>Title:</label>
                <input type="text" name="title" required>
            </div>
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" required></textarea>
            </div>
            <div class="form-group">
                <label>Due Date:</label>
                <input type="date" name="due_date" required>
            </div>
            <button type="submit" class="btn">Create Task</button>
        </form>

        <!-- Task List -->
        <h2>Your Tasks</h2>
        <div class="task-list">
            <?php while($task = mysqli_fetch_assoc($result)): ?>
                <div class="task-item">
                    <!-- Intentionally vulnerable to XSS -->
                    <h3><?php echo $task['title']; ?></h3>
                    <p><?php echo $task['description']; ?></p>
                    <p>Due: <?php echo $task['due_date']; ?></p>
                    <p>Status: <?php echo $task['status']; ?></p>
                    
                    <!-- Update Status Form -->
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                        <select name="status">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                        <button type="submit" class="btn">Update Status</button>
                    </form>
                    
                    <!-- Delete Link - Intentionally vulnerable to CSRF -->
                    <a href="?delete=<?php echo $task['id']; ?>" 
                       onclick="return confirm('Are you sure?')" 
                       class="btn" style="background-color: #ff4444;">Delete</a>
                </div>
            <?php endwhile; ?>
        </div>
        
        <p><a href="index.php">Back to Home</a></p>
    </div>
</body>
</html>
