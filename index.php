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


// Establish database connection
include "db_connection.php";

session_start();

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login-app.php");
    exit;
}


// Generate a CSRF token if none is present
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check-in / Check-out logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify the CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }

    // Employee ID
    $employeeId = isset($_POST['employee_id']) ? htmlspecialchars($_POST['employee_id']) : '';

    // Check if the employee has already checked in
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND DATE(check_in_time) = CURDATE() ORDER BY check_in_time DESC LIMIT 1");
    $stmt->bind_param("s", $employeeId);
    $stmt->execute();
    $checkResult = $stmt->get_result();
    $checkData = $checkResult->fetch_assoc();
    $checkOutTime = $checkData ? $checkData['check_out_time'] : null;

    $message = '';  // variable to store the message

    if (isset($_POST['checkin']) && $_POST['checkin'] === 'Check-in' && preg_match("/^[a-zA-ZäöüßÄÖÜ\s]+$/", $employeeId)) {
        // Check if the employee has already checked in and checked out
        if ($checkResult->num_rows === 0 || $checkOutTime !== null) {
            // Check-in process
            $currentTime = date('Y-m-d H:i:s');
            // Insert check-in record into the database
            $stmt = $conn->prepare("INSERT INTO attendance (employee_id, check_in_time) VALUES (?, ?)");
            $stmt->bind_param("ss", $employeeId, $currentTime);
            if ($stmt->execute() === TRUE) {
                $message = "Check-in successful for Employee: " . $employeeId;
                header("Refresh:0");
            } else {
                $message = "Error during check-in for Employee: " . $employeeId . " Error: " . $conn->error;
                header("Refresh:0");
            }
        } else {
            $message = "The employee: " . $employeeId . " has already checked in and not checked out.";
            header("Refresh:0");
        }
    } elseif (isset($_POST['checkout']) && $_POST['checkout'] === 'Check-out' && preg_match("/^[a-zA-ZäöüßÄÖÜ\s]+$/", $employeeId)) {
        // Check if the employee has already checked in and not checked out
        if ($checkResult->num_rows > 0 && $checkOutTime === null) {
            // Check-out process
            $currentTime = date('Y-m-d H:i:s');
            // Insert check-out record into the database
            $stmt = $conn->prepare("UPDATE attendance SET check_out_time = ? WHERE employee_id = ? AND check_out_time IS NULL");
            $stmt->bind_param("ss", $currentTime, $employeeId);
            if ($stmt->execute() === TRUE) {
                $message = "Check-out successful for Employee: " . $employeeId;
                header("Refresh:0");
            } else {
                $message = "Error during check-out for Employee: " . $employeeId . " Error: " . $conn->error;
                header("Refresh:0");
            }
        } else {
            $message = "The employee: " . $employeeId . " has already checked out or not checked in.";
            header("Refresh:0");
        }
    } else {
        $message = "No QR Code recognized.";
        header("Refresh:0");
    }
}

?>

<html>

