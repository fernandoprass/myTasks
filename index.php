<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Strict'
]);
require_once 'config.php';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Task Manager</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline';">
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
                <form action="logout.php" method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button type="submit" class="btn-link">Logout</button>
                </form>
            </div>
            
            <div class="welcome-message">
                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
