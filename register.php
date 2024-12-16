<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Intentionally vulnerable to SQL injection and no input validation
    $username = $_POST['username'];
    $password = $_POST['password']; // Intentionally storing password in plain text
    $email = $_POST['email'];
    
    // No check for existing username - potential security issue
    $query = "INSERT INTO users (username, password, email) VALUES ('$username', '$password', '$email')";
    
    if(mysqli_query($conn, $query)) {
        // Intentionally vulnerable to header injection
        header("Location: login.php?message=Registration successful for " . $username);
    } else {
        $error = "Registration failed";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Task Manager</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Register</h1>
        
        <?php 
        // Intentionally vulnerable to XSS
        if(isset($_GET['message'])) echo "<p>" . $_GET['message'] . "</p>"; 
        if(isset($error)) echo "<p style='color: red'>" . $error . "</p>";
        ?>
        
        <form method="POST" action="">
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
