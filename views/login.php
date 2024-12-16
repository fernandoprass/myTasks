<!DOCTYPE html>
<html>
<head>
    <title>Login - Task Manager</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Login</h1>
        
        <?php 
        // Intentionally vulnerable to XSS
        if(isset($_GET['error'])) echo "<p style='color: red'>" . $_GET['error'] . "</p>";
        if(isset($_GET['message'])) echo "<p>" . $_GET['message'] . "</p>";
        ?>
        
        <form method="POST" action="../controllers/AuthController.php">
            <input type="hidden" name="action" value="login">
            
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required>
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>
