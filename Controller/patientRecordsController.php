<?php
// Include database connection
require_once '../Model/config.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['is_logged_in']) || !isset($_SESSION['is_doctor']) || $_SESSION['is_doctor'] !== true) {
    // Redirect to login page if not logged in
    header("Location: ../View/doctorLogin.php");
    exit();
}

// Get doctor ID from session
$doctorId = $_SESSION['doctor_id'];

// Check if form was submitted or action was requested
if (isset($_POST['action']) || isset($_GET['action'])) {
    $action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'];
    
    // Handle different actions
    switch ($action) {
        case 'add':
            addPatientRecord($conn);
            break;
        case 'edit':
            editPatientRecord($conn);
            break;
        case 'delete':
            deletePatientRecord($conn);
            break;
        case 'view':
            viewPatientRecord($conn);
            break;
        default:
            $_SESSION['error'] = "Invalid action.";
            header("Location: ../View/Doctor/patientManagement.php");
            exit();
    }
} else {
    // No action specified
    $_SESSION['error'] = "No action specified.";
    header("Location: ../View/Doctor/patientManagement.php");
    exit();
}

/**
 * Add a new patient medical record
 * 
 * @param mysqli $conn Database connection
 */
function addPatientRecord($conn) {
    // Get doctor ID from session
    $doctorId = $_SESSION['doctor_id'];
    
    // Validate and sanitize input
    $patientId = filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT);
    $recordDate = filter_input(INPUT_POST, 'record_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $examination = filter_input(INPUT_POST, 'examination', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $direction = filter_input(INPUT_POST, 'direction', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    // Optional: Get appointment ID if it exists
    $appointmentId = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
    
    // Validate required fields
    if (!$patientId || !$recordDate || !$examination || !$direction) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: ../View/Doctor/addPatientRecord.php?patient_id=" . $patientId);
        exit();
    }
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Insert record into database
        $stmt = $conn->prepare("
            INSERT INTO patient_records 
            (patient_id, doctor_id, record_date, examination, direction) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisss", $patientId, $doctorId, $recordDate, $examination, $direction);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to add record: " . $conn->error);
        }
        
        $recordId = $conn->insert_id;
        
        // If appointment ID was provided, update the appointment status to completed
        if ($appointmentId) {
            $stmt = $conn->prepare("
                UPDATE appointments 
                SET status = 'completed' 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("ii", $appointmentId, $patientId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update appointment: " . $conn->error);
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success'] = "Medical record added successfully.";
        header("Location: ../View/Doctor/viewPatient.php?id=" . $patientId);
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        $_SESSION['error'] = $e->getMessage();
        header("Location: ../View/Doctor/addPatientRecord.php?patient_id=" . $patientId);
        exit();
    }
}

/**
 * Edit an existing patient medical record
 * 
 * @param mysqli $conn Database connection
 */
function editPatientRecord($conn) {
    // Validate and sanitize input
    $recordId = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);
    $patientId = filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT);
    $recordDate = filter_input(INPUT_POST, 'record_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $examination = filter_input(INPUT_POST, 'examination', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $direction = filter_input(INPUT_POST, 'direction', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    // Validate required fields
    if (!$recordId || !$patientId || !$recordDate || !$examination || !$direction) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: ../View/Doctor/editPatientRecord.php?id=" . $recordId);
        exit();
    }
    
    try {
        // Check if record exists and belongs to the patient
        $stmt = $conn->prepare("
            SELECT id FROM patient_records 
            WHERE id = ? AND patient_id = ?
        ");
        $stmt->bind_param("ii", $recordId, $patientId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Record not found or does not belong to this patient.");
        }
        
        // Update record
        $stmt = $conn->prepare("
            UPDATE patient_records 
            SET record_date = ?, examination = ?, direction = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("sssi", $recordDate, $examination, $direction, $recordId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update record: " . $conn->error);
        }
        
        $_SESSION['success'] = "Medical record updated successfully.";
        header("Location: ../View/Doctor/viewPatient.php?id=" . $patientId);
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: ../View/Doctor/editPatientRecord.php?id=" . $recordId);
        exit();
    }
}

/**
 * Delete a patient medical record
 * 
 * @param mysqli $conn Database connection
 */
function deletePatientRecord($conn) {
    // Get record ID and patient ID
    $recordId = isset($_GET['id']) ? filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) : null;
    $patientId = isset($_GET['patient_id']) ? filter_input(INPUT_GET, 'patient_id', FILTER_VALIDATE_INT) : null;
    
    if (!$recordId || !$patientId) {
        $_SESSION['error'] = "Invalid record ID or patient ID.";
        header("Location: ../View/Doctor/patientManagement.php");
        exit();
    }
    
    try {
        // Check if record exists and belongs to the patient
        $stmt = $conn->prepare("
            SELECT id FROM patient_records 
            WHERE id = ? AND patient_id = ?
        ");
        $stmt->bind_param("ii", $recordId, $patientId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Record not found or does not belong to this patient.");
        }
        
        // Delete record
        $stmt = $conn->prepare("DELETE FROM patient_records WHERE id = ?");
        $stmt->bind_param("i", $recordId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete record: " . $conn->error);
        }
        
        $_SESSION['success'] = "Medical record deleted successfully.";
        header("Location: ../View/Doctor/viewPatient.php?id=" . $patientId);
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: ../View/Doctor/viewPatient.php?id=" . $patientId);
        exit();
    }
}

/**
 * View a specific patient record
 * 
 * @param mysqli $conn Database connection
 */
function viewPatientRecord($conn) {
    // Get record ID
    $recordId = isset($_GET['id']) ? filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) : null;
    
    if (!$recordId) {
        $_SESSION['error'] = "Invalid record ID.";
        header("Location: ../View/Doctor/patientManagement.php");
        exit();
    }
    
    try {
        // Get detailed record information including patient and doctor details
        $stmt = $conn->prepare("
            SELECT pr.*, 
                   pc.firstName as patient_first_name, 
                   pc.lastName as patient_last_name,
                   dc.firstName as doctor_first_name, 
                   dc.lastName as doctor_last_name
            FROM patient_records pr
            JOIN patient_creds pc ON pr.patient_id = pc.id
            JOIN doctor_creds dc ON pr.doctor_id = dc.id
            WHERE pr.id = ?
        ");
        $stmt->bind_param("i", $recordId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Record not found.");
        }
        
        $record = $result->fetch_assoc();
        
        // Store record in session and redirect to view page
        $_SESSION['temp_record'] = $record;
        header("Location: ../View/Doctor/viewRecord.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: ../View/Doctor/patientManagement.php");
        exit();
    }
}
?>