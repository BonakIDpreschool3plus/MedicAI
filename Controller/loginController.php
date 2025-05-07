<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../Model/config.php';

// Check if form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $username = $_POST['username'];
    $password = $_POST['password'];
    $rememberMe = isset($_POST['rememberMe']);
    $userType = $_POST['userType']; // Added for determining login type
    
    // Validate input
    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "Please enter both username and password";
        header("Location: ../View/login.php");
        exit();
    }
    
    // Check if logging in as doctor or patient
    if ($userType === 'doctor') {
        // DOCTOR LOGIN FLOW
        
        // Prepare SQL to check credentials - include specialty
        $stmt = $conn->prepare("SELECT id, username, password, firstName, lastName, specialty, status, is_admin FROM doctor_creds WHERE username = ?");
        
        // Check if the prepare failed
        if (!$stmt) {
            // Log the error and show an informative message
            error_log("SQL Error: " . $conn->error);
            die("Error preparing SQL query for doctor credentials.");
        }
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Check if the account is approved
            if ($user['status'] != 'approved') {
                $_SESSION['login_error'] = "Your account is pending approval. Please wait for an administrator to approve your account.";
                header("Location: ../View/login.php");
                exit();
            }
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables for logged in user
                $_SESSION['is_logged_in'] = true;
                $_SESSION['is_doctor'] = true;
                $_SESSION['doctor_id'] = $user['id'];
                $_SESSION['doctor_username'] = $user['username'];
                $_SESSION['doctor_name'] = $user['firstName'] . " " . $user['lastName'];
                $_SESSION['doctor_specialty'] = $user['specialty'];
                $_SESSION['is_admin'] = $user['is_admin'] ? true : false;
                $_SESSION['first_login'] = true;
                
                // Set remember me cookie if selected
                if ($rememberMe) {
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = password_hash($token, PASSWORD_DEFAULT);
                    $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                    
                    // Delete any existing tokens
                    $deleteStmt = $conn->prepare("DELETE FROM auth_tokens WHERE user_id = ? AND is_doctor = 1");
                    $deleteStmt->bind_param("i", $user['id']);
                    $deleteStmt->execute();
                    
                    // Store token in database
                    $tokenStmt = $conn->prepare("INSERT INTO auth_tokens (user_id, token_hash, expiry, is_doctor) VALUES (?, ?, ?, 1)");
                    $tokenStmt->bind_param("iss", $user['id'], $tokenHash, $expiry);
                    $tokenStmt->execute();
                    
                    // Set cookies
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                    setcookie('remember_user', $user['id'], time() + (30 * 24 * 60 * 60), '/', '', false, true);
                    setcookie('remember_type', 'doctor', time() + (30 * 24 * 60 * 60), '/', '', false, true);
                }
                
                // Log successful login
                error_log("Doctor login successful: " . $user['username']);
                
                // Redirect to doctor dashboard
                header("Location: ../View/Doctor/doctorDashboard.php");
                exit();
            } else {
                // Password verification failed
                $_SESSION['login_error'] = "Invalid username or password";
                header("Location: ../View/login.php");
                exit();
            }
        } else {
            // No user found with that username
            $_SESSION['login_error'] = "Invalid username or password";
            header("Location: ../View/login.php");
            exit();
        }
    } else {
        // PATIENT LOGIN FLOW
        
        // Prepare SQL to check patient credentials
        $stmt = $conn->prepare("SELECT id, username, password, firstName, lastName FROM patient_creds WHERE username = ?");
        
        // Check if the prepare failed
        if (!$stmt) {
            // Log the error and show an informative message
            error_log("SQL Error: " . $conn->error);
            die("Error preparing SQL query for patient credentials.");
        }
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables for logged in user
                $_SESSION['is_logged_in'] = true;
                $_SESSION['is_doctor'] = false;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['firstName'] . " " . $user['lastName'];
                $_SESSION['first_login'] = true;
                
                // Set remember me cookie if selected
                if ($rememberMe) {
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = password_hash($token, PASSWORD_DEFAULT);
                    $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                    
                    // Delete any existing tokens
                    $deleteStmt = $conn->prepare("DELETE FROM auth_tokens WHERE user_id = ? AND is_doctor = 0");
                    $deleteStmt->bind_param("i", $user['id']);
                    $deleteStmt->execute();
                    
                    // Store token in database
                    $tokenStmt = $conn->prepare("INSERT INTO auth_tokens (user_id, token_hash, expiry, is_doctor) VALUES (?, ?, ?, 0)");
                    $tokenStmt->bind_param("iss", $user['id'], $tokenHash, $expiry);
                    $tokenStmt->execute();
                    
                    // Set cookies
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                    setcookie('remember_user', $user['id'], time() + (30 * 24 * 60 * 60), '/', '', false, true);
                    setcookie('remember_type', 'patient', time() + (30 * 24 * 60 * 60), '/', '', false, true);
                }
                
                // Log successful login
                error_log("Patient login successful: " . $user['username']);
                
                // Redirect to patient dashboard
                header("Location: ../View/Patient/patientDashboard.php");
                exit();
            } else {
                // Password verification failed
                $_SESSION['login_error'] = "Invalid username or password";
                header("Location: ../View/login.php");
                exit();
            }
        } else {
            // No user found with that username
            $_SESSION['login_error'] = "Invalid username or password";
            header("Location: ../View/login.php");
            exit();
        }
    }
} else {
    // If not a POST request, redirect to the login page
    header("Location: ../View/login.php");
    exit();
}
?>
