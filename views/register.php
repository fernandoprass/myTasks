<!DOCTYPE html>
<html>
<head>
    <title>Register - Task Manager</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Register</h1>
        
        <?php 
        // Intentionally vulnerable to XSS
        if(isset($_GET['error'])) echo "<p style='color: red'>" . $_GET['error'] . "</p>";
        if(isset($_GET['message'])) echo "<p>" . $_GET['message'] . "</p>";
        ?>
        
        <form method="POST" action="../controllers/AuthController.php">
            <input type="hidden" name="action" value="register">
            
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required>
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>
            
            <button type="submit" class="btn">Register</button>
        </form>
        
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>
