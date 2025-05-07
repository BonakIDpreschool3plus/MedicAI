<?php
// Start session
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'css/links.html'; ?>
    <title>Forgot Password - Clinic Management</title>
    <style>
        .forgot-password-container {
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
            padding: 20px 0 10px 0;
        }
        .btn-primary {
            border-radius: 5px;
            padding: 10px 20px;
        }
    </style>
</head>
<body>
    <div class="container forgot-password-container">
        <div class="card">
            <div class="card-header text-center">
                <h3>Reset Your Password</h3>
                <p class="text-muted">Enter your email to receive a password reset link</p>
            </div>
            <div class="card-body p-4">
                <form method="post" action="../Controller/forgotPasswordController.php" id="forgotPasswordForm" novalidate class="needs-validation">
                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="invalid-feedback">
                            Please enter a valid email address
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Send Reset Link</button>
                    </div>
                    
                    <div class="mt-3 text-center">
                        <a href="login.php">Back to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'js/scripts.html'; ?>
    
    <script>
    // Form validation
    (function() {
        'use strict';
        const form = document.getElementById('forgotPasswordForm');
        
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
        
        // Display SweetAlert for success/error messages
        <?php if(isset($_SESSION['reset_message']) && isset($_SESSION['reset_status'])): ?>
        Swal.fire({
            title: '<?php echo ($_SESSION['reset_status'] == 'success') ? 'Email Sent' : 'Error'; ?>',
            text: '<?php echo $_SESSION['reset_message']; ?>',
            icon: '<?php echo $_SESSION['reset_status']; ?>',
            confirmButtonColor: '<?php echo ($_SESSION['reset_status'] == 'success') ? '#3085d6' : '#d33'; ?>'
        });
        <?php 
        unset($_SESSION['reset_message']);
        unset($_SESSION['reset_status']);
        endif; 
        ?>

                    // Display the reset link for development purposes
            <?php if(isset($_SESSION['reset_link'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Development Mode',
                    html: 'Email sending failed, but here is your reset link:<br><br>' +
                        '<a href="<?php echo $_SESSION['reset_link']; ?>" target="_blank">' +
                        'Click here to reset your password</a><br><br>' +
                        'In production, this would be sent via email.',
                    icon: 'info',
                    confirmButtonColor: '#3085d6'
                });
            });
            <?php unset($_SESSION['reset_link']); ?>
            <?php endif; ?>
    })();
    </script>
</body>
</html>