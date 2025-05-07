<?php
// Include database connection
require_once '../Model/config.php';
session_start(); // Add this line to make sure session is started

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Sanitize input data
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = $_POST['password']; // Don't sanitize password before hashing
        $confirmPassword = $_POST['confirmPassword'];
        $firstName = filter_input(INPUT_POST, 'firstName', FILTER_SANITIZE_STRING);
        $middleName = filter_input(INPUT_POST, 'middleName', FILTER_SANITIZE_STRING) ?: null;
        $lastName = filter_input(INPUT_POST, 'lastName', FILTER_SANITIZE_STRING);
        $birthday = filter_input(INPUT_POST, 'birthday', FILTER_SANITIZE_STRING);
        $sex = filter_input(INPUT_POST, 'sex', FILTER_SANITIZE_STRING);
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING) ?: null;
        $officeAddress = filter_input(INPUT_POST, 'officeAddress', FILTER_SANITIZE_STRING) ?: null;
        $occupation = filter_input(INPUT_POST, 'occupation', FILTER_SANITIZE_STRING) ?: null;
        $facebookLink = filter_input(INPUT_POST, 'facebookLink', FILTER_SANITIZE_URL) ?: null;
        
        // Default profile image
        $defaultProfileImage = '../../assets/images/default-profile.png';

        // Validate required fields
        if (!$username || !$password || !$firstName || !$lastName || !$birthday || !$sex || !$status || !$email || !$phone) {
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
        $checkUsernameStmt = $conn->prepare("SELECT username FROM patient_creds WHERE username = ?");
        $checkUsernameStmt->bind_param("s", $username);
        $checkUsernameStmt->execute();
        $result = $checkUsernameStmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Username already taken. Please choose another one.");
        }
        $checkUsernameStmt->close();

        // Check if email already exists
        $checkEmailStmt = $conn->prepare("SELECT email FROM patient_creds WHERE email = ?");
        $checkEmailStmt->bind_param("s", $email);
        $checkEmailStmt->execute();
        $result = $checkEmailStmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Email address already registered.");
        }
        $checkEmailStmt->close();

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Prepare SQL statement to insert data - UPDATED WITH profile_image
        $sql = "INSERT INTO patient_creds (username, password, firstName, middleName, lastName, birthday, sex, status, email, phone_number, address, office_address, occupation, facebook_link, profile_image) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssssssss", 
            $username,
            $hashedPassword,
            $firstName, 
            $middleName, 
            $lastName, 
            $birthday, 
            $sex, 
            $status, 
            $email, 
            $phone, 
            $address, 
            $officeAddress, 
            $occupation, 
            $facebookLink,
            $defaultProfileImage
        );

        // Execute query
        if ($stmt->execute()) {
            // Get the newly created user ID
            $userId = $conn->insert_id;
            
            // Create the assets/images directory if it doesn't exist
            $directory = '../assets/images';
            if (!file_exists($directory)) {
                mkdir($directory, 0777, recursive: true);
            }
            
            // Set session variables for the newly registered user
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['full_name'] = $firstName . ' ' . $lastName;
            $_SESSION['profile_image'] = $defaultProfileImage;
            
            // Set success flag for modal
            $_SESSION['signup_success'] = true;
            $_SESSION['patient_name'] = $firstName . ' ' . $lastName;
            header("Location: ../View/signUp.php?success=1");
            exit();
        } else {
            throw new Exception("Error: " . $stmt->error);
        }

        $stmt->close();
        
    } catch (Exception $e) {
        // Set error message and redirect back to form
        $_SESSION['signup_error'] = $e->getMessage();
        header("Location: ../View/signUp.php");
        exit();
    }
    
    // Close connection
    $conn->close();
} else {
    // If not a POST request, redirect to the signup form
    header("Location: ../View/signUp.php");
    exit();
}
?>