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

// Export function for Excel
function exportToExcel($data)
{
    require_once __DIR__ . '/../vendor/autoload.php';

    // Create a new Spreadsheet instance
    $spreadsheet = new Spreadsheet();

    // Select the active sheet
    $sheet = $spreadsheet->getActiveSheet();

    // Set table headers
    $sheet->setCellValue('A1', 'Employee ID');
    $sheet->setCellValue('B1', 'Check-in');
    $sheet->setCellValue('C1', 'Check-out');
    $sheet->setCellValue('D1', 'Working Time');

    // Insert data into the table
    $row = 2;
    foreach ($data as $rowdata) {
        $sheet->setCellValue('A' . $row, $rowdata['employee_id']);
        $sheet->setCellValue('B' . $row, $rowdata['check_in_time']);
        $sheet->setCellValue('C' . $row, $rowdata['check_out_time']);

        $totalTime = strtotime($rowdata['check_out_time']) - strtotime($rowdata['check_in_time']);
        $hours = floor($totalTime / 3600);
        $minutes = floor(($totalTime % 3600) / 60);
        $sheet->setCellValue('D' . $row, $hours . ' hours ' . $minutes . ' minutes');

        $row++;
    }

    // Set the filename for the export
    $filename = 'employee_attendance_' . date('Y-m-d_H-i-s') . '.xlsx';

    // Get the path to the /excel directory
    $filePath = __DIR__ . '/excel/' . $filename;

    // Generate the Excel file
    $writer = new Xlsx($spreadsheet);
    $writer->save($filePath);

    // Return the filename so that you can use it elsewhere in your code.
    return $filename;
}

// Employee ID filter
$employeeIdFilter = isset($_GET['employee_id']) ? $conn->real_escape_string($_GET['employee_id']) : '';

// Check-in date filter
$checkinDateFilter = isset($_GET['checkin_date']) ? $conn->real_escape_string($_GET['checkin_date']) : '';

// Check-out date filter
$checkoutDateFilter = isset($_GET['checkout_date']) ? $conn->real_escape_string($_GET['checkout_date']) : '';

// Working time filter
$workingTimeFilter = isset($_GET['working_time']) ? $conn->real_escape_string($_GET['working_time']) : '';

$filters = [];

if (!empty($employeeIdFilter)) {
    $filters[] = "employee_id = '$employeeIdFilter'";
}

if (!empty($checkinDateFilter)) {
    $filters[] = "DATE(check_in_time) = '$checkinDateFilter'";
}

if (!empty($checkoutDateFilter)) {
    $filters[] = "DATE(check_out_time) = '$checkoutDateFilter'";
}

if (!empty($workingTimeFilter)) {
    $filters[] = "(TIME_TO_SEC(TIMEDIFF(check_out_time, check_in_time)) / 3600) >= $workingTimeFilter";
}

$filterCondition = '';
if (!empty($filters)) {
    $filterCondition = 'WHERE ' . implode(' AND ', $filters);
}

if (isset($_GET['delete_id'])) {
    if (checkCSRFToken()) {
        $deleteId = $conn->real_escape_string($_GET['delete_id']);
        $deleteQuery = "DELETE FROM attendance WHERE id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("s", $deleteId);
        if ($stmt->execute()) {
            // Redirect to the same page with a success parameter
            header("Location: index.php?delete_success=true");
            exit;
        } else {
            // Redirect to the same page with an error parameter
            header("Location: index.php?delete_error=true");
            exit;
        }
    } else {
        // CSRF token verification failed, take appropriate action here (e.g., error handling, aborting the action, etc.)
        die('CSRF Token verification failed. Deletion aborted.');
    }
}

$query = "SELECT id, employee_id, check_in_time, check_out_time FROM attendance $filterCondition ORDER BY check_in_time DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

// Call the export function if the export parameter is set
if (isset($_GET['export']) && $_GET['export'] === 'true') {
    if ($result->num_rows > 0) {
        $filteredData = [];
        while ($row = $result->fetch_assoc()) {
            $filteredData[] = $row;
        }
        exportToExcel($filteredData);
    } else {
        echo "No data found for export.";
        exit;
    }
}
?>
<html>

<head>
    <title>Mewdev Employee Attendance Tracker - Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <script src="jquery.min.js"></script>
    <link rel="stylesheet" type="text/css" href="../adminstyle.css">
