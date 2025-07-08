<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Redirect to user login page
header("Location: user_login.php");
exit();
?>
