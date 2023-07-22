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

if (!file_exists('db_connection.php')) {
    header("Location: install/install.php");
    exit;
}
// Start the session
session_start();

// Establish database connection
include "db_connection.php";

// Check if the login form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the username and password from the form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Authenticate the user
    if (authenticateUser($username, $password, $conn)) {
        // User is authenticated, set the session variable
        $_SESSION['authenticated'] = true;

        // Redirect the user to the protected page
        header("Location: index.php");
        exit;
    } else {
        // Invalid login credentials, display an error message
        $error = "Invalid login credentials. Please try again.";
    }
}

// Function to authenticate the user
function authenticateUser($username, $password, $conn)
{
    // Execute a database query to check the user
    $query = "SELECT * FROM users_app WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if a result was found
    if ($result->num_rows > 0) {
        // User was found, check the password
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Password is correct, user is authenticated
            return true;
        }
    }

    // User was not found or password is incorrect
    return false;
}
?>

<!-- HTML code for the Login page -->
<html>

<head>
    <title>Employee Attendance Tracker - Login Employee</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
        }

        h1 {
            text-align: center;
            margin-top: 50px;
        }

        form {
            max-width: 300px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 3px;
            box-sizing: border-box;
        }

        input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: #fff;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        p.error-message {
            color: #ff0000;
            text-align: center;
            margin-top: 10px;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #333;
            color: white;
            padding: 15px;
        }

        .navbar .logo img {
            width: 180px;
        }

        .navbar .menu-links {
            display: flex;
            gap: 15px;
        }

        .navbar .menu-links a {
            color: white;
            text-decoration: none;
        }

        .navbar .menu-links a:hover {
            color: #ddd;
        }

        .navbar .back-button {
            background-color: #4caf50;
            /* Green */
            border: none;
            color: white;
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
        }

        footer {
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #333;
            color: white;
            padding: 15px;
        }

        footer p {
            margin: 0;
            text-align: center;
        }

        footer a {
            color: white;
            text-decoration: none;
        }

        footer a:hover {
            color: #ddd;
        }
    </style>
</head>

<body>
    <div class="navbar">
        <div class="logo">
            <a href="/"><img src="/admin/logo.png" alt="Logo"></a>
        </div>
        <div class="menu-links">
            <a href="https://mewdev.com/employee-attendance-tracker/" target="_blank">About</a>
            <a href="https://mewdev.com/#contact" target="_blank">Support</a>
            <a href="https://fr-be.trustpilot.com/review/mewdev.com" target="_blank">Review</a>
        </div>
        <div>
            <a class="back-button" href="/admin">Admin Area</a>
        </div>
    </div>

    <h1>Login Employee</h1>
    <?php if (isset($error)) { ?>
        <p class="error-message"><?php echo $error; ?></p>
    <?php } ?>
    <form method="post" action="">
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>
        <br>
        <input type="submit" value="Login">
    </form>
</body>
<footer>
    <p>&copy; <?php echo date("Y"); ?> Mewdev All rights reserved. Developed by <a href="https://www.mewdev.com" target="_blank">Mewdev.com</a></p>
</footer>

</html>