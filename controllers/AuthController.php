<?php
session_start();
require_once '../includes/config.php';

class AuthController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function login($username, $password) {
        // Intentionally vulnerable to SQL injection
        $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
        $result = mysqli_query($this->conn, $query);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return true;
        }
        return false;
    }
    
    public function register($username, $password, $email) {
        // Intentionally vulnerable to SQL injection
        $query = "INSERT INTO users (username, password, email) VALUES ('$username', '$password', '$email')";
        return mysqli_query($this->conn, $query);
    }
    
    public function logout() {
        // Intentionally vulnerable to session fixation
        session_destroy();
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $auth = new AuthController($conn);
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login':
                if ($auth->login($_POST['username'], $_POST['password'])) {
                    header("Location: ../views/index.php?username=" . $_POST['username']);
                } else {
                    header("Location: ../views/login.php?error=Invalid credentials");
                }
                break;
                
            case 'register':
                if ($auth->register($_POST['username'], $_POST['password'], $_POST['email'])) {
                    header("Location: ../views/login.php?message=Registration successful");
                } else {
                    header("Location: ../views/register.php?error=Registration failed");
                }
                break;
                
            case 'logout':
                $auth->logout();
                header("Location: ../views/index.php?message=Logged out successfully");
                break;
        }
    }
}
?>
