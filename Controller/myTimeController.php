<?php
// Include database connection
require_once '../../Model/config.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['is_logged_in']) || !isset($_SESSION['is_doctor']) || $_SESSION['is_doctor'] !== true) {
    // Redirect to login page if not logged in
    header("Location: ../doctorLogin.php");
    exit();
}

// Get doctor ID from session
$doctorId = $_SESSION['doctor_id'];
$doctorName = $_SESSION['doctor_name'];

// Get all availability slots for this doctor
$stmt = $conn->prepare("
    SELECT * FROM doctor_availability 
    WHERE doctor_id = ? 
    ORDER BY date ASC, start_time ASC
");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$result = $stmt->get_result();
$availabilitySlots = [];

while ($row = $result->fetch_assoc()) {
    $availabilitySlots[] = $row;
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        // Add new availability
        if ($_POST['action'] == 'add') {
            $startTime = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_STRING);
            $endTime = filter_input(INPUT_POST, 'end_time', FILTER_SANITIZE_STRING);
            $specificDate = $_POST['specific_date']; // Now required
            
            // Validate times
            if ($endTime <= $startTime) {
                $_SESSION['time_error'] = "End time must be later than start time.";
            } else if (empty($specificDate)) {
                $_SESSION['time_error'] = "Specific date is required.";
            } else {
                // Check for overlapping times on that date
                $overlapCheck = $conn->prepare("
                    SELECT * FROM doctor_availability 
                    WHERE doctor_id = ? 
                    AND date = ?
                    AND ((start_time <= ? AND end_time > ?) 
                    OR (start_time < ? AND end_time >= ?) 
                    OR (start_time >= ? AND end_time <= ?))
                ");
                // Fix the parameter count - 8 parameters need 8 type identifiers
                $overlapCheck->bind_param("isssssss", $doctorId, $specificDate, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime);
                $overlapCheck->execute();
                $overlapResult = $overlapCheck->get_result();
                
                if ($overlapResult->num_rows > 0) {
                    $_SESSION['time_error'] = "This time slot overlaps with an existing availability time.";
                } else {
                    // Add the new availability
                    $insertStmt = $conn->prepare("
                        INSERT INTO doctor_availability 
                        (doctor_id, start_time, end_time, date) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $insertStmt->bind_param("isss", $doctorId, $startTime, $endTime, $specificDate);
                    
                    if ($insertStmt->execute()) {
                        $_SESSION['time_success'] = "Availability time slot added successfully.";
                    } else {
                        $_SESSION['time_error'] = "Error adding availability: " . $conn->error;
                    }
                }
            }
            header("Location: myTime.php");
            exit();
        }
        
        // Delete availability
        else if ($_POST['action'] == 'delete' && isset($_POST['id'])) {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            
            if ($id) {
                $deleteStmt = $conn->prepare("DELETE FROM doctor_availability WHERE id = ? AND doctor_id = ?");
                $deleteStmt->bind_param("ii", $id, $doctorId);
                
                if ($deleteStmt->execute()) {
                    $_SESSION['time_success'] = "Availability time slot deleted successfully.";
                } else {
                    $_SESSION['time_error'] = "Error deleting availability: " . $conn->error;
                }
            }
            header("Location: myTime.php");
            exit();
        }
        
        // Update availability
        else if ($_POST['action'] == 'update' && isset($_POST['id'])) {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $startTime = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_STRING);
            $endTime = filter_input(INPUT_POST, 'end_time', FILTER_SANITIZE_STRING);
            $specificDate = $_POST['specific_date']; // Now required
            
            // Validate times
            if ($endTime <= $startTime) {
                $_SESSION['time_error'] = "End time must be later than start time.";
            } else if (empty($specificDate)) {
                $_SESSION['time_error'] = "Specific date is required.";
            } else {
                // Check for overlapping times, excluding the current slot
                $overlapCheck = $conn->prepare("
                    SELECT * FROM doctor_availability 
                    WHERE doctor_id = ? 
                    AND date = ? 
                    AND id != ?
                    AND ((start_time <= ? AND end_time > ?) 
                    OR (start_time < ? AND end_time >= ?) 
                    OR (start_time >= ? AND end_time <= ?))
                ");
                // Fix the parameter count - 9 parameters need 9 type identifiers
                $overlapCheck->bind_param("isisissss", $doctorId, $specificDate, $id, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime);
                $overlapCheck->execute();
                $overlapResult = $overlapCheck->get_result();
                
                if ($overlapResult->num_rows > 0) {
                    $_SESSION['time_error'] = "This time slot overlaps with an existing availability time.";
                } else {
                    // Update the availability
                    $updateStmt = $conn->prepare("
                        UPDATE doctor_availability 
                        SET start_time = ?, end_time = ?, date = ?
                        WHERE id = ? AND doctor_id = ?
                    ");
                    $updateStmt->bind_param("sssii", $startTime, $endTime, $specificDate, $id, $doctorId);
                    
                    if ($updateStmt->execute()) {
                        $_SESSION['time_success'] = "Availability time slot updated successfully.";
                    } else {
                        $_SESSION['time_error'] = "Error updating availability: " . $conn->error;
                    }
                }
            }
            header("Location: myTime.php");
            exit();
        }
    }
}
?>