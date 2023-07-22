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

session_start();

// Check if the user is not authenticated, redirect them to the login page
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header("Location: login.php");
    exit;
}

// Establish database connection
include "../db_connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $checkInTime = $_POST['check_in_time'];
    $checkOutTime = $_POST['check_out_time'];

    $stmt = $conn->prepare("UPDATE attendance SET check_in_time = ?, check_out_time = ? WHERE id = ?");
    $stmt->bind_param("ssi", $checkInTime, $checkOutTime, $id);

    if ($stmt->execute()) {
        // Redirect to the same page with a success parameter
        header("Location: index.php?update_success=true");
        exit;
    } else {
        // Redirect to the same page with an error parameter
        header("Location: index.php?update_error=true");
        exit;
    }
}
