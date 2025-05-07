<?php
// filepath: c:\xampp\htdocs\clinicManagement\Controller\resetPasswordController.php
// Start session
session_start();

// Include database connection
require_once '../Model/config.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize input
    $token = $_POST['token'];
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    
    // Validate input
    if (empty($token) || empty($email) || empty($userId) || empty($password) || empty($confirmPassword)) {
        $_SESSION['reset_status'] = 'error';
        $_SESSION['reset_message'] = 'All fields are required.';
        header("Location: ../View/resetPassword.php?token=$token&email=$email");
        exit();
    }
    
    // Validate password length
    if (strlen($password) < 8) {
        $_SESSION['reset_status'] = 'error';
        $_SESSION['reset_message'] = 'Password must be at least 8 characters.';
        header("Location: ../View/resetPassword.php?token=$token&email=$email");
        exit();
    }
    
    // Validate password match
    if ($password !== $confirmPassword) {
        $_SESSION['reset_status'] = 'error';
        $_SESSION['reset_message'] = 'Passwords do not match.';
        header("Location: ../View/resetPassword.php?token=$token&email=$email");
        exit();
    }
    
    try {
        // Hash the new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Update user password
        $updateStmt = $conn->prepare("UPDATE patient_creds SET password = ? WHERE id = ?");
        $updateStmt->bind_param("si", $hashedPassword, $userId);
        
            // Replace these lines in resetPasswordController.php
        if ($updateStmt->execute()) {
            // Delete used token
            $deleteStmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?");
            $deleteStmt->bind_param("i", $userId);
            $deleteStmt->execute();
            
            // Set success message
            $_SESSION['reset_status'] = 'success';
            $_SESSION['reset_message'] = 'Your password has been reset successfully. You can now login with your new password.';
            
            // Redirect to login page
            header("Location: ../View/login.php");
            exit();
        } else {
            $_SESSION['reset_status'] = 'error';
            $_SESSION['reset_message'] = 'Failed to update password. Please try again.';
            header("Location: ../View/resetPassword.php?token=$token&email=$email");
            exit();
        }
        
    } catch (Exception $e) {
        $_SESSION['reset_status'] = 'error';
        $_SESSION['reset_message'] = 'An error occurred: ' . $e->getMessage();
        header("Location: ../View/resetPassword.php?token=$token&email=$email");
        exit();
    }
    
    // Close database connection
    $conn->close();
} else {
    // If not a POST request, redirect to forgot password page
    header("Location: ../View/forgotPassword.php");
    exit();
}
?>