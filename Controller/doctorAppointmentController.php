<?php
require_once '../Model/config.php';
require '../vendor/autoload.php'; // Add PHPMailer

// Import the PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check if user is logged in and is a doctor
if (!isset($_SESSION['is_logged_in']) || !isset($_SESSION['is_doctor']) || $_SESSION['is_doctor'] !== true) {
    // Redirect to login page if not logged in
    header("Location: ../doctorLogin.php");
    exit();
}

// Process appointment confirmation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == "confirm") {
    try {
        // Check if appointment ID is provided
        if (!isset($_POST['id'])) {
            throw new Exception("Missing appointment identifier");
        }
        
        $appointmentId = $_POST['id'];
        $doctorId = $_POST['doctor_id']; // Get the doctor ID from the form
        
        // First, get the details of the appointment being confirmed
        $detailsStmt = $conn->prepare("
            SELECT a.*, pc.firstName, pc.lastName, pc.email 
            FROM appointments a 
            JOIN patient_creds pc ON a.user_id = pc.id 
            WHERE a.id = ?
        ");
        $detailsStmt->bind_param("i", $appointmentId);
        $detailsStmt->execute();
        $result = $detailsStmt->get_result();
        $appointment = $result->fetch_assoc();
        
        if (!$appointment) {
            throw new Exception("Appointment not found");
        }
        
        // Begin transaction to ensure both operations complete or fail together
        $conn->begin_transaction();
        
        // 1. Update the selected appointment to confirmed AND set the doctor_id
        $stmt = $conn->prepare("UPDATE appointments SET status = 'confirmed', doctor_id = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $doctorId, $appointmentId);
        $stmt->execute();
        
        
        // 2. Cancel all other pending appointments for the same date and time
        $cancelStmt = $conn->prepare("
            UPDATE appointments 
            SET status = 'cancelled', 
                updated_at = NOW() 
            WHERE id != ? 
            AND appointment_date = ? 
            AND appointment_time = ? 
            AND status = 'pending'
        ");
        $cancelStmt->bind_param("iss", $appointmentId, $appointment['appointment_date'], $appointment['appointment_time']);
        $cancelStmt->execute();
        
        // Get number of cancelled appointments
        $cancelledCount = $cancelStmt->affected_rows;
        
        // Commit the transaction
        $conn->commit();
        
        // Format date and time for display
        $patientName = $appointment['firstName'] . ' ' . $appointment['lastName'];
        $formattedDate = date("l, F j, Y", strtotime($appointment['appointment_date']));
        $formattedTime = date("h:i A", strtotime($appointment['appointment_time']));
        
        $_SESSION['appointment_success'] = true;
        $_SESSION['success_message'] = "Appointment #$appointmentId for $patientName on $formattedDate at $formattedTime has been confirmed.";
        
        // Add info about cancelled appointments if any
        if ($cancelledCount > 0) {
            $_SESSION['success_message'] .= " Additionally, $cancelledCount conflicting appointment(s) were automatically cancelled.";
        }
        
        // Send confirmation email to patient
        $patientEmail = $appointment['email'];
        
        $subject = "Your Appointment Has Been Confirmed - #$appointmentId";
        $message = "
        <html>
        <head>
            <title>Appointment Confirmation</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #28a745; color: white; padding: 10px 20px; text-align: center; }
                .content { padding: 20px; border: 1px solid #ddd; }
                .appointment-details { background-color: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 4px solid #28a745; }
                .footer { font-size: 12px; color: #6c757d; margin-top: 20px; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Appointment Confirmation</h2>
                </div>
                <div class='content'>
                    <p>Dear $patientName,</p>
                    <p>We're pleased to inform you that your appointment has been <strong>confirmed</strong>.</p>
                    
                    <div class='appointment-details'>
                        <h3>Appointment Details:</h3>
                        <p><strong>Appointment ID:</strong> #$appointmentId</p>
                        <p><strong>Date:</strong> $formattedDate</p>
                        <p><strong>Time:</strong> $formattedTime</p>
                        <p><strong>Status:</strong> Confirmed</p>
                    </div>
                    
                    <p>Please arrive 15 minutes before your scheduled appointment time. If you need to reschedule or cancel, please do so at least 24 hours in advance.</p>
                    
                    <p>For any questions, please contact our clinic.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Send email
        sendEmail($patientEmail, $subject, $message);
        
        header("Location: ../View/Doctor/appointmentManagement.php");
        exit();
        
    } catch (Exception $e) {
        // If there was an error, roll back the transaction
        if ($conn->ping()) {
            $conn->rollback();
        }
        
        $_SESSION['appointment_error'] = $e->getMessage();
        header("Location: ../View/Doctor/appointmentManagement.php");
        exit();
    }
}

// Process appointment completion
else if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == "complete") {
    try {
        // Check if appointment ID is provided
        if (!isset($_POST['id'])) {
            throw new Exception("Missing appointment identifier");
        }
        
        $appointmentId = $_POST['id'];
        $currentDoctorId = $_SESSION['doctor_id']; // Get the current doctor's ID from session
        
        // First check if the appointment exists and was confirmed by this doctor
        $checkStmt = $conn->prepare("
            SELECT a.*, pc.firstName, pc.lastName, pc.email 
            FROM appointments a 
            JOIN patient_creds pc ON a.user_id = pc.id 
            WHERE a.id = ?
        ");
        $checkStmt->bind_param("i", $appointmentId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $appointment = $result->fetch_assoc();
        
        if (!$appointment) {
            throw new Exception("Appointment not found");
        }
        
        // Check if the appointment is confirmed and has a doctor assigned
        if ($appointment['status'] === 'confirmed' && !empty($appointment['doctor_id'])) {
            // If doctor_id doesn't match current doctor, prevent completion
            if ($appointment['doctor_id'] != $currentDoctorId) {
                throw new Exception("You cannot complete an appointment confirmed by another doctor");
            }
        }
        
        // Update appointment status to completed
        $stmt = $conn->prepare("UPDATE appointments SET status = 'completed', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $appointmentId);
        
        if ($stmt->execute()) {
            // Get appointment details for confirmation message and email
            $detailsStmt = $conn->prepare("
                SELECT a.*, pc.firstName, pc.lastName, pc.email 
                FROM appointments a 
                JOIN patient_creds pc ON a.user_id = pc.id 
                WHERE a.id = ?
            ");
            $detailsStmt->bind_param("i", $appointmentId);
            $detailsStmt->execute();
            $result = $detailsStmt->get_result();
            $appointment = $result->fetch_assoc();
            
            if ($appointment) {
                $patientName = $appointment['firstName'] . ' ' . $appointment['lastName'];
                $patientEmail = $appointment['email'];
                $formattedDate = date("l, F j, Y", strtotime($appointment['appointment_date']));
                $formattedTime = date("h:i A", strtotime($appointment['appointment_time']));
                
                $_SESSION['appointment_success'] = true;
                $_SESSION['success_message'] = "Appointment #$appointmentId for $patientName has been marked as completed.";
                
                // Send completion email to patient
                $subject = "Your Appointment Has Been Completed - #$appointmentId";
                $message = "
                <html>
                <head>
                    <title>Appointment Completed</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #17a2b8; color: white; padding: 10px 20px; text-align: center; }
                        .content { padding: 20px; border: 1px solid #ddd; }
                        .appointment-details { background-color: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 4px solid #17a2b8; }
                        .footer { font-size: 12px; color: #6c757d; margin-top: 20px; text-align: center; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Appointment Completed</h2>
                        </div>
                        <div class='content'>
                            <p>Dear $patientName,</p>
                            <p>Thank you for visiting our clinic. Your appointment has been marked as <strong>completed</strong>.</p>
                            
                            <div class='appointment-details'>
                                <h3>Appointment Details:</h3>
                                <p><strong>Appointment ID:</strong> #$appointmentId</p>
                                <p><strong>Date:</strong> $formattedDate</p>
                                <p><strong>Time:</strong> $formattedTime</p>
                            </div>
                            
                            <p>If you have any follow-up questions or need to schedule another appointment, please feel free to contact our clinic.</p>
                            
                            <p>We hope you had a good experience and look forward to serving you again.</p>
                        </div>
                        <div class='footer'>
                            <p>This is an automated message. Please do not reply to this email.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                // Send email
                sendEmail($patientEmail, $subject, $message);
            } else {
                $_SESSION['appointment_success'] = true;
                $_SESSION['success_message'] = "Appointment #$appointmentId has been marked as completed.";
            }
            
            header("Location: ../View/Doctor/appointmentManagement.php");
            exit();
        } else {
            throw new Exception("Error completing appointment: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        $_SESSION['appointment_error'] = $e->getMessage();
        header("Location: ../View/Doctor/appointmentManagement.php");
        exit();
    }
}

// Process appointment cancellation by doctor
else if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == "cancel") {
    try {
        // Check if appointment ID is provided
        if (!isset($_POST['id'])) {
            throw new Exception("Missing appointment identifier");
        }
        
        $appointmentId = $_POST['id'];
        $currentDoctorId = $_SESSION['doctor_id']; // Get the current doctor's ID from session
        
        // First, check if the appointment exists and get its details
        $checkStmt = $conn->prepare("
            SELECT a.*, pc.firstName, pc.lastName, pc.email 
            FROM appointments a 
            JOIN patient_creds pc ON a.user_id = pc.id 
            WHERE a.id = ?
        ");
        $checkStmt->bind_param("i", $appointmentId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $appointment = $result->fetch_assoc();
        
        if (!$appointment) {
            throw new Exception("Appointment not found");
        }
        
        // Check if the appointment is confirmed and has a doctor assigned
        if ($appointment['status'] === 'confirmed' && !empty($appointment['doctor_id'])) {
            // If doctor_id doesn't match current doctor, prevent cancellation
            if ($appointment['doctor_id'] != $currentDoctorId) {
                throw new Exception("You cannot cancel an appointment confirmed by another doctor");
            }
        }
        
        // If all checks pass, proceed with cancellation
        $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $appointmentId);
        
        if ($stmt->execute()) {
            // Appointment details already loaded from the verification check above
            $patientName = $appointment['firstName'] . ' ' . $appointment['lastName'];
            $patientEmail = $appointment['email'];
            $formattedDate = date("l, F j, Y", strtotime($appointment['appointment_date']));
            $formattedTime = date("h:i A", strtotime($appointment['appointment_time']));
            
            $_SESSION['appointment_success'] = true;
            $_SESSION['success_message'] = "Appointment #$appointmentId for $patientName on $formattedDate at $formattedTime has been cancelled.";
            
            // Existing email sending code remains the same
            $subject = "Your Appointment Has Been Cancelled - #$appointmentId";
            $message = "
            <html>
            <head>
                <title>Appointment Cancellation</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #dc3545; color: white; padding: 10px 20px; text-align: center; }
                    .content { padding: 20px; border: 1px solid #ddd; }
                    .appointment-details { background-color: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 4px solid #dc3545; }
                    .footer { font-size: 12px; color: #6c757d; margin-top: 20px; text-align: center; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Appointment Cancellation</h2>
                    </div>
                    <div class='content'>
                        <p>Dear $patientName,</p>
                        <p>We regret to inform you that your appointment has been <strong>cancelled</strong>.</p>
                        
                        <div class='appointment-details'>
                            <h3>Appointment Details:</h3>
                            <p><strong>Appointment ID:</strong> #$appointmentId</p>
                            <p><strong>Date:</strong> $formattedDate</p>
                            <p><strong>Time:</strong> $formattedTime</p>
                        </div>
                        
                        <p>If you would like to reschedule, please log in to your account or contact our clinic.</p>
                        
                        <p>We apologize for any inconvenience this may have caused and appreciate your understanding.</p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message. Please do not reply to this email.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // Send email
            sendEmail($patientEmail, $subject, $message);
            
            header("Location: ../View/Doctor/appointmentManagement.php");
            exit();
        } else {
            throw new Exception("Error cancelling appointment: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        $_SESSION['appointment_error'] = $e->getMessage();
        header("Location: ../View/Doctor/appointmentManagement.php");
        exit();
    }
}

// If not a valid request, redirect to dashboard
else {
    header("Location: ../View/Doctor/doctorDashboard.php");
    exit();
}

/**
 * Send email notification to a patient
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message Email HTML body
 * @return bool Success status
 */
function sendEmail($to, $subject, $message) {
    try {
        // Create email configuration array (ideally, move this to a config file)
        $emailConfig = [
            'host' => 'smtp.gmail.com',
            'username' => 'lemuellazaro6704@gmail.com', // Replace with your actual email
            'password' => 'khnn crik uacj vdar',    // Replace with your actual app password
            'from_email' => 'lemuellazaro6704@gmail.com',
            'from_name' => 'Clinic Management'
        ];
        
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $emailConfig['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $emailConfig['username'];
        $mail->Password   = $emailConfig['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
    
        // Recipients
        $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
        $mail->addAddress($to);
    
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
    
        $mail->send();
        error_log("Email sent successfully to: $to");
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>