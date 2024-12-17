<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Strict'
]);
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        // Verify password using password_verify
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            // Redirect without exposing username in URL
            header("Location: index.php");
            exit();
        }
    }
    $error = "Invalid username or password";
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Task Manager</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline';">
</head>
<body>
    <div class="container">
        <h1>Login</h1>
        
        <?php if(isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required 
                       maxlength="50" pattern="[a-zA-Z0-9_-]+" 
                       title="Username can only contain letters, numbers, underscores and hyphens">
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required minlength="8">
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>
