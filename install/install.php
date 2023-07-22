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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Datenbankverbindung herstellen
    $host = $_POST['host'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $database = $_POST['database'];

    $conn = new mysqli($host, $username, $password);
    if ($conn->connect_error) {
        echo "<script>";
        echo "alert('Error connecting to database.');";
        echo "window.location.href = 'install.php';";
        echo "</script>";
        exit;
    }

    // Datenbank erstellen
    $createDatabaseQuery = "CREATE DATABASE IF NOT EXISTS $database";
    if ($conn->query($createDatabaseQuery) !== TRUE) {
        die("Error creating database: " . $conn->error);
    }

    // Verbindung zur Datenbank herstellen
    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
        echo '<script>alert("Error connecting to database. Please check your database information."); location.reload();</script>';
        die();
    }

    // Tabellen erstellen, falls sie nicht vorhanden sind
    $createUsersTableQuery = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL
    )";
    if ($conn->query($createUsersTableQuery) !== TRUE) {
        die("Error creating table 'users': " . $conn->error);
    }

    $createUsersAppTableQuery = "CREATE TABLE IF NOT EXISTS users_app (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL
    )";
    if ($conn->query($createUsersAppTableQuery) !== TRUE) {
        die("Error creating table 'users_app': " . $conn->error);
    }

    $createAttendanceTableQuery = "CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id VARCHAR(50) NOT NULL,
        check_in_time DATETIME DEFAULT NULL,
        check_out_time DATETIME DEFAULT NULL
    )";
    if ($conn->query($createAttendanceTableQuery) !== TRUE) {
        die("Error creating table 'attendance': " . $conn->error);
    }

    // Admin-Benutzer erstellen
    $adminUsername = $_POST['admin_username'];
    $adminPassword = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);

    $insertAdminQuery = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = $conn->prepare($insertAdminQuery);
    $stmt->bind_param("ss", $adminUsername, $adminPassword);
    $stmt->execute();

    // Employee-Benutzer erstellen
    $employeeUsername = $_POST['employee_username'];
    $employeePassword = password_hash($_POST['employee_password'], PASSWORD_DEFAULT);

    $insertEmployeeQuery = "INSERT INTO users_app (username, password) VALUES (?, ?)";
    $stmt = $conn->prepare($insertEmployeeQuery);
    $stmt->bind_param("ss", $employeeUsername, $employeePassword);
    $stmt->execute();

    // Installation abgeschlossen
    echo "The installation was successfully completed. You can now go to the <a href='login.php'>login area</a>.";
    echo "<script>";
    echo "alert('The installation was successfully completed.');";
    echo "window.location.href = 'success-installation.php';";
    echo "</script>";
    // db_connection.php erstellen
    $dbConnectionContent = <<<EOD

    
<?php

\$servername = "$host";
\$username = "$username";
\$password = "$password";
\$dbname = "$database";

\$conn = new mysqli(\$servername, \$username, \$password, \$dbname);
if (\$conn->connect_error) {
    die("Fehler bei der Verbindung zur Datenbank: " . \$conn->connect_error);
}
EOD;

    file_put_contents('../db_connection.php', $dbConnectionContent);
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Installation - Mewdev Employee Attendance Tracker</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<div class="navbar">
    <div class="logo">
        <a href="/"><img src="/admin/logo.png" alt="Logo"></a>
    </div>
    <div class="menu-links">
        <a href="https://mewdev.com/employee-attendance-tracker/" target="_blank">About</a>
        <a href="https://mewdev.com/#contact" target="_blank">Support</a>
        <a href="https://fr-be.trustpilot.com/review/mewdev.com" target="_blank">Review</a>
    </div>
</div>

<body>
    <h1>Installation</h1>
    <form method="post" action="">
        <label for="host">Database Host:</label>
        <input type="text" name="host" id="host" value="localhost" required><br>

        <label for="username">Database Username:</label>
        <input type="text" name="username" id="username" value="root" required><br>

        <label for="password">Database Password:</label>
        <input type="password" name="password" id="password"><br>

        <label for="database">Database Name:</label>
        <input type="text" name="database" id="database" value="mewdev_eat" required><br>

        <label for="admin_username">Admin Username:</label>
        <input type="text" name="admin_username" id="admin_username" value="admin" required><br>

        <label for="admin_password">Admin Password:</label>
        <input type="password" name="admin_password" id="admin_password" required><br>

        <label for="employee_username">Employee Username:</label>
        <input type="text" name="employee_username" id="employee_username" value="employee" required><br>

        <label for="employee_password">Employee Password:</label>
        <input type="password" name="employee_password" id="employee_password" required><br>
        <input type="submit" value="Install">
    </form>
</body>
<footer>
    <p>&copy; <?php echo date("Y"); ?> Mewdev All rights reserved. Developed by <a href="https://www.mewdev.com" target="_blank">Mewdev.com</a></p>
</footer>

</html>