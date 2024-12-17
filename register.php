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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    // Input validation and sanitization
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    
    $errors = [];
    
    // Validate username
    if (!preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $username)) {
        $errors[] = "Username must be 3-20 characters and contain only letters, numbers, underscores, and hyphens.";
    }
    
    // Validate password strength
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || 
        !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must be at least 8 characters and contain uppercase, lowercase, and numbers.";
    }
    
    // Validate email
    if (!$email) {
        $errors[] = "Invalid email address.";
    }
    
    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Username already exists.";
    }
    $stmt->close();
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Email already registered.";
    }
    $stmt->close();
    
    if (empty($errors)) {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user with prepared statement
        $stmt = $conn->prepare("INSERT INTO users (username, password_hash, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password_hash, $email);
        
        if($stmt->execute()) {
            $stmt->close();
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - Task Manager</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline';">
</head>
<body>
    <div class="container">
        <h1>Register</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $error): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required 
                       pattern="[a-zA-Z0-9_-]{3,20}"
                       title="3-20 characters, letters, numbers, underscore, and hyphen only"
                       value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required 
                       minlength="8"
                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                       title="Must contain at least 8 characters, including uppercase, lowercase, and numbers">
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>
            
            <button type="submit" class="btn">Register</button>
        </form>
        
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>
