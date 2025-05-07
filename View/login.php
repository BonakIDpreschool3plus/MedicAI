<?php
// Start session at the very beginning
session_start();

// Check if user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    if (isset($_SESSION['is_doctor']) && $_SESSION['is_doctor'] === true) {
        header("Location: Doctor/doctorDashboard.php");
    } else {
        header("Location: Patient/patientDashboard.php");
    }
    exit();
}

// Add cache control headers to prevent back button after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'css/links.html'; ?>
    <title>Login - Clinic Management</title>
    <link rel="stylesheet" href="css/styles.css" />
</head>
<body>
    <div class="container login-container">
        <div class="card">
        <div class="card-header d-flex justify-content-center align-items-center gap-2 text-white text-center p-3" style="background-color: #57DE7B;">
    <a href="guestDashboard.php">
        <img src="../Assets/images/logo.png" alt="logo" width="40" height="40" class="d-inline-block align-text-top">
    </a>
    <h3>Login to Your Account</h3>
</div>
            <div class="card-body p-4">
                <?php
                // Display error message if login failed
                if(isset($_SESSION['login_error'])) {
                    echo '<div class="alert alert-danger">' . $_SESSION['login_error'] . '</div>';
                    unset($_SESSION['login_error']);
                }
                ?>
                
                <form method="post" action="../Controller/loginController.php" novalidate class="needs-validation">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                        <div class="invalid-feedback">
                            Please enter your username
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="invalid-feedback">
                            Please enter your password
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Login As</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="userType" id="patientRadio" value="patient" checked>
                            <label class="form-check-label" for="patientRadio">
                                Patient
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="userType" id="doctorRadio" value="doctor">
                            <label class="form-check-label" for="doctorRadio">
                                Doctor
                            </label>
                        </div>
                    </div>
                    
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">Login</button>
                    </div>
                    
                    <div class="mt-3 text-center">
                        <a href="forgotPassword.php">Forgot Password?</a>
                    </div>
                    
                    <hr>
                    
                    <div class="text-center">
                        Don't have an account? <a href="signUp.php">Sign up</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'js/scripts.html'; ?>
    
    <script>
    // Simple form validation
    (function() {
        'use strict';
        const form = document.querySelector('form');
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    })();

    <?php if(isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
        // Show SweetAlert for successful logout
        document.addEventListener('DOMContentLoaded', function() {
            const userName = "<?php echo isset($_GET['user']) ? htmlspecialchars($_GET['user']) : 'User'; ?>";
            Swal.fire({
                icon: 'success',
                title: 'Logged Out Successfully',
                text: 'Goodbye, ' + userName + '! You have been safely logged out.',
                timer: 3000,
                timerProgressBar: true
            });
        });
    <?php endif; ?>

    // Display SweetAlert for password reset success
    <?php if(isset($_SESSION['reset_status']) && isset($_SESSION['reset_message']) && $_SESSION['reset_status'] == 'success'): ?>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Success!',
            text: '<?php echo $_SESSION['reset_message']; ?>',
            icon: 'success',
            confirmButtonColor: '#3085d6'
        });
    });
    <?php 
    unset($_SESSION['reset_message']);
    unset($_SESSION['reset_status']);
    endif; 
    ?>
    </script>
</body>
</html>