<head>
    <title>Mewdev Employee Attendance Tracker</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" type="text/css" href="style.css">
    <script src="jsQR.js"></script>
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
            <a class="back-button" href="/logout-app.php">Logout</a>
            <a class="back-button" href="/admin">Admin Area</a>
        </div>
    </div>

    <form method="post" action="" id="attendanceForm">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <label style="display: none;" for="employee_id">Employee ID:</label>
        <input type="text" id="employee_id" name="employee_id" style="display: none;">
        <button type="button" id="scanQR">Scan QR Code</button>
        <input type="submit" name="checkin" value="Check-in" style="display: none;">
        <input type="submit" name="checkout" value="Check-out" style="display: none;">
    </form>

    <div id="popup" class="popup">
        <div class="popup-content">
            <span class="close">&times;</span>
            <p>Scanning QR code, please wait...</p>
            <canvas id="qr-canvas"></canvas>
            <button class="popup-checkin" id="popup-checkin">Check-in</button>
            <button class="popup-checkout" id="popup-checkout">Check-out</button>
        </div>
    </div>
    <?php
    // Get the last checked-in employee
    $stmt = $conn->prepare("SELECT employee_id, check_in_time, check_out_time FROM attendance ORDER BY check_in_time DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<h2>Last Checked-in Employee</h2>";
        echo "<table>";
        echo "<tr><th>Employee ID</th><th>Check-in</th><th>Check-out</th><th>Working Time</th></tr>";

        while ($row = $result->fetch_assoc()) {
            $employeeId = $row['employee_id'];
            $checkInTime = $row['check_in_time'];
            $checkOutTime = $row['check_out_time'];

            $totalTime = 0; // Default value in case the employee hasn't checked out yet
            $hours = 0;
            $minutes = 0;

            if ($checkOutTime !== null) {
                $totalTime = strtotime($checkOutTime) - strtotime($checkInTime);
                $hours = floor($totalTime / 3600);
                $minutes = floor(($totalTime % 3600) / 60);
            }

            echo "<tr><td>$employeeId</td><td>$checkInTime</td><td>$checkOutTime</td><td>$hours hours $minutes minutes</td></tr>";
        }

        echo "</table>";
    } else {
        echo "No data found.";
    }

    // Employee form
    ?>
    <script>
        let video = document.createElement("video");
        let canvasElement = document.getElementById("qr-canvas");
        let canvas = canvasElement.getContext("2d");

        // Move this function into the 'scanQR' button event
        function enableCamera() {
            // Use facingMode: environment to attempt to get the front camera on phones
            navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: "user"
                }
            }).then(function(stream) {
                video.srcObject = stream;
                video.setAttribute("playsinline", true); // required to tell iOS safari we don't want fullscreen
                video.play();
                requestAnimationFrame(tick);
            });
        }

        function tick() {
            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvasElement.hidden = false;

                canvasElement.height = video.videoHeight;
                canvasElement.width = video.videoWidth;
                canvas.drawImage(video, 0, 0, canvasElement.width, canvasElement.height);
                let imageData = canvas.getImageData(0, 0, canvasElement.width, canvasElement.height);
                let code = jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: "dontInvert",
                });
                if (code) {
                    document.getElementById('employee_id').value = code.data;
                    document.getElementById('attendanceForm').submit();
                }
            }
            requestAnimationFrame(tick);
        }

        document.getElementById('scanQR').addEventListener('click', function() {
            document.getElementById('popup').style.display = 'block';
            enableCamera(); // Call the function when 'scanQR' button is clicked
        });

        document.getElementById('popup-checkin').addEventListener('click', function() {
            document.getElementById('attendanceForm').checkin.click();
        });

        document.getElementById('popup-checkout').addEventListener('click', function() {
            document.getElementById('attendanceForm').checkout.click();
        });

        document.getElementsByClassName('close')[0].addEventListener('click', function() {
            document.getElementById('popup').style.display = 'none';
            qrMessage.innerText = 'Please Scan your QR Code'; // Resets the message when the popup is closed
        });

        let qrMessage = document.querySelector('.popup-content p');
        qrMessage.innerText = 'Please Scan your QR Code'; // Sets the message when the page loads

        let timer; // Add a timer to hold the setTimeout function

        function tick() {
            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvasElement.hidden = false;

                canvasElement.height = video.videoHeight;
                canvasElement.width = video.videoWidth;
                canvas.drawImage(video, 0, 0, canvasElement.width, canvasElement.height);
                let imageData = canvas.getImageData(0, 0, canvasElement.width, canvasElement.height);
                let code = jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: "dontInvert",
                });
                if (code) {
                    document.getElementById('employee_id').value = code.data;
                    qrMessage.innerText = 'Welcome ' + code.data; // Updates the paragraph content when a QR code is recognized

                    // Don't clear the timer when a new QR code is recognized

                    if (!timer) { // Start a new timer only if it's not already started
                        timer = setTimeout(() => {
                            qrMessage.innerText = 'Please Scan your QR Code'; // Resets the paragraph content after 10 seconds
                            timer = null; // Reset the timer
                        }, 10000);
                    }
                }
            }
            requestAnimationFrame(tick);
        }
    </script>
    <script>
        let message = <?php echo json_encode($message); ?>;
        if (message) {
            alert(message);
        }
    </script>

</body>

<footer>
    <p>&copy; <?php echo date("Y"); ?> Mewdev All rights reserved. Developed by <a href="https://www.mewdev.com" target="_blank">Mewdev.com</a></p>
</footer>

</html>