</head>
<script>
    function checkCSRFToken(token) {
        // Check if the token matches the one stored in the session data
        if (!token || token !== "<?php echo $_SESSION['csrf_token']; ?>") {
            // CSRF token verification failed, take appropriate action here (e.g., error handling, aborting the action, etc.)
            alert('CSRF Token verification failed.');
            return false;
        }
        return true;
    }

    function confirmDelete(id) {
        var csrfToken = "<?php echo $_SESSION['csrf_token']; ?>";
        if (confirm("Please confirm")) {
            var url = "index.php?delete_id=" + id + "&csrf_token=" + csrfToken;
            window.location.href = url;
        }
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Execute code here
        function showSuccessMessage(message) {
            var messageElement = document.getElementById("success-message");
            messageElement.textContent = message;
            messageElement.style.display = "block";
            setTimeout(function() {
                messageElement.style.display = "none";
            }, 4000);
        }

        // Check if success message exists and display it
        var deleteSuccess = "<?php echo isset($_GET['delete_success']) ? 'true' : 'false'; ?>";
        var deleteError = "<?php echo isset($_GET['delete_error']) ? $_GET['delete_error'] : ''; ?>";

        if (deleteSuccess === "true") {
            showSuccessMessage("Record deleted successfully");
        } else if (deleteError !== "") {
            showSuccessMessage("Error deleting record: " + deleteError);
        }
    });
</script>

<script>
    function openModal(id) {
        document.getElementById('editModal').style.display = 'block';
        // Save the id of the record for later
        document.getElementById('editModal').dataset.id = id;
    }

    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    function saveChanges() {
        var id = document.getElementById('editModal').dataset.id;
        var checkInTime = document.getElementById('editCheckInTime').value;
        var checkOutTime = document.getElementById('editCheckOutTime').value;

        closeModal();
    }
</script>

