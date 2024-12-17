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

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
}

// Create Task
if(isset($_POST['action']) && $_POST['action'] == 'create') {
    $stmt = $conn->prepare("INSERT INTO tasks (title, description, user_id, due_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", 
        $_POST['title'],
        $_POST['description'],
        $_SESSION['user_id'],
        $_POST['due_date']
    );
    
    if ($stmt->execute()) {
        $task_id = $stmt->insert_id;
        
        // Create notification using prepared statement
        $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, task_id, message) VALUES (?, ?, ?)");
        $message = 'New task created: ' . htmlspecialchars($_POST['title']);
        $notify_stmt->bind_param("iis", $_SESSION['user_id'], $task_id, $message);
        $notify_stmt->execute();
        $notify_stmt->close();
    }
    $stmt->close();
}

// Delete Task with CSRF protection
if(isset($_POST['delete'])) {
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $_POST['task_id'], $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}

// Update Task
if(isset($_POST['action']) && $_POST['action'] == 'update') {
    $allowed_statuses = ['pending', 'in_progress', 'completed'];
    if (in_array($_POST['status'], $allowed_statuses)) {
        $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sii", $_POST['status'], $_POST['task_id'], $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch Tasks with secure search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT * FROM tasks WHERE user_id = ?";
$params = [$_SESSION['user_id']];
$types = "i";

if($search) {
    $query .= " AND (title LIKE ? OR description LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tasks - Task Manager</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <!-- Add Content Security Policy -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline';">
</head>
<body>
    <div class="container">
        <h1>Tasks</h1>
        
        <!-- Search form with XSS protection -->
        <form method="GET" action="">
            <div class="form-group">
                <input type="text" name="search" placeholder="Search tasks..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn">Search</button>
            </div>
        </form>

        <!-- Create Task Form with CSRF protection -->
        <h2>Create New Task</h2>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>Title:</label>
                <input type="text" name="title" required maxlength="255">
            </div>
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" required maxlength="1000"></textarea>
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
            <?php while($task = $result->fetch_assoc()): ?>
                <div class="task-item">
                    <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                    <p><?php echo htmlspecialchars($task['description']); ?></p>
                    <p>Due: <?php echo htmlspecialchars($task['due_date']); ?></p>
                    <p>Status: <?php echo htmlspecialchars($task['status']); ?></p>
                    
                    <!-- Update Status Form with CSRF protection -->
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="task_id" value="<?php echo (int)$task['id']; ?>">
                        <select name="status">
                            <option value="pending" <?php echo $task['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $task['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                        <button type="submit" class="btn">Update Status</button>
                    </form>
                    
                    <!-- Delete Form with CSRF protection -->
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="delete" value="1">
                        <input type="hidden" name="task_id" value="<?php echo (int)$task['id']; ?>">
                        <button type="submit" class="btn" style="background-color: #ff4444;" 
                                onclick="return confirm('Are you sure?')">Delete</button>
                    </form>
                </div>
            <?php endwhile; ?>
            <?php $stmt->close(); ?>
        </div>
        
        <p><a href="index.php">Back to Home</a></p>
    </div>
</body>
</html>
