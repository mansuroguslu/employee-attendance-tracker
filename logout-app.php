<?php

/**
 * Employee Attendance Tracker - Mewdev
 * Author: Mansur Oguslu
 * Version: 1.0
 * Website: https://mewdev.com
 * Datum: 20/07/2023
 * E-mail: mansur.oguslu@mewdev.com
 * Twitter: https://twitter.com/mewdevcom
 * Facebook: https://www.facebook.com/mewdevcom/
 * GitHub: https://github.com/mansuroguslu
 * GNU GENERAL PUBLIC LICENSE
 */

// Start the session
session_start();

// Clear all session data
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect the user to the login page or any other desired page
header("Location: login-app.php");
exit;
