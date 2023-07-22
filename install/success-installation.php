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


if (!file_exists('../db_connection.php')) {
    header("Location: install.php");
    exit;
}
// Establish database connection
include "../db_connection.php";

// Admin-Benutzer abrufen
$adminQuery = "SELECT username FROM users WHERE id = 1";
$adminResult = $conn->query($adminQuery);
$adminRow = $adminResult->fetch_assoc();
$adminUsername = $adminRow['username'];

// Employee-Benutzer abrufen
$employeeQuery = "SELECT username FROM users_app WHERE id = 1";
$employeeResult = $conn->query($employeeQuery);
$employeeRow = $employeeResult->fetch_assoc();
$employeeUsername = $employeeRow['username'];

?>

<!DOCTYPE html>
<html>

<head>
    <title>Installation Successful - Mewdev Employee Attendance Tracker</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .navbar {
            background-color: #333;
            color: #fff;
            padding: 10px;
        }

        .navbar a {
            color: #fff;
            text-decoration: none;
            margin-right: 10px;
        }

        h1 {
            margin-top: 40px;
            text-align: center;
        }

        .container {
            width: 80%;
            margin: 0 auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }

        th,
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #333;
            color: #fff;
            text-align: left;
        }

        .footer {
            background-color: #333;
            color: #fff;
            padding: 10px;
            text-align: center;
            margin-top: 30px;
        }

        .footer a {
            color: white;
            text-decoration: none;
        }

        .footer a:hover {
            color: #ddd;
        }

        .delete-install {
            background-color: #f2dede;
            color: #a94442;
            padding: 10px;
            border: 1px solid #ebccd1;
            border-radius: 4px;
            margin-top: 20px;
        }

        .logo {
            width: 50%;
        }
    </style>
</head>

<body>
    <div class="navbar">
        <div class="container">
            <a href="/"><img class="logo" src="/admin/logo.png" alt="Logo"></a>
            <a href="https://mewdev.com/employee-attendance-tracker/" target="_blank">About</a>
            <a href="https://mewdev.com/#contact" target="_blank">Support</a>
            <a href="https://fr-be.trustpilot.com/review/mewdev.com" target="_blank">Review</a>
        </div>
    </div>

    <div class="container">
        <h1>Installation was Successful</h1>
        <p>Thank you for using Mewdev Employee Attendance Tracker!</p>
        <p class="delete-install"><b>Please make sure to delete the /install folder for security purposes.</b></p>

        <h2>Admin Area:</h2>
        <table>
            <tr>
                <th>URL:</th>
                <td><a href="/admin/index.php">/admin/index.php</a></td>
            </tr>
            <tr>
                <th>Username:</th>
                <td><?php echo $adminUsername; ?></td>
            </tr>
            <tr>
                <th>Password:</th>
                <td>[Hashed Password]</td>
            </tr>
        </table>

        <h2>Employee Area:</h2>
        <table>
            <tr>
                <th>URL:</th>
                <td><a href="/index.php">/index.php</a></td>
            </tr>
            <tr>
                <th>Username:</th>
                <td><?php echo $employeeUsername; ?></td>
            </tr>
            <tr>
                <th>Password:</th>
                <td>[Hashed Password]</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        &copy; <?php echo date("Y"); ?> Mewdev All rights reserved. Developed by <a href="https://www.mewdev.com" target="_blank">Mewdev.com</a>
    </div>
</body>

</html>