<body>
    <div class="navbar">
        <div class="logo">
            <a href="/admin"><img src="logo.png" alt="Logo"></a>
        </div>
        <div class="menu-links">
            <a href="https://mewdev.com/employee-attendance-tracker/" target="_blank">About</a>
            <a href="https://mewdev.com/contact" target="_blank">Support</a>
            <a href="https://fr-be.trustpilot.com/review/mewdev.com" target="_blank">Review</a>
        </div>
        <div>
            <a class="back-button" href="/admin/qr-generate.php">QR CODE GENERATOR</a>
            <a class="back-button" href="/">Back to App</a>
            <a class="back-button" href="logout.php">Logout</a>
        </div>
    </div>

    <h1>Employee Attendance Tracker - Admin</h1>

    <h2>Filters</h2>
    <form method="get" action="">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <label for="employee_id">Employee ID:</label>
        <input type="text" name="employee_id" id="employee_id" value="<?php echo $employeeIdFilter; ?>">
        <br>
        <label for="checkin_date">Check-in Date:</label>
        <input type="date" name="checkin_date" id="checkin_date" value="<?php echo $checkinDateFilter; ?>">
        <br>
        <label for="checkout_date">Check-out Date:</label>
        <input type="date" name="checkout_date" id="checkout_date" value="<?php echo $checkoutDateFilter; ?>">
        <br>
        <label for="working_time">Minimum Working Time (in hours):</label>
        <input type="number" name="working_time" id="working_time" value="<?php echo $workingTimeFilter; ?>">
        <br>
        <input type="submit" value="Filter">
    </form>

    <div id="downloadModal" style="display: none;">
        <p>Last generated Excel file:</p>
        <p id="downloadFileName"></p>
        <a id="downloadLink" href="#" style="text-decoration: none; color: #fff; background-color: #007BFF; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Download</a>
        <button onclick="closeDownloadModal()">Close</button>
    </div>

    <h2>All Employees</h2>
    <div id="editModal" style="display:none;">
        <label for="editCheckInTime">Check-in time:</label>
        <input type="datetime-local" id="editCheckInTime">
        <br>
        <label for="editCheckOutTime">Check-out time:</label>
        <input type="datetime-local" id="editCheckOutTime">
        <br>
        <button onclick="saveChanges()">Save</button>
        <button onclick="closeModal()">Cancel</button>
    </div>
    <script>
        var idToEdit = null;

        function openModal(id) {
            idToEdit = id;
            document.getElementById('editModal').style.display = 'block';
            // Save the id of the record for later
            document.getElementById('editModal').dataset.id = id;
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function saveChanges() {
            var id = idToEdit;
            var checkInTime = document.getElementById('editCheckInTime').value;
            var checkOutTime = document.getElementById('editCheckOutTime').value;

            $.ajax({
                url: 'update.php',
                type: 'post',
                data: {
                    id: id,
                    check_in_time: checkInTime,
                    check_out_time: checkOutTime,
                    csrf_token: "<?php echo $_SESSION['csrf_token']; ?>"
                },
                success: function(response) {
                    if (response === 'success') {
                        alert('Changes saved successfully.');
                        location.reload();
                    } else {
                        location.reload();
                    }
                },
                error: function() {
                    alert('Error sending request.');
                }
            });

            closeModal();
        }
    </script>

    <?php
    if ($result->num_rows > 0) {
        // Create an array with the filter parameters
        $filterParams = array(
            'employee_id' => $employeeIdFilter,
            'checkin_date' => $checkinDateFilter,
            'checkout_date' => $checkoutDateFilter,
            'working_time' => $workingTimeFilter,
            'export' => 'true',
            'csrf_token' => $_SESSION['csrf_token']
        );

        // Create a string with the filter parameters
        $filterParamsString = http_build_query($filterParams);

        // Create the download URL with the filter parameters
        $downloadUrl = "index.php?" . $filterParamsString;

        // Set the download URL as a link
        echo '<button data-download onclick="generateExcel()"><a href="' . $downloadUrl . '" style="text-decoration: none; color: #fff;">Generate Excel File</a></button>';
        echo '<script>
    function generateExcel() {
        // Show the alert message with "New File generated"
        alert("New File generated");

        // Reload the page after a short delay (adjust the delay as needed)
        setTimeout(function() {
            location.reload();
        }, 1000);
    }
</script>';

        // Set the Last generated File button
        echo '<button data-show-last-file onclick="showLastGeneratedFile()" style="margin-left: 5px;">Show Last generated File</button>';

        echo "<table>";
        echo "<tr><th>Employee ID</th><th>Check-in</th><th>Check-out</th><th>Working Time</th><th>Delete</th><th>Edit</th></tr>";

        while ($row = $result->fetch_assoc()) {
            $employeeId = $row['employee_id'];
            $checkInTime = $row['check_in_time'] ?? 'No Check-in registered';
            $checkOutTime = $row['check_out_time'] ?? 'No Check-out registered';

            if (!empty($row['check_out_time'])) {
                $totalTime = strtotime($checkOutTime) - strtotime($checkInTime);
                $hours = floor($totalTime / 3600);
                $minutes = floor(($totalTime % 3600) / 60);
                $workingTime = "$hours hours $minutes minutes";
            } else {
                $workingTime = "0";
            }

            echo "<tr><td>$employeeId</td><td>$checkInTime</td><td>$checkOutTime</td><td>$workingTime</td>";
            echo "<td><button onclick=\"confirmDelete(" . $row['id'] . ")\" style=\"text-decoration: none; color: #fff; background-color: #ff0000; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;\">Delete</button></td>";
            echo "<td><button data-edit onclick='openModal(" . $row['id'] . ")'>Edit</button></td></tr>";
        }

        echo "</table>";
    } else {
        echo "No data found.";
    }
    ?>
    <div id="success-message" class="popup-message">Record deleted successfully</div>

    <script>
        function showLastGeneratedFile() {
            $.ajax({
                url: 'get_last_generated_file.php', // Updated URL to point to the correct file location
                type: 'get',
                success: function(response) {
                    if (response !== 'error') {
                        var fileName = response;
                        var downloadUrl = 'excel/' + fileName; // Assuming the files are in the "excel" directory

                        document.getElementById('downloadFileName').textContent = fileName;
                        document.getElementById('downloadLink').setAttribute('href', downloadUrl);
                        document.getElementById('downloadModal').style.display = 'block';
                    } else {
                        alert('Error fetching last generated file.');
                    }
                },
                error: function() {
                    alert('Error fetching last generated file.');
                }

            });
        }

        function closeDownloadModal() {
            document.getElementById('downloadModal').style.display = 'none';
        }
    </script>

</body>

<footer>
    <p>&copy; <?php echo date("Y"); ?> Mewdev All rights reserved. Developed by <a href="https://www.mewdev.com" target="_blank">Mewdev.com</a></p>
</footer>

</html>