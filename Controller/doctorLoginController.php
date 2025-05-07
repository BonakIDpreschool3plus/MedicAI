<?php
// Start session
session_start();

// Include database connection
require_once '../Model/config.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize input
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password']; // Don't sanitize password before verification
    $rememberMe = isset($_POST['rememberMe']);
    
    // Validate input
    if (empty($username) || empty($password)) {
        $_SESSION['doctor_login_error'] = "Username and password are required";
        header("Location: ../View/doctorLogin.php");
        exit();
    }
    
    try {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, username, password, firstName, lastName, specialty FROM doctor_creds WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Check if user exists
        if ($result->num_rows === 1) {
            $doctor = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $doctor['password'])) {
                // Password is correct, create session
                $_SESSION['doctor_id'] = $doctor['id'];
                $_SESSION['doctor_username'] = $doctor['username'];
                $_SESSION['doctor_name'] = $doctor['firstName'] . ' ' . $doctor['lastName'];
                $_SESSION['doctor_specialty'] = $doctor['specialty'];
                $_SESSION['is_doctor'] = true;
                $_SESSION['is_logged_in'] = true;
                $_SESSION['first_login'] = true; // Set flag for first login
                
                // Set remember me cookie if selected
                if ($rememberMe) {
                    $token = bin2hex(random_bytes(32));
                    
                    // Store token in database
                    $tokenHash = password_hash($token, PASSWORD_DEFAULT);
                    $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                    
                    $tokenStmt = $conn->prepare("INSERT INTO auth_tokens (user_id, token_hash, expiry, user_type) VALUES (?, ?, ?, 'doctor')");
                    $tokenStmt->bind_param("iss", $doctor['id'], $tokenHash, $expiry);
                    $tokenStmt->execute();
                    
                    // Set cookie with token
                    setcookie('doctor_remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                    setcookie('doctor_remember_user', $doctor['id'], time() + (30 * 24 * 60 * 60), '/', '', false, true);
                }
                
                // Redirect to doctor dashboard
                header("Location: ../View/Doctor/doctorDashboard.php");
                exit();
            } else {
                $_SESSION['doctor_login_error'] = "Invalid username or password";
                header("Location: ../View/doctorLogin.php");
                exit();
            }
        } else {
            // User doesn't exist
            $_SESSION['doctor_login_error'] = "Invalid username or password";
            header("Location: ../View/doctorLogin.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['doctor_login_error'] = "Login failed: " . $e->getMessage();
        header("Location: ../View/doctorLogin.php");
        exit();
    }
    
    // Close connection
    $stmt->close();
    $conn->close();
} else {
    // If not a POST request, redirect to login page
    header("Location: ../View/doctorLogin.php");
    exit();
}
?>