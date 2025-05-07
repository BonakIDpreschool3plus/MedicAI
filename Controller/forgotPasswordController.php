<?php
// Start session
session_start();

// Include database connection
require_once '../Model/config.php';
date_default_timezone_set('UTC');
// Add Composer autoloader
require_once '../vendor/autoload.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize input
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['reset_status'] = 'error';
        $_SESSION['reset_message'] = 'Please enter a valid email address.';
        header("Location: ../View/forgotPassword.php");
        exit();
    }
    
    try {
        // Check if email exists in database
        $stmt = $conn->prepare("SELECT id, username, firstName, lastName FROM patient_creds WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Don't reveal that email doesn't exist (security best practice)
            $_SESSION['reset_status'] = 'success';
            $_SESSION['reset_message'] = 'If your email exists in our system, you will receive a password reset link shortly.';
            header("Location: ../View/forgotPassword.php");
            exit();
        }
        
        // Get user information
        $user = $result->fetch_assoc();
        $userId = $user['id'];
        $username = $user['username'];
        $fullName = $user['firstName'] . ' ' . $user['lastName'];
        
        // Generate unique token
        $token = bin2hex(random_bytes(32));
        $tokenHash = password_hash($token, PASSWORD_DEFAULT);
        
    
        // Set expiration time (24 hours from now)
        $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // If you want to be extra careful, you can sync with the database time
        $timeStmt = $conn->query("SELECT NOW() as db_time");
        $timeRow = $timeStmt->fetch_assoc();
        $dbTime = $timeRow['db_time'];
        $expiry = date('Y-m-d H:i:s', strtotime($dbTime . ' +24 hours'));
        
        // Delete any existing tokens for this user
        $deleteStmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?");
        $deleteStmt->bind_param("i", $userId);
        $deleteStmt->execute();
        
        // Store token in database
        $insertStmt = $conn->prepare("INSERT INTO password_reset_tokens (user_id, token_hash, expiry) VALUES (?, ?, ?)");
        $insertStmt->bind_param("iss", $userId, $tokenHash, $expiry);
        
        if ($insertStmt->execute()) {
            // Create reset URL
            $resetUrl = "http://" . $_SERVER['HTTP_HOST'] . 
                         dirname($_SERVER['PHP_SELF']) . 
                         "/../View/resetPassword.php?token=" . $token . "&email=" . urlencode($email);
            
            // Email content
            $subject = "Password Reset - Clinic Management System";
            $message = "
            <html>
            <head>
                <title>Password Reset Request</title>
            </head>
            <body>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
                    <div style='background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;'>
                        <h2 style='color: #0d6efd; margin-top: 0;'>Password Reset Request</h2>
                    </div>
                    
                    <p>Hello $fullName,</p>
                    
                    <p>We received a request to reset your password for your account at Clinic Management System.</p>
                    
                    <p>To reset your password, please click the button below:</p>
                    
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='$resetUrl' style='background-color: #0d6efd; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Reset My Password</a>
                    </p>
                    
                    <p>Alternatively, you can copy and paste the following link in your browser:</p>
                    <p style='word-break: break-all;'><a href='$resetUrl'>$resetUrl</a></p>
                    
                    <p>This link will expire in 1 hour for security reasons.</p>
                    
                    <p>If you did not request this password reset, please ignore this email and your password will remain unchanged.</p>
                    
                    <div style='margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; color: #777; font-size: 12px;'>
                        <p>This is an automated email, please do not reply.</p>
                        <p>&copy; " . date('Y') . " Clinic Management System. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // Create a new PHPMailer instance
            $mail = new PHPMailer(true);
            
            try {
                // Server settings for Gmail
                // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'lemuellazaro6704@gmail.com'; // CHANGE THIS
                $mail->Password   = 'khnn crik uacj vdar';    // CHANGE THIS - use app password for Gmail
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                
                // Recipients
                $mail->setFrom('lemuellazaro6704@gmail.com', 'Clinic Management System'); // CHANGE THIS
                $mail->addAddress($email, $fullName);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $message;
                
                // Send email
                $mail->send();
                
                $_SESSION['reset_status'] = 'success';
                $_SESSION['reset_message'] = 'Password reset link has been sent to your email.';
            } catch (Exception $e) {
                // For development purposes, store the reset link in session
                $_SESSION['reset_status'] = 'success';
                $_SESSION['reset_message'] = "Mailer Error: {$mail->ErrorInfo}, but we've saved your reset link for testing.";
                $_SESSION['reset_link'] = $resetUrl;
            }
        } else {
            $_SESSION['reset_status'] = 'error';
            $_SESSION['reset_message'] = 'An error occurred. Please try again later.';
        }
        
        header("Location: ../View/forgotPassword.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['reset_status'] = 'error';
        $_SESSION['reset_message'] = 'An error occurred: ' . $e->getMessage();
        header("Location: ../View/forgotPassword.php");
        exit();
    }
    
    // Close database connection
    $stmt->close();
    $conn->close();
} else {
    // If not a POST request, redirect to forgot password page
    header("Location: ../View/forgotPassword.php");
    exit();
}
?>