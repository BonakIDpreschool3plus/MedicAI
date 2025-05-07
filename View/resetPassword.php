<?php


// Include database connection
require_once '../Model/config.php';

date_default_timezone_set('UTC');
// Check if token and email are provided
if (!isset($_GET['token']) || !isset($_GET['email'])) {
    header("Location: forgotPassword.php");
    exit();
}

$token = $_GET['token'];
$email = $_GET['email'];

// Validate token
$isValidToken = false;
$userId = null;
$debugInfo = []; // For debugging

try {
    // Get user ID from email
    $userStmt = $conn->prepare("SELECT id FROM patient_creds WHERE email = ?");
    $userStmt->bind_param("s", $email);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    
    if ($userResult->num_rows === 1) {
        $user = $userResult->fetch_assoc();
        $userId = $user['id'];
        $debugInfo['userId'] = $userId;
        
        // Modify this query to get all tokens and check expiry in PHP
        $tokenStmt = $conn->prepare("SELECT token_hash, expiry FROM password_reset_tokens WHERE user_id = ?");
        $tokenStmt->bind_param("i", $userId);
        $tokenStmt->execute();
        $tokenResult = $tokenStmt->get_result();
        
        if ($tokenResult->num_rows > 0) {
            $tokenRow = $tokenResult->fetch_assoc();
            $debugInfo['expiry'] = $tokenRow['expiry'];
            $debugInfo['current_time'] = date('Y-m-d H:i:s');
            
            // Check if token is expired
            $expiryTime = strtotime($tokenRow['expiry']);
            $currentTime = time();
            $isExpired = ($expiryTime < $currentTime);
            $debugInfo['is_expired'] = $isExpired ? 'Yes' : 'No';
            
            // Verify token hash
            if (!$isExpired && password_verify($token, $tokenRow['token_hash'])) {
                $isValidToken = true;
            }
        }
    }
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Reset Password Error: " . $e->getMessage());
}

// You can uncomment this for debugging
// $_SESSION['debug_info'] = $debugInfo;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'css/links.html'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Reset Password - Clinic Management</title>
    <style>
        .reset-password-container {
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
    <div class="container reset-password-container">
        <div class="card">
            <div class="card-header text-center">
                <h3>Reset Your Password</h3>
                <p class="text-muted">Enter your new password below</p>
            </div>
            <div class="card-body p-4">
                <!-- Uncomment for debugging -->
                <?php /*if(isset($_SESSION['debug_info'])): ?>
                <div class="alert alert-warning">
                    <h5>Debug Info:</h5>
                    <pre><?php print_r($_SESSION['debug_info']); ?></pre>
                </div>
                <?php unset($_SESSION['debug_info']); endif;*/ ?>
                
                <?php if ($isValidToken): ?>
                <form method="post" action="../Controller/resetPasswordController.php" id="resetPasswordForm" novalidate class="needs-validation">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="invalid-feedback">
                            Password must be at least 8 characters
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirmPassword" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                        <div class="invalid-feedback">
                            Passwords do not match
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-danger">
                    <h5 class="alert-heading">Invalid or Expired Link</h5>
                    <p>The password reset link is invalid or has expired. Please request a new one.</p>
                    <hr>
                    <a href="forgotPassword.php" class="btn btn-outline-danger">Request New Link</a>
                </div>
                <?php endif; ?>
                
                <div class="mt-3 text-center">
                    <a href="login.php">Back to Login</a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'js/scripts.html'; ?>
    
    <script>
    // Form validation
    (function() {
        'use strict';
        
        <?php if ($isValidToken): ?>
        const form = document.getElementById('resetPasswordForm');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirmPassword');
        
        // Validate password requirements
        password.addEventListener('input', function() {
            if (this.value.length < 8) {
                this.setCustomValidity('Password must be at least 8 characters');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Validate password match
        confirmPassword.addEventListener('input', function() {
            if (this.value !== password.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            // Additional manual validation
            if (password.value.length < 8) {
                event.preventDefault();
                Swal.fire({
                    title: 'Password Too Short',
                    text: 'Password must be at least 8 characters',
                    icon: 'error'
                });
                return;
            }
            
            if (password.value !== confirmPassword.value) {
                event.preventDefault();
                Swal.fire({
                    title: 'Passwords Do Not Match',
                    text: 'Please make sure your passwords match',
                    icon: 'error'
                });
                return;
            }
            
            form.classList.add('was-validated');
        }, false);
        <?php endif; ?>
        
        // Display SweetAlert for error messages
        <?php if(isset($_SESSION['reset_message']) && isset($_SESSION['reset_status'])): ?>
        Swal.fire({
            title: '<?php echo ($_SESSION['reset_status'] == 'success') ? 'Success' : 'Error'; ?>',
            text: '<?php echo $_SESSION['reset_message']; ?>',
            icon: '<?php echo $_SESSION['reset_status']; ?>',
            confirmButtonColor: '<?php echo ($_SESSION['reset_status'] == 'success') ? '#3085d6' : '#d33'; ?>'
        }).then((result) => {
            <?php if($_SESSION['reset_status'] == 'success'): ?>
            window.location.href = 'login.php';
            <?php endif; ?>
        });
        <?php 
        unset($_SESSION['reset_message']);
        unset($_SESSION['reset_status']);
        endif; 
        ?>
    })();
    </script>
</body>
</html>