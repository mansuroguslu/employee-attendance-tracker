
    
<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mewdev_eat";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Fehler bei der Verbindung zur Datenbank: " . $conn->connect_error);
}
