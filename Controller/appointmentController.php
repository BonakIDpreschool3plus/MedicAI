<?php
// filepath: c:\xampp\htdocs\clinicManagement\Controller\appointmentController.php
// At the top of the file, after session_start():
require_once '../Model/config.php';
require '../vendor/autoload.php'; // Add this line to load PHPMailer

// Import the PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// DEBUG: Log form submission (uncomment to debug)
// file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . ' - Form received: ' . print_r($_POST, true) . "\n", FILE_APPEND);
// file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . ' - Files received: ' . print_r($_FILES, true) . "\n", FILE_APPEND);

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: ../View/login.php");
    exit();
}

// Process appointment booking
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == "book") {
    try {
        // Get form data
        $userId = $_POST['userId'];
        $appointmentDate = $_POST['appointment_date'];
        $appointmentTime = $_POST['appointment_time'];
        
        // Validate required data
        if (empty($appointmentDate) || empty($appointmentTime)) {
            throw new Exception("Please select both date and time for your appointment.");
        }
        
        // Check if the appointment date is in the past
        if (strtotime($appointmentDate) < strtotime(date('Y-m-d'))) {
            throw new Exception("Cannot book appointments for past dates.");
        }
        
        /// Update the validation section that checks for already booked slots:

        // Check if the slot is already booked (only check confirmed/completed appointments)
        $checkStmt = $conn->prepare("
            SELECT id FROM appointments 
            WHERE appointment_date = ? 
            AND appointment_time = ? 
            AND (status = 'confirmed' OR status = 'completed')
        ");
        $checkStmt->bind_param("ss", $appointmentDate, $appointmentTime);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $_SESSION['appointment_error'] = "This time slot is already booked. Please select another time.";
            header("Location: ../View/Patient/bookAppointment.php");
            exit();
        }
                // Handle file upload
        $filePath = null;
        if (isset($_FILES['medical_records']) && $_FILES['medical_records']['size'] > 0) {
            $targetDir = "../uploads/medical_records/";
            
            // Create directory if it doesn't exist
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            $fileName = time() . '_' . basename($_FILES["medical_records"]["name"]);
            $targetFilePath = $targetDir . $fileName;
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
            
            // Allow certain file formats
            $allowTypes = array('pdf', 'jpg', 'jpeg', 'png');
            if (in_array(strtolower($fileType), $allowTypes)) {
                // Check file size (5MB max)
                if ($_FILES["medical_records"]["size"] <= 5000000) {
                    if (move_uploaded_file($_FILES["medical_records"]["tmp_name"], $targetFilePath)) {
                        $filePath = $fileName;
                    } else {
                        throw new Exception("Error uploading file.");
                    }
                } else {
                    throw new Exception("File is too large. Maximum size is 5MB.");
                }
            } else {
                throw new Exception("Only PDF, JPG, JPEG, and PNG files are allowed.");
            }
        }
        
        // Insert appointment into database
        $stmt = $conn->prepare("INSERT INTO appointments (user_id, appointment_date, appointment_time, medical_records, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("isss", $userId, $appointmentDate, $appointmentTime, $filePath);
        
        if ($stmt->execute()) {
            $_SESSION['appointment_success'] = true;
            
            // Get the appointment ID for the confirmation message
            $appointmentId = $stmt->insert_id;
            
            // Format time for display
            $formattedTime = date("h:i A", strtotime($appointmentTime));
            $formattedDate = date("l, F j, Y", strtotime($appointmentDate));
            
            // Set success message
            $_SESSION['success_message'] = "Your appointment has been successfully booked for $formattedDate at $formattedTime.";
            $_SESSION['appointment_id'] = $appointmentId;
            
            // Send email notification to doctor(s)
            sendAppointmentNotificationEmail($conn, $userId, $appointmentId, $appointmentDate, $appointmentTime);
            
            // Redirect back to booking page with success parameter
            header("Location: ../View/Patient/bookAppointment.php?booking_success=1");
            exit();
        } else {
            throw new Exception("Error booking appointment: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        $_SESSION['appointment_error'] = $e->getMessage();
        header("Location: ../View/Patient/bookAppointment.php");
        exit();
    }
}

// Process appointment cancellation
else if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == "cancel") {
    try {
        // Check if appointment ID is provided
        if (!isset($_POST['id'])) {
            throw new Exception("Missing appointment identifier");
        }
        
        $appointmentId = $_POST['id'];
        $userId = $_SESSION['user_id'];
        
        // Check if appointment belongs to the user
        $checkStmt = $conn->prepare("SELECT id FROM appointments WHERE id = ? AND user_id = ?");
        $checkStmt->bind_param("ii", $appointmentId, $userId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows == 0) {
            throw new Exception("You don't have permission to cancel this appointment.");
        }
        
        // Update appointment status to cancelled
        $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $appointmentId);
        
        if ($stmt->execute()) {
            $_SESSION['cancel_success'] = true;
            $_SESSION['success_message'] = "Your appointment has been successfully cancelled.";
            header("Location: ../View/Patient/myAppointments.php");
            exit();
        } else {
            throw new Exception("Error cancelling appointment: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        $_SESSION['appointment_error'] = $e->getMessage();
        header("Location: ../View/Patient/myAppointments.php");
        exit();
    }
}

else if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == "reschedule") {
    try {
        if (!isset($_POST['id']) || empty($_POST['appointment_date']) || empty($_POST['appointment_time'])) {
            throw new Exception("Missing required fields.");
        }

        $appointmentId = $_POST['id'];
        $appointmentDate = $_POST['appointment_date'];
        $appointmentTime = $_POST['appointment_time'];
        $userId = $_SESSION['user_id'];

        // Check if the appointment belongs to the user
        $stmt = $conn->prepare("SELECT id FROM appointments WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $appointmentId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            throw new Exception("You don't have permission to reschedule this appointment.");
        }

        // Check if the new time slot is available
        $stmt = $conn->prepare("SELECT id FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled' AND id != ?");
        $stmt->bind_param("ssi", $appointmentDate, $appointmentTime, $appointmentId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            throw new Exception("The selected time slot is already booked. Please choose another time.");
        }

        // Update the appointment
        $stmt = $conn->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssi", $appointmentDate, $appointmentTime, $appointmentId);

        if ($stmt->execute()) {
            $_SESSION['reschedule_success'] = true;
            
            // Format time for display in success message
            $formattedTime = date("h:i A", strtotime($appointmentTime));
            $formattedDate = date("l, F j, Y", strtotime($appointmentDate));
            
            $_SESSION['success_message'] = "Your appointment has been successfully rescheduled to $formattedDate at $formattedTime.";
            
            // Send email notification about rescheduled appointment
            sendRescheduledAppointmentEmail($conn, $userId, $appointmentId, $appointmentDate, $appointmentTime);
            
            header("Location: ../View/Patient/myAppointments.php");
            exit();
        } else {
            throw new Exception("Error rescheduling appointment.");
        }
    } catch (Exception $e) {
        $_SESSION['appointment_error'] = $e->getMessage();
        header("Location: ../View/Patient/rescheduleAppointment.php?id=" . $_POST['id']); // Fixed the path
        exit();
    }
}
// List upcoming appointments
else if (isset($_GET['action']) && $_GET['action'] == "list") {
    try {
        $userId = $_SESSION['user_id'];
        $currentDate = date('Y-m-d');
        
        // Get all upcoming appointments for the user
        $stmt = $conn->prepare("SELECT * FROM appointments WHERE user_id = ? AND appointment_date >= ? AND status != 'cancelled' ORDER BY appointment_date ASC, appointment_time ASC");
        $stmt->bind_param("is", $userId, $currentDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $appointments = array();
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
        
        echo json_encode($appointments);
        exit();
        
    } catch (Exception $e) {
        echo json_encode(array('error' => $e->getMessage()));
        exit();
    }
}

/**
 * Send email notification to doctor(s) about new appointment
 * 
 * @param mysqli $conn Database connection
 * @param int $patientId Patient ID
 * @param int $appointmentId Appointment ID
 * @param string $appointmentDate Appointment date
 * @param string $appointmentTime Appointment time
 * @return bool Success status
 */
function sendAppointmentNotificationEmail($conn, $patientId, $appointmentId, $appointmentDate, $appointmentTime) {
    try {
        // Get patient details (same code as before)
        $patientStmt = $conn->prepare("SELECT firstName, lastName, email, phone_number FROM patient_creds WHERE id = ?");
        $patientStmt->bind_param("i", $patientId);
        $patientStmt->execute();
        $patientResult = $patientStmt->get_result();
        $patient = $patientResult->fetch_assoc();
        
        if (!$patient) {
            error_log("Patient not found for email notification: Patient ID $patientId");
            return false;
        }
        
        // Get doctor email(s) (same code as before)
        $doctorStmt = $conn->prepare("SELECT email FROM doctor_creds");
        $doctorStmt->execute();
        $doctorResult = $doctorStmt->get_result();
        
        if ($doctorResult->num_rows === 0) {
            error_log("No doctors found for email notification");
            return false;
        }
        
        // Format date and time for email (same code as before)
        $formattedDate = date("l, F j, Y", strtotime($appointmentDate));
        $formattedTime = date("h:i A", strtotime($appointmentTime));
        
        // Email subject (same as before)
        $subject = "New Appointment Booking - #$appointmentId";
        
        // Create email body (same HTML as before)
        $message = "
        <html>
        <head>
            <title>New Appointment Notification</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #0d6efd; color: white; padding: 10px 20px; text-align: center; }
                .content { padding: 20px; border: 1px solid #ddd; }
                .appointment-details { background-color: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 4px solid #0d6efd; }
                .footer { font-size: 12px; color: #6c757d; margin-top: 20px; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>New Appointment Notification</h2>
                </div>
                <div class='content'>
                    <p>Dear Doctor,</p>
                    <p>A new appointment has been booked in the Clinic Management System.</p>
                    
                    <div class='appointment-details'>
                        <h3>Appointment Details:</h3>
                        <p><strong>Appointment ID:</strong> #$appointmentId</p>
                        <p><strong>Date:</strong> $formattedDate</p>
                        <p><strong>Time:</strong> $formattedTime</p>
                        <p><strong>Status:</strong> Pending</p>
                    </div>
                    
                    <div class='appointment-details'>
                        <h3>Patient Information:</h3>
                        <p><strong>Name:</strong> {$patient['firstName']} {$patient['lastName']}</p>
                        <p><strong>Email:</strong> {$patient['email']}</p>
                        <p><strong>Phone:</strong> {$patient['phone_number']}</p>
                    </div>
                    
                    <p>Please log in to the <a href='http://localhost/clinicManagement/View/doctorLogin.php'>Clinic Management System</a> to manage this appointment.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Send email to each doctor using PHPMailer
        $emailsSent = 0;
        while ($doctor = $doctorResult->fetch_assoc()) {
            // Create a new PHPMailer instance
            $mail = new PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();                                      // Use SMTP
                $mail->Host       = 'smtp.gmail.com';                 // SMTP server (use your email provider's SMTP)
                $mail->SMTPAuth   = true;                             // Enable SMTP authentication
                $mail->Username   = 'lemuellazaro6704@gmail.com';           // SMTP username (your email)
                $mail->Password   = 'khnn crik uacj vdar';              // SMTP password (use app password for Gmail)
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // Enable TLS encryption
                $mail->Port       = 587;                              // TCP port to connect to
            
                // Recipients
                $mail->setFrom('lemuellazaro6704@gmail.com', 'Clinic Management');
                $mail->addAddress($doctor['email']);                  // Add recipient
            
                // Content
                $mail->isHTML(true);                                  // Set email format to HTML
                $mail->Subject = $subject;
                $mail->Body    = $message;
            
                $mail->send();
                $emailsSent++;
            } catch (Exception $e) {
                error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
            }
        }
        
        if ($emailsSent > 0) {
            error_log("Appointment notification emails sent successfully to $emailsSent doctors");
            return true;
        } else {
            error_log("Failed to send appointment notification emails");
            return false;
        }
    } catch (Exception $e) {
        error_log("Error sending appointment notification email: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email notification to doctor(s) about rescheduled appointment
 * 
 * @param mysqli $conn Database connection
 * @param int $patientId Patient ID
 * @param int $appointmentId Appointment ID
 * @param string $appointmentDate New appointment date
 * @param string $appointmentTime New appointment time
 * @return bool Success status
 */
function sendRescheduledAppointmentEmail($conn, $patientId, $appointmentId, $appointmentDate, $appointmentTime) {
    try {
        // Get patient details
        $patientStmt = $conn->prepare("SELECT firstName, lastName, email, phone_number FROM patient_creds WHERE id = ?");
        $patientStmt->bind_param("i", $patientId);
        $patientStmt->execute();
        $patientResult = $patientStmt->get_result();
        $patient = $patientResult->fetch_assoc();
        
        if (!$patient) {
            error_log("Patient not found for reschedule email notification: Patient ID $patientId");
            return false;
        }
        
        // Get doctor email(s)
        $doctorStmt = $conn->prepare("SELECT email FROM doctor_creds");
        $doctorStmt->execute();
        $doctorResult = $doctorStmt->get_result();
        
        if ($doctorResult->num_rows === 0) {
            error_log("No doctors found for reschedule email notification");
            return false;
        }
        
        // Format date and time for email
        $formattedDate = date("l, F j, Y", strtotime($appointmentDate));
        $formattedTime = date("h:i A", strtotime($appointmentTime));
        
        // Email subject
        $subject = "Appointment Rescheduled - #$appointmentId";
        
        // Email headers
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Clinic Management <noreply@clinicmanagement.com>" . "\r\n";
        
        // Email body
        $message = "
        <html>
        <head>
            <title>Appointment Reschedule Notification</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #ffc107; color: #212529; padding: 10px 20px; text-align: center; }
                .content { padding: 20px; border: 1px solid #ddd; }
                .appointment-details { background-color: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 4px solid #ffc107; }
                .footer { font-size: 12px; color: #6c757d; margin-top: 20px; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Appointment Reschedule Notification</h2>
                </div>
                <div class='content'>
                    <p>Dear Doctor,</p>
                    <p>An appointment has been rescheduled in the Clinic Management System.</p>
                    
                    <div class='appointment-details'>
                        <h3>Updated Appointment Details:</h3>
                        <p><strong>Appointment ID:</strong> #$appointmentId</p>
                        <p><strong>New Date:</strong> $formattedDate</p>
                        <p><strong>New Time:</strong> $formattedTime</p>
                    </div>
                    
                    <div class='appointment-details'>
                        <h3>Patient Information:</h3>
                        <p><strong>Name:</strong> {$patient['firstName']} {$patient['lastName']}</p>
                        <p><strong>Email:</strong> {$patient['email']}</p>
                        <p><strong>Phone:</strong> {$patient['phone_number']}</p>
                    </div>
                    
                    <p>Please log in to the <a href='http://localhost/clinicManagement/View/doctorLogin.php'>Clinic Management System</a> to view this updated appointment.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Send email to each doctor
        $emailsSent = 0;
        while ($doctor = $doctorResult->fetch_assoc()) {
            if (mail($doctor['email'], $subject, $message, $headers)) {
                $emailsSent++;
            }
        }
        
        if ($emailsSent > 0) {
            error_log("Appointment reschedule emails sent successfully to $emailsSent doctors");
            return true;
        } else {
            error_log("Failed to send appointment reschedule emails");
            return false;
        }
    } catch (Exception $e) {
        error_log("Error sending appointment reschedule email: " . $e->getMessage());
        return false;
    }
}

if (!isset($_SERVER["REQUEST_METHOD"]) || 
    ($_SERVER["REQUEST_METHOD"] == "POST" && (!isset($_POST['action']) || 
    ($_POST['action'] != "book" && $_POST['action'] != "cancel" && $_POST['action'] != "reschedule"))) || 
    ($_SERVER["REQUEST_METHOD"] == "GET" && (!isset($_GET['action']) || $_GET['action'] != "list"))) {
    header("Location: ../View/Patient/patientDashboard.php");
    exit();
}
?>