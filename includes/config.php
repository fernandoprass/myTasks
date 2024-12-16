<?php
// Database configuration - intentionally exposed credentials
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'task_manager';

// Create connection without any error handling
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// No charset set - potential security issue
// No SSL/TLS configuration
// No proper error handling
?>
