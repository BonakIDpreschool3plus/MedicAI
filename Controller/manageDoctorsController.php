<?php
session_start();
require_once '../Model/config.php';
require '../vendor/autoload.php'; // For PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check if user is logged in and is a doctor
if (!isset($_SESSION['is_logged_in']) || !isset($_SESSION['is_doctor']) || $_SESSION['is_doctor'] !== true) {
    header("Location: ../View/login.php");
    exit();
}

// Check if the logged-in doctor is an admin using the session variable
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../View/Doctor/doctorDashboard.php");
    exit();
}

// Get the correct doctor ID from session
$doctorId = $_SESSION['doctor_id'];

// Process form actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_POST['doctor_id'])) {
    $targetDoctorId = $_POST['doctor_id'];
    $action = $_POST['action'];
    
    // Get doctor information for email
    $doctorStmt = $conn->prepare("SELECT firstName, lastName, email FROM doctor_creds WHERE id = ?");
    $doctorStmt->bind_param("i", $targetDoctorId);
    $doctorStmt->execute();
    $doctorResult = $doctorStmt->get_result();
    $targetDoctor = $doctorResult->fetch_assoc();
    
    if (!$targetDoctor) {
        $_SESSION['admin_message'] = "Doctor not found.";
        $_SESSION['admin_message_type'] = "danger";
        header("Location: ../View/Doctor/manageDoctors.php");
        exit();
    }
    
    switch ($action) {
        case 'approve':
            // Approve a doctor
            $updateStmt = $conn->prepare("UPDATE doctor_creds SET status = 'approved', updated_at = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $targetDoctorId);
            
            if ($updateStmt->execute()) {
                // Send approval email
                $emailStatus = sendDoctorStatusEmail(
                    $targetDoctor['email'], 
                    $targetDoctor['firstName'], 
                    $targetDoctor['lastName'],
                    'approved'
                );
                
                $_SESSION['admin_message'] = "Doctor account approved successfully.";
                $_SESSION['admin_message_type'] = "success";
            } else {
                $_SESSION['admin_message'] = "Error approving doctor account: " . $updateStmt->error;
                $_SESSION['admin_message_type'] = "danger";
            }
            break;
            
        case 'reject':
            // Reject a doctor
            $updateStmt = $conn->prepare("UPDATE doctor_creds SET status = 'rejected', updated_at = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $targetDoctorId);
            
            if ($updateStmt->execute()) {
                // Send rejection email
                $emailStatus = sendDoctorStatusEmail(
                    $targetDoctor['email'], 
                    $targetDoctor['firstName'], 
                    $targetDoctor['lastName'],
                    'rejected'
                );
                
                $_SESSION['admin_message'] = "Doctor application rejected.";
                $_SESSION['admin_message_type'] = "warning";
            } else {
                $_SESSION['admin_message'] = "Error rejecting doctor application: " . $updateStmt->error;
                $_SESSION['admin_message_type'] = "danger";
            }
            break;
            
        case 'make_admin':
            // Make a doctor an admin
            $updateStmt = $conn->prepare("UPDATE doctor_creds SET is_admin = 1, updated_at = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $targetDoctorId);
            
            if ($updateStmt->execute()) {
                // Send admin privilege email
                $emailStatus = sendDoctorStatusEmail(
                    $targetDoctor['email'], 
                    $targetDoctor['firstName'], 
                    $targetDoctor['lastName'],
                    'admin_granted'
                );
                
                $_SESSION['admin_message'] = "Admin privileges granted to Dr. " . $targetDoctor['lastName'] . ".";
                $_SESSION['admin_message_type'] = "success";
            } else {
                $_SESSION['admin_message'] = "Error granting admin privileges: " . $updateStmt->error;
                $_SESSION['admin_message_type'] = "danger";
            }
            break;
            
        case 'remove_admin':
            // Remove admin status
            $updateStmt = $conn->prepare("UPDATE doctor_creds SET is_admin = 0, updated_at = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $targetDoctorId);
            
            if ($updateStmt->execute()) {
                // Send admin removal email
                $emailStatus = sendDoctorStatusEmail(
                    $targetDoctor['email'], 
                    $targetDoctor['firstName'], 
                    $targetDoctor['lastName'],
                    'admin_removed'
                );
                
                $_SESSION['admin_message'] = "Admin privileges removed from Dr. " . $targetDoctor['lastName'] . ".";
                $_SESSION['admin_message_type'] = "warning";
            } else {
                $_SESSION['admin_message'] = "Error removing admin privileges: " . $updateStmt->error;
                $_SESSION['admin_message_type'] = "danger";
            }
            break;
            
        default:
            $_SESSION['admin_message'] = "Invalid action.";
            $_SESSION['admin_message_type'] = "danger";
    }
    
    header("Location: ../View/Doctor/manageDoctors.php");
    exit();
}

/**
 * Send email notification to a doctor about their account status
 * 
 * @param string $to Recipient email address
 * @param string $firstName Doctor's first name
 * @param string $lastName Doctor's last name
 * @param string $status New status (approved, rejected, admin_granted, admin_removed)
 * @return bool Success status
 */
function sendDoctorStatusEmail($to, $firstName, $lastName, $status) {
    try {
        // Create email configuration array (ideally, move this to a config file)
        $emailConfig = [
            'host' => 'smtp.gmail.com',
            'username' => 'your-email@gmail.com', // Replace with your actual email
            'password' => 'your-app-password',    // Replace with your actual app password
            'from_email' => 'your-email@gmail.com',
            'from_name' => 'Clinic Management'
        ];
        
        // Set email content based on status
        switch ($status) {
            case 'approved':
                $subject = "Your Doctor Account Has Been Approved";
                $headerColor = "#28a745";
                $headerText = "Account Approved";
                $contentText = "
                    <p>Dear Dr. $firstName $lastName,</p>
                    <p>We are pleased to inform you that your doctor account registration has been <strong>approved</strong>.</p>
                    <p>You can now log in to the Clinic Management System using your credentials.</p>
                    <div style='text-align: center; margin-top: 20px;'>
                        <a href='http://localhost/clinicManagement/View/doctorLogin.php' 
                           style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>
                           Login to Your Account
                        </a>
                    </div>
                ";
                break;
                
            case 'rejected':
                $subject = "Your Doctor Account Application Status";
                $headerColor = "#dc3545";
                $headerText = "Application Not Approved";
                $contentText = "
                    <p>Dear Dr. $firstName $lastName,</p>
                    <p>We regret to inform you that your doctor account application has not been approved at this time.</p>
                    <p>If you believe this is an error or would like more information, please contact the clinic administrator.</p>
                ";
                break;
                
            case 'admin_granted':
                $subject = "Admin Privileges Granted";
                $headerColor = "#0d6efd";
                $headerText = "Admin Privileges Granted";
                $contentText = "
                    <p>Dear Dr. $firstName $lastName,</p>
                    <p>You have been granted <strong>administrator privileges</strong> in the Clinic Management System.</p>
                    <p>As an administrator, you can now:</p>
                    <ul>
                        <li>Approve or reject new doctor registrations</li>
                        <li>Manage other doctors' admin privileges</li>
                        <li>Access additional administrative features</li>
                    </ul>
                    <p>These privileges are effective immediately upon your next login.</p>
                ";
                break;
                
            case 'admin_removed':
                $subject = "Admin Privileges Update";
                $headerColor = "#6c757d";
                $headerText = "Admin Privileges Removed";
                $contentText = "
                    <p>Dear Dr. $firstName $lastName,</p>
                    <p>This is to inform you that your administrator privileges in the Clinic Management System have been removed.</p>
                    <p>You can still access the system with regular doctor permissions.</p>
                    <p>If you have any questions, please contact the clinic administrator.</p>
                ";
                break;
                
            default:
                return false;
        }
        
        // Create email message
        $message = "
        <html>
        <head>
            <title>$subject</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: $headerColor; color: white; padding: 10px 20px; text-align: center; }
                .content { padding: 20px; border: 1px solid #ddd; }
                .footer { font-size: 12px; color: #6c757d; margin-top: 20px; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>$headerText</h2>
                </div>
                <div class='content'>
                    $contentText
                </div>
                <div class='footer'>
                    <p>This is an automated message from Clinic Management System.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = "smtp.gmail.com";
        $mail->SMTPAuth   = true;
        $mail->Username   = "lemuellazaro6704@gmail.com";
        $mail->Password   = "khnn crik uacj vdar";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
    
        // Recipients
        $mail->setFrom("lemuellazaro6704@gmail.com", "Clinic Management System");
        $mail->addAddress($to);
    
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
    
        $mail->send();
        error_log("Status email sent successfully to: $to");
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// If not a POST request, redirect to doctor management page
header("Location: ../View/Doctor/manageDoctors.php");
exit();
?>