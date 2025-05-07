<?php
// Include database connection
require_once '../Model/config.php';
session_start(); // Make sure session is started

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Sanitize input data
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = $_POST['password']; // Don't sanitize password before hashing
        $confirmPassword = $_POST['confirmPassword'];
        $firstName = filter_input(INPUT_POST, 'firstName', FILTER_SANITIZE_STRING);
        $lastName = filter_input(INPUT_POST, 'lastName', FILTER_SANITIZE_STRING);
        $specialty = filter_input(INPUT_POST, 'specialty', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        
        // Validate required fields
        if (!$username || !$password || !$firstName || !$lastName || !$specialty || !$email || !$phone) {
            throw new Exception("Please fill all required fields.");
        }

        // Validate username length
        if (strlen($username) < 5) {
            throw new Exception("Username must be at least 5 characters long.");
        }

        // Validate password
        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long.");
        }

        // Validate password match
        if ($password !== $confirmPassword) {
            throw new Exception("Passwords do not match.");
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // Validate phone number (Philippines format)
        if (!preg_match('/^(09|\+639)\d{9}$/', $phone)) {
            throw new Exception("Invalid phone number format. Use 09XXXXXXXXX or +639XXXXXXXXX format.");
        }

        // Check if username already exists
        $checkUsernameStmt = $conn->prepare("SELECT username FROM doctor_creds WHERE username = ?");
        $checkUsernameStmt->bind_param("s", $username);
        $checkUsernameStmt->execute();
        $result = $checkUsernameStmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Username already taken. Please choose another one.");
        }
        $checkUsernameStmt->close();

        // Check if email already exists
        $checkEmailStmt = $conn->prepare("SELECT email FROM doctor_creds WHERE email = ?");
        $checkEmailStmt->bind_param("s", $email);
        $checkEmailStmt->execute();
        $result = $checkEmailStmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Email address already registered.");
        }
        $checkEmailStmt->close();

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Set default values for new columns
        $isAdmin = 0;  // Not an admin by default
        $status = 'pending';  // Requires approval

        // Prepare SQL statement to insert data (updated to include new fields)
        $sql = "INSERT INTO doctor_creds (username, password, email, firstName, lastName, specialty, phone, is_admin, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssss", 
            $username,
            $hashedPassword,
            $email,
            $firstName,
            $lastName,
            $specialty,
            $phone,
            $isAdmin,
            $status
        );

        // Execute query
        if ($stmt->execute()) {
            // Get the newly created user ID
            $doctorId = $conn->insert_id;
            
            // Notify admin doctors about the new registration
            notifyAdminDoctors($conn, $firstName, $lastName, $specialty, $email);
            
            // Set success flag for modal - but with pending status message
            $_SESSION['signup_success'] = true;
            $_SESSION['doctor_name'] = $firstName . ' ' . $lastName;
            $_SESSION['pending_approval'] = true;
            header("Location: ../View/signUp.php?success=1&pending=1");
            exit();
        } else {
            throw new Exception("Error creating account: " . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $_SESSION['signup_error'] = $e->getMessage();
        header("Location: ../View/signUp.php");
        exit();
    }
    
    // Close connection
    $conn->close();
} else {
    // If not a POST request, redirect to signup page
    header("Location: ../View/signUp.php");
    exit();
}

/**
 * Notify admin doctors about new doctor registration
 * 
 * @param mysqli $conn Database connection
 * @param string $firstName Doctor's first name
 * @param string $lastName Doctor's last name
 * @param string $specialty Doctor's specialty
 * @param string $email Doctor's email
 * @return void
 */
function notifyAdminDoctors($conn, $firstName, $lastName, $specialty, $email) {
    // Get all admin doctors' emails
    $stmt = $conn->prepare("SELECT email FROM doctor_creds WHERE is_admin = 1 AND status = 'approved'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Create email content
        $subject = "New Doctor Registration Requires Approval";
        $message = "
        <html>
        <head>
            <title>New Doctor Registration</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #57DE7B; color: white; padding: 10px 20px; text-align: center; }
                .content { padding: 20px; border: 1px solid #ddd; }
                .doctor-details { background-color: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 4px solid #57DE7B; }
                .actions { margin-top: 20px; text-align: center; }
                .btn { display: inline-block; padding: 10px 20px; margin: 0 10px; text-decoration: none; border-radius: 4px; }
                .btn-approve { background-color: #28a745; color: white; }
                .footer { font-size: 12px; color: #6c757d; margin-top: 20px; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>New Doctor Registration Requires Approval</h2>
                </div>
                <div class='content'>
                    <p>A new doctor has registered and is awaiting your approval:</p>
                    
                    <div class='doctor-details'>
                        <h3>Doctor Information:</h3>
                        <p><strong>Name:</strong> Dr. $firstName $lastName</p>
                        <p><strong>Specialty:</strong> $specialty</p>
                        <p><strong>Email:</strong> $email</p>
                    </div>
                    
                    <div class='actions'>
                        <p>Please log in to the admin panel to review and approve this registration.</p>
                        <a href='http://localhost/clinicManagement/View/Doctor/manageDoctors.php' class='btn btn-approve'>Go to Admin Panel</a>
                    </div>
                </div>
                <div class='footer'>
                    <p>This is an automated message from Clinic Management System.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Loop through admin emails and send notifications
        // Note: You should implement a proper email sending function using PHPMailer
        // This is just a placeholder for the concept
        while ($row = $result->fetch_assoc()) {
            // For now, just log that we would send an email
            error_log("Would send notification to admin: " . $row['email']);
            
            // In a real implementation, you would call your email function:
            // sendEmail($row['email'], $subject, $message);
        }
    }
    
    $stmt->close();
}
?>