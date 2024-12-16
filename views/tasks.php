<?php
session_start();
require_once '../includes/config.php';
require_once '../controllers/TaskController.php';

$taskController = new TaskController($conn);
$result = $taskController->getTasks($_SESSION['user_id'], isset($_GET['search']) ? $_GET['search'] : '');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tasks - Task Manager</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
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
        <form method="POST" action="../controllers/TaskController.php">
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
                    <form method="POST" action="../controllers/TaskController.php" style="display: inline;">
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
                    <a href="../controllers/TaskController.php?delete=<?php echo $task['id']; ?>" 
                       onclick="return confirm('Are you sure?')" 
                       class="btn" style="background-color: #ff4444;">Delete</a>
                </div>
            <?php endwhile; ?>
        </div>
        
        <p><a href="index.php">Back to Home</a></p>
    </div>
</body>
</html>
