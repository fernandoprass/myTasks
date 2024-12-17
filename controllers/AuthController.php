<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Strict'
]);
require_once '../includes/config.php';

class AuthController {
    private $conn;
    private $max_login_attempts = 5;
    private $lockout_time = 900; // 15 minutes in seconds
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    private function checkLoginAttempts($username) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as attempts, MAX(attempt_time) as last_attempt 
                                    FROM login_attempts 
                                    WHERE username = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result['attempts'] >= $this->max_login_attempts) {
            $time_remaining = $this->lockout_time - (time() - strtotime($result['last_attempt']));
            if ($time_remaining > 0) {
                throw new Exception("Account temporarily locked. Try again in " . ceil($time_remaining / 60) . " minutes.");
            }
        }
        return true;
    }
    
    private function recordLoginAttempt($username, $success) {
        $stmt = $this->conn->prepare("INSERT INTO login_attempts (username, success) VALUES (?, ?)");
        $stmt->bind_param("si", $username, $success);
        $stmt->execute();
        $stmt->close();
        
        if ($success) {
            // Clear previous attempts on successful login
            $stmt = $this->conn->prepare("DELETE FROM login_attempts WHERE username = ? AND success = 0");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    public function login($username, $password) {
        try {
            // Validate input
            $username = trim($username);
            if (empty($username) || empty($password)) {
                throw new Exception("Username and password are required.");
            }
            
            // Check login attempts
            $this->checkLoginAttempts($username);
            
            // Get user with prepared statement
            $stmt = $this->conn->prepare("SELECT id, username, password_hash, is_active FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Check if account is active
                if (!$user['is_active']) {
                    throw new Exception("Account is deactivated. Please contact support.");
                }
                
                // Verify password
                if (password_verify($password, $user['password_hash'])) {
                    // Record successful login
                    $this->recordLoginAttempt($username, 1);
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);
                    
                    return true;
                }
            }
            
            // Record failed login attempt
            $this->recordLoginAttempt($username, 0);
            throw new Exception("Invalid username or password.");
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function register($username, $password, $email) {
        try {
            // Validate input
            $username = trim($username);
            $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
            
            if (!$email) {
                throw new Exception("Invalid email address.");
            }
            
            if (!preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $username)) {
                throw new Exception("Invalid username format.");
            }
            
            // Check password strength
            if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || 
                !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
                throw new Exception("Password does not meet security requirements.");
            }
            
            // Check if username exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $stmt->close();
                throw new Exception("Username already exists.");
            }
            $stmt->close();
            
            // Check if email exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $stmt->close();
                throw new Exception("Email already registered.");
            }
            $stmt->close();
            
            // Hash password and insert user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("INSERT INTO users (username, password_hash, email, is_active) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("sss", $username, $password_hash, $email);
            
            if (!$stmt->execute()) {
                throw new Exception("Registration failed. Please try again.");
            }
            
            $stmt->close();
            return true;
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function logout() {
        // Unset all session variables
        $_SESSION = array();
        
        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy the session
        session_destroy();
    }
    
    public function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ../views/login.php");
            exit();
        }
    }
    
    public function validateCSRF($token) {
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            throw new Exception("Invalid security token.");
        }
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $auth = new AuthController($conn);
        
        // Validate CSRF token for all POST requests except login
        if (isset($_POST['action']) && $_POST['action'] !== 'login') {
            $auth->validateCSRF($_POST['csrf_token'] ?? '');
        }
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'login':
                    if ($auth->login($_POST['username'], $_POST['password'])) {
                        header("Location: ../views/index.php");
                        exit();
                    }
                    break;
                    
                case 'register':
                    if ($auth->register($_POST['username'], $_POST['password'], $_POST['email'])) {
                        header("Location: ../views/login.php");
                        exit();
                    }
                    break;
                    
                case 'logout':
                    $auth->logout();
                    header("Location: ../views/login.php");
                    exit();
                    break;
                    
                default:
                    throw new Exception("Invalid action.");
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=" . urlencode($error));
        exit();
    }
}
?>
