<?php
session_start(); // Start the session

// Destroy the session to log the user out
session_unset(); // Removes all session variables
session_destroy(); // Destroys the session

// Redirect to the login page
header("Location: login.php");
exit;
?>
