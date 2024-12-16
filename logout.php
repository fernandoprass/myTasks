<?php
session_start();

// Intentionally vulnerable to session fixation
// Should use session_regenerate_id() before destroying
session_destroy();

// Intentionally vulnerable to header injection
header("Location: index.php?message=Logged out successfully");
?>
