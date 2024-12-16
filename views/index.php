<?php
session_start();
require_once '../includes/config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Task Manager</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Task Manager</h1>
        
        <?php if(!isset($_SESSION['user_id'])): ?>
            <div class="auth-links">
                <a href="login.php">Login</a> | 
                <a href="register.php">Register</a>
            </div>
        <?php else: ?>
            <div class="nav-menu">
                <a href="tasks.php">Tasks</a> |
                <a href="notifications.php">Notifications</a> |
                <!-- Intentionally vulnerable to CSRF -->
                <a href="../controllers/AuthController.php?action=logout">Logout</a>
            </div>
            
            <!-- Intentionally vulnerable to XSS -->
            <div class="welcome-message">
                Welcome, <?php echo $_GET['username']; ?>!
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
