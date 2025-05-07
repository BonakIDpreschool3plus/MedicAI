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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Doctor Login - Clinic Management</title>
    <style>
        .login-container {
            max-width: 450px;
            margin: 80px auto;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: none;
            padding: 20px 0 0 0;
        }
        .btn-primary {
            border-radius: 5px;
            padding: 10px 20px;
        }
        .doctor-icon {
            font-size: 3rem;
            color: #0d6efd;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="card">
            <div class="card-header text-center">
                <div class="doctor-icon">
                    <i class="bi bi-hospital"></i>
                </div>
                <h3>Doctor Login</h3>
                <p class="text-muted">Access your doctor dashboard</p>
            </div>
            <div class="card-body p-4">
                <?php
                // Display error message if login failed
                if(isset($_SESSION['doctor_login_error'])) {
                    echo '<div class="alert alert-danger">' . $_SESSION['doctor_login_error'] . '</div>';
                    unset($_SESSION['doctor_login_error']);
                }
                ?>
                
                <form method="post" action="../Controller/doctorLoginController.php" id="doctorLoginForm" novalidate class="needs-validation">
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
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe">
                        <label class="form-check-label" for="rememberMe">Remember me</label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                    
                    <div class="mt-3 text-center">
                        <a href="forgotPassword.php?type=doctor">Forgot Password?</a>
                    </div>
                    
                    <hr>
                    
                    
                </form>
            </div>
        </div>
    </div>

    <?php include 'js/scripts.html'; ?>
    
    <script>
    // Form validation
    (function() {
        'use strict';
        const form = document.getElementById('doctorLoginForm');
        
        // Display SweetAlert for login errors
        <?php if(isset($_SESSION['doctor_login_error'])): ?>
        Swal.fire({
            title: 'Login Failed',
            text: '<?php echo $_SESSION['doctor_login_error']; ?>',
            icon: 'error',
            confirmButtonColor: '#d33'
        });
        <?php unset($_SESSION['doctor_login_error']); ?>
        <?php endif; ?>
        
        // Display SweetAlert for logout success
        <?php if(isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const doctorName = "<?php echo isset($_GET['user']) ? htmlspecialchars($_GET['user']) : 'Doctor'; ?>";
            Swal.fire({
                icon: 'success',
                title: 'Logged Out Successfully',
                text: 'Goodbye, ' + doctorName + '! You have been safely logged out.',
                timerProgressBar: true
            });
        });
        <?php endif; ?>
        
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    })();
    </script>
</body>
</html>