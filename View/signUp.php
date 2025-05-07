<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'css/links.html'; 
    ?>
    <title>Sign Up</title>
</head>
<body>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
<div class="card-header d-flex justify-content-center align-items-center gap-2 text-white text-center p-3" style="background-color: #57DE7B;">
    <a href="guestDashboard.php">
        <img src="../Assets/images/logo.png" alt="logo" width="40" height="40" class="d-inline-block align-text-top">
    </a>
    <h3>Create Your Account</h3>
</div>

                    <div class="card-body">
                        <?php if(isset($_SESSION['signup_error'])): ?>
                            <div class="alert alert-danger">
                                <?php echo $_SESSION['signup_error']; unset($_SESSION['signup_error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Account Type Selection Tabs -->
                        <ul class="nav nav-tabs mb-4" id="accountTypeTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="patient-tab" data-bs-toggle="tab" data-bs-target="#patient-form" type="button" role="tab" aria-controls="patient-form" aria-selected="true">
                                    <i class="bi bi-person"></i> Patient
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="doctor-tab" data-bs-toggle="tab" data-bs-target="#doctor-form" type="button" role="tab" aria-controls="doctor-form" aria-selected="false">
                                    <i class="bi bi-hospital"></i> Doctor
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="accountTypeTabContent">
                            <!-- Patient Registration Form -->
                            <div class="tab-pane fade show active" id="patient-form" role="tabpanel" aria-labelledby="patient-tab">
                                <form method="POST" action="../Controller/signUpController.php" id="signupForm" novalidate>
                                    <input type="hidden" name="account_type" value="patient">
                                    
                                    <!-- Account Information -->
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label for="username" class="form-label">Username*</label>
                                            <input type="text" class="form-control" id="username" name="username" required>
                                            <div class="invalid-feedback">Please choose a username (min 5 characters)</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="password" class="form-label">Password*</label>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                            <div class="invalid-feedback">Password must be at least 8 characters</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="confirmPassword" class="form-label">Confirm Password*</label>
                                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                                            <div class="invalid-feedback">Passwords do not match</div>
                                        </div>
                                    </div>

                                    <!-- Rest of the patient form remains unchanged -->
                                    <!-- Name Fields -->
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label for="firstName" class="form-label">First Name*</label>
                                            <input type="text" class="form-control" id="firstName" name="firstName" required>
                                            <div class="invalid-feedback">Please enter your first name</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="middleName" class="form-label">Middle Name</label>
                                            <input type="text" class="form-control" id="middleName" name="middleName">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="lastName" class="form-label">Last Name*</label>
                                            <input type="text" class="form-control" id="lastName" name="lastName" required>
                                            <div class="invalid-feedback">Please enter your last name</div>
                                        </div>
                                    </div>

                                    <!-- Birthday and Sex -->
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label for="birthday" class="form-label">Birthday*</label>
                                            <input type="date" class="form-control" id="birthday" name="birthday" required>
                                            <div class="invalid-feedback">Please select your birth date</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="sex" class="form-label">Sex*</label>
                                            <select class="form-select" id="sex" name="sex" required>
                                                <option value="">Select Sex</option>
                                                <option value="male">Male</option>
                                                <option value="female">Female</option>
                                            </select>
                                            <div class="invalid-feedback">Please select your sex</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="status" class="form-label">Status*</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="">Select Status</option>
                                                <option value="single">Single</option>
                                                <option value="married">Married</option>
                                                <option value="widowed">Widowed</option>
                                                <option value="divorced">Divorced</option>
                                            </select>
                                            <div class="invalid-feedback">Please select your status</div>
                                        </div>
                                    </div>

                                    <!-- Contact Information -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email Address*</label>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                            <div class="invalid-feedback">Please enter a valid email address</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="phone" class="form-label">Phone Number*</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="e.g., 09123456789" required>
                                            <div class="invalid-feedback">Please enter a valid phone number</div>
                                        </div>
                                    </div>

                                    <!-- Optional Fields -->
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Home Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="officeAddress" class="form-label">Office Address</label>
                                        <textarea class="form-control" id="officeAddress" name="officeAddress" rows="2"></textarea>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="occupation" class="form-label">Occupation</label>
                                            <input type="text" class="form-control" id="occupation" name="occupation">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="facebookLink" class="form-label">Facebook Link</label>
                                            <input type="url" class="form-control" id="facebookLink" name="facebookLink">
                                        </div>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="agreeTerms" name="agreeTerms" required>
                                        <label class="form-check-label" for="agreeTerms">
                                            I agree to the terms and conditions*
                                        </label>
                                        <div class="invalid-feedback">You must agree to the terms and conditions</div>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn btn-primary px-5">Sign Up as Patient</button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Doctor Registration Form -->
                            <div class="tab-pane fade" id="doctor-form" role="tabpanel" aria-labelledby="doctor-tab">
                                <form method="POST" action="../Controller/doctorSignUpController.php" id="doctorSignupForm" novalidate>
                                    <input type="hidden" name="account_type" value="doctor">
                                    
                                    <!-- Account Information -->
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label for="doctor_username" class="form-label">Username*</label>
                                            <input type="text" class="form-control" id="doctor_username" name="username" required>
                                            <div class="invalid-feedback">Please choose a username (min 5 characters)</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="doctor_password" class="form-label">Password*</label>
                                            <input type="password" class="form-control" id="doctor_password" name="password" required>
                                            <div class="invalid-feedback">Password must be at least 8 characters</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="doctor_confirmPassword" class="form-label">Confirm Password*</label>
                                            <input type="password" class="form-control" id="doctor_confirmPassword" name="confirmPassword" required>
                                            <div class="invalid-feedback">Passwords do not match</div>
                                        </div>
                                    </div>

                                    <!-- Name Fields -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="doctor_firstName" class="form-label">First Name*</label>
                                            <input type="text" class="form-control" id="doctor_firstName" name="firstName" required>
                                            <div class="invalid-feedback">Please enter your first name</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="doctor_lastName" class="form-label">Last Name*</label>
                                            <input type="text" class="form-control" id="doctor_lastName" name="lastName" required>
                                            <div class="invalid-feedback">Please enter your last name</div>
                                        </div>
                                    </div>

                                    <!-- Doctor-specific fields -->
                                    <div class="mb-3">
                                        <label for="specialty" class="form-label">Specialty*</label>
                                        <input type="text" class="form-control" id="specialty" name="specialty" required>
                                        <div class="invalid-feedback">Please enter your medical specialty</div>
                                    </div>

                                    <!-- Contact Information -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="doctor_email" class="form-label">Email Address*</label>
                                            <input type="email" class="form-control" id="doctor_email" name="email" required>
                                            <div class="invalid-feedback">Please enter a valid email address</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="doctor_phone" class="form-label">Phone Number*</label>
                                            <input type="tel" class="form-control" id="doctor_phone" name="phone" placeholder="e.g., 09123456789" required>
                                            <div class="invalid-feedback">Please enter a valid phone number</div>
                                        </div>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="doctor_agreeTerms" name="agreeTerms" required>
                                        <label class="form-check-label" for="doctor_agreeTerms">
                                            I agree to the terms and conditions*
                                        </label>
                                        <div class="invalid-feedback">You must agree to the terms and conditions</div>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn btn-primary px-5">Sign Up as Doctor</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="mt-3 text-center">
                            Already have an account? <a href="login.php">Login here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
  
    <?php include 'js/scripts.html'; ?>

    <script>
    // Wait for the DOM to load
    document.addEventListener('DOMContentLoaded', function() {
        // Get the form elements
        const patientForm = document.getElementById('signupForm');
        const doctorForm = document.getElementById('doctorSignupForm');
        
        <?php if(isset($_GET['success']) && $_GET['success'] == 1): ?>
            Swal.fire({
                title: 'Registration Successful!',
                <?php if(isset($_GET['pending']) && $_GET['pending'] == 1): ?>
                text: 'Your doctor account has been created and is pending approval. You will receive an email when your account is approved.',
                icon: 'info',
                <?php else: ?>
                text: '<?php echo isset($_SESSION["patient_name"]) ? $_SESSION["patient_name"] . ", your account has been created successfully." : "Your account has been created successfully."; ?>',
                icon: 'success',
                <?php endif; ?>
                confirmButtonText: 'Continue to Login',
                confirmButtonColor: '#3085d6',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php';
                }
            });
            <?php endif; ?>
        
        // Patient form validation
        const setupFormValidation = (formId, passwordId, confirmPasswordId, usernameId, phoneId) => {
            const form = document.getElementById(formId);
            const password = document.getElementById(passwordId);
            const confirmPassword = document.getElementById(confirmPasswordId);
            const username = document.getElementById(usernameId);
            const phone = document.getElementById(phoneId);
            
            // Password match validation
            confirmPassword.addEventListener('input', function() {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            });
            
            // Username length validation
            username.addEventListener('input', function() {
                if (username.value.length < 5) {
                    username.setCustomValidity('Username must be at least 5 characters');
                } else {
                    username.setCustomValidity('');
                }
            });
            
            // Password length validation
            password.addEventListener('input', function() {
                if (password.value.length < 8) {
                    password.setCustomValidity('Password must be at least 8 characters');
                } else {
                    password.setCustomValidity('');
                    // Check confirm password match again
                    if (confirmPassword.value) {
                        if (password.value !== confirmPassword.value) {
                            confirmPassword.setCustomValidity('Passwords do not match');
                        } else {
                            confirmPassword.setCustomValidity('');
                        }
                    }
                }
            });
            
            // Phone number validation
            phone.addEventListener('input', function() {
                const phoneRegex = /^(09|\+639)\d{9}$/;
                if (!phoneRegex.test(phone.value)) {
                    phone.setCustomValidity('Please enter a valid Philippine phone number (09XXXXXXXXX or +639XXXXXXXXX)');
                } else {
                    phone.setCustomValidity('');
                }
            });
            
            // Form submission validation
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            });
        };
        
        // Set up validation for both forms
        setupFormValidation('signupForm', 'password', 'confirmPassword', 'username', 'phone');
        setupFormValidation('doctorSignupForm', 'doctor_password', 'doctor_confirmPassword', 'doctor_username', 'doctor_phone');
    });
    </script>
</body>
</html>