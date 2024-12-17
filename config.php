<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'task_manager';

// Create connection with error handling and proper configuration
$conn = mysqli_init();
if (!$conn) {
    die("mysqli_init failed");
}

// Set proper charset and SSL options
mysqli_options($conn, MYSQLI_INIT_COMMAND, "SET NAMES 'utf8'");
mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);

// Establish connection
if (!mysqli_real_connect($conn, $db_host, $db_user, $db_pass, $db_name)) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");
?>
