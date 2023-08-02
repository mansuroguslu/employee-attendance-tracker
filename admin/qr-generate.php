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
    header("Location: ../install/install.php");
    exit;
}

// Start session
session_start();

// Check if the user is not authenticated or not from the "users" table, redirect them to the login page
if (
    !isset($_SESSION['authenticated']) ||
    !$_SESSION['authenticated'] ||
    !isset($_SESSION['user_type']) ||
    $_SESSION['user_type'] !== 'users'
) {
    header("Location: login.php");
    exit;
}

// Generate CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check CSRF token when submitting the form
function checkCSRFToken()
{
    if (
        isset($_GET['csrf_token']) &&
        isset($_SESSION['csrf_token']) &&
        $_GET['csrf_token'] === $_SESSION['csrf_token']
    ) {
        return true;
    } else {
        return false;
    }
}

// Establish database connection
include "../db_connection.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

use chillerlan\QRCode\{QRCode, QROptions};

// Lade die QR Code Generator Library
require_once "../vendor/autoload.php";
require_once "../vendor/chillerlan/php-qrcode/src/QROptions.php";
require_once "../vendor/chillerlan/php-qrcode/src/QRCode.php";

// Wenn das Formular abgeschickt wurde, generiere den QR-Code
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST["name"];

    // Prüfe, ob der Name nicht leer ist
    if (!empty($name)) {
        // Definiere den "Content Text" für den QR-Code
        $contentText = "Name: " . $name;

        // Pfad zum Speichern des QR-Codes
        $qrCodeImagePath = "qrcodepng/qr_code.png"; // Passe den Speicherpfad an

        // Konfigurationsoptionen für den QR-Code
        $options = new chillerlan\QRCode\QROptions([
            'outputType' => 'png',
            'eccLevel' => QRCode::ECC_L,
            'scale' => intval(6),
        ]);

        // Generiere den QR-Code
        $qrCode = new QRCode($options);
        $qrCode->render($contentText, $qrCodeImagePath);
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>QR Code Generator</title>
    <link rel="stylesheet" type="text/css" href="../adminstyle.css">

    <style>
        /* Stil für die Visitenkarte */

        .container {
            padding: 20px;
        }

        h1 {
            text-align: center;
        }

        h2 {
            margin-top: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
        }

        .success-message {
            display: none;
            background-color: #4CAF50;
            color: white;
            text-align: center;
            padding: 10px;
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1;
        }

        .downloadModal {
            display: none;
            background-color: #f2f2f2;
            padding: 20px;
            border: 1px solid #ccc;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1;
        }

        .downloadModal p {
            margin: 0;
            margin-bottom: 10px;
        }

        .downloadModal button {
            cursor: pointer;
            margin-top: 10px;
        }

        .download-button {
            background-color: #007BFF;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }

        .print-button {
            background-color: #4CAF50;
            margin-top: 20px;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }

        .card {
            max-width: 9cm;
            max-height: 5.4cm;
            margin: 20px auto;
            padding: 20px;
            border: 2px solid #ccc;
            border-radius: 5px;
        }

        .card h2 {
            font-size: 12px;
            /* Kleinerer Schriftgrad für den Namen */
        }

        .qr-code {
            max-width: 100%;
            /* QR-Code wird so groß wie möglich sein */
            margin: 10px auto;
            /* Etwas Abstand oben und unten */
        }

        /* Verstecke den Rest der Seite beim Drucken */
        @media print {
            body * {
                visibility: hidden;
            }

            .card,
            .card * {
                visibility: visible;
            }

            .qr-code {
                margin: 0;
            }

            /* Verstecke die Druck- und Herunterladen-Buttons beim Drucken */
            .print-button,
            .download-button {
                display: none;
            }
        }
    </style>
    </style>
    <!-- Skript für das Herunterladen der kompletten Visitenkarte als PNG-Datei -->
    <script src="html2canvas.min.js"></script>
</head>

<body>
    <div class="navbar">
        <div class="logo">
            <a href="/admin"><img src="logo.png" alt="Logo"></a>
        </div>
        <div class="menu-links">
            <a href="https://mewdev.com/employee-attendance-tracker/" target="_blank">About</a>
            <a href="https://mewdev.com/#contact" target="_blank">Support</a>
            <a href="https://fr-be.trustpilot.com/review/mewdev.com" target="_blank">Review</a>
        </div>
        <div>
            <a class="back-button" href="/admin">Back to Dashboard</a>
            <a class="back-button" href="logout.php">Logout</a>
        </div>
    </div>

    <h1>QR Code Generator</h1>
    <form method="post">
        <label for="name">Name:</label>
        <input type="text" name="name" id="name" required style="width: 500px;">
        <button type="submit" class="print-button">Generate QR-Code</button>
    </form>

    <?php
    // Zeige den QR-Code und den Namen auf einer Visitenkarte an, wenn er generiert wurde
    if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($name)) {
    ?>
        <!-- Verstecke den Rest der Seite beim Drucken -->
        <div class="card">
            <h2>Name: <?php echo $name; ?></h2>
            <div class="qr-code">
                <img src="qrcodepng/qr_code.png" alt="QR Code">
            </div>
            <!-- Button zum Drucken -->
            <button onclick="window.print()" class="print-button">Print</button>
            <!-- Button zum Herunterladen -->
            <button onclick="downloadVisitenkarte()" class="download-button">Download PNG File</button>
        </div>
        <!-- Skript für das Herunterladen der kompletten Visitenkarte als PNG-Datei -->
        <script>
            function downloadVisitenkarte() {
                const card = document.querySelector('.card');
                html2canvas(card).then(canvas => {
                    const link = document.createElement('a');
                    link.download = 'visitenkarte.png';
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                });
            }
        </script>
    <?php
    }
    ?>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Mewdev All rights reserved. Developed by <a href="https://www.mewdev.com" target="_blank">Mewdev.com</a></p>
    </footer>

    <div class="success-message" id="success-message">Record deleted successfully</div>
    <div class="downloadModal" id="downloadModal">
        <p>Last generated Excel file:</p>
        <p id="downloadFileName"></p>
        <a id="downloadLink" href="#" class="download-button">Download</a>
        <button onclick="closeDownloadModal()">Close</button>
    </div>

</body>

</html>