<?php
// Include database connection
require_once '../../Model/config.php';

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    // Redirect to login page if not logged in
    header("Location: ../login.php");
    exit();
}

// Get user information from session with fallbacks
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$fullName = $_SESSION['full_name'];
$profileImage = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : '../../assets/images/default-profile.png';
$firstName = $_SESSION['firstName'] ?? '';
$middleName = $_SESSION['middleName'] ?? '';
$lastName = $_SESSION['lastName'] ?? '';

// Get the latest data from database
try {
    $stmt = $conn->prepare("SELECT * FROM patient_creds WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $userData = $result->fetch_assoc();
        $email = $userData['email'];
        $firstName = $userData['firstName'];
        $middleName = $userData['middleName'] ?? '';
        $lastName = $userData['lastName'];
        $facebookLink = $userData['facebook_link'] ?? '';
        $phone = $userData['phone_number'] ?? '';
        $address = $userData['address'] ?? '';
        $officeAddress = $userData['office_address'] ?? '';
        $occupation = $userData['occupation'] ?? '';
        $status = $userData['status'] ?? '';
        $birthday = $userData['birthday'] ?? '';
        
        // Update profile image if it exists
        if(!empty($userData['profile_image'])) {
            $profileImage = $userData['profile_image'];
            $_SESSION['profile_image'] = $profileImage;
        }
        
        // Update session with these values for future use
        $_SESSION['email'] = $email;
        $_SESSION['firstName'] = $firstName;
        $_SESSION['middleName'] = $middleName;
        $_SESSION['lastName'] = $lastName;
        $_SESSION['full_name'] = $firstName . ' ' . $lastName;
        $_SESSION['facebookLink'] = $facebookLink;
        $_SESSION['phoneNumber'] = $phone;
    } else {
        // Fallbacks if database query fails
        $email = $_SESSION['email'] ?? 'Not available';
        $facebookLink = $_SESSION['facebookLink'] ?? '';
        $phone = $_SESSION['phoneNumber'] ?? 'Not available';
        $address = 'Not available';
        $officeAddress = 'Not available';
        $occupation = 'Not available';
        $status = 'Not available';
        $birthday = 'Not available';
    }
} catch (Exception $e) {
    // Error fallbacks
    $email = $_SESSION['email'] ?? 'Not available';
    $facebookLink = $_SESSION['facebookLink'] ?? '';
    $phone = $_SESSION['phoneNumber'] ?? 'Not available';
    $address = 'Not available';
    $officeAddress = 'Not available';
    $occupation = 'Not available';
    $status = 'Not available';
    $birthday = 'Not available';
}

// Handle profile image upload
$uploadError = '';
$uploadSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $targetDir = "../../assets/images/profiles/";
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = basename($_FILES["profile_image"]["name"]);
    $targetFilePath = $targetDir . $userId . '_' . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
    // Allow certain file formats
    $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
    if (in_array(strtolower($fileType), $allowTypes)) {
        // Upload file to server
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFilePath)) {
            // Update database
            $relativePath = '../../assets/images/profiles/' . $userId . '_' . $fileName;
            $stmt = $conn->prepare("UPDATE patient_creds SET profile_image = ? WHERE id = ?");
            $stmt->bind_param("si", $relativePath, $userId);
            
            if ($stmt->execute()) {
                // Update session
                $_SESSION['profile_image'] = $relativePath;
                $profileImage = $relativePath;
                $uploadSuccess = "Your profile image has been updated successfully.";
            } else {
                $uploadError = "Database update failed.";
            }
        } else {
            $uploadError = "Sorry, there was an error uploading your file.";
        }
    } else {
        $uploadError = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
    }
}

// Handle profile information update
$updateError = '';
$updateSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $newFirstName = filter_input(INPUT_POST, 'firstName', FILTER_SANITIZE_SPECIAL_CHARS);
    $newMiddleName = filter_input(INPUT_POST, 'middleName', FILTER_SANITIZE_SPECIAL_CHARS);
    $newLastName = filter_input(INPUT_POST, 'lastName', FILTER_SANITIZE_SPECIAL_CHARS);
    $newEmail = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $newFacebookLink = filter_input(INPUT_POST, 'facebookLink', FILTER_SANITIZE_URL);
    $newPhone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
    $newAddress = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_SPECIAL_CHARS);
    $newOfficeAddress = filter_input(INPUT_POST, 'officeAddress', FILTER_SANITIZE_SPECIAL_CHARS);
    $newOccupation = filter_input(INPUT_POST, 'occupation', FILTER_SANITIZE_SPECIAL_CHARS);
    $newStatus = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
    $newBirthday = filter_input(INPUT_POST, 'birthday', FILTER_SANITIZE_SPECIAL_CHARS);
    
    // Validate email
    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $updateError = "Please provide a valid email address.";
    } else {
        try {
            // Update the database
            $stmt = $conn->prepare("
                UPDATE patient_creds 
                SET firstName = ?, 
                    middleName = ?, 
                    lastName = ?, 
                    email = ?, 
                    facebook_link = ?, 
                    phone_number = ?, 
                    address = ?, 
                    office_address = ?,
                    occupation = ?,
                    status = ?,
                    birthday = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                "sssssssssssi", 
                $newFirstName, 
                $newMiddleName, 
                $newLastName, 
                $newEmail, 
                $newFacebookLink, 
                $newPhone, 
                $newAddress, 
                $newOfficeAddress,
                $newOccupation,
                $newStatus,
                $newBirthday,
                $userId
            );
            
            if ($stmt->execute()) {
                // Update session variables
                $_SESSION['firstName'] = $newFirstName;
                $_SESSION['middleName'] = $newMiddleName;
                $_SESSION['lastName'] = $newLastName;
                $_SESSION['full_name'] = $newFirstName . ' ' . $newLastName;
                $_SESSION['email'] = $newEmail;
                $_SESSION['facebookLink'] = $newFacebookLink;
                $_SESSION['phoneNumber'] = $newPhone;
                
                // Update local variables
                $firstName = $newFirstName;
                $middleName = $newMiddleName;
                $lastName = $newLastName;
                $email = $newEmail;
                $facebookLink = $newFacebookLink;
                $phone = $newPhone;
                $address = $newAddress;
                $officeAddress = $newOfficeAddress;
                $occupation = $newOccupation;
                $status = $newStatus;
                $birthday = $newBirthday;
                
                $updateSuccess = "Your profile has been updated successfully.";
            } else {
                $updateError = "Failed to update profile. Please try again.";
            }
        } catch (Exception $e) {
            $updateError = "An error occurred: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../css/links.html'; ?>
    <title>Account Settings - Clinic Management</title>
    <style>
        .main-content {
            padding: 30px 0;
            min-height: 80vh;
        }
        .page-header {
            margin-bottom: 30px;
        }
        .profile-card {
            margin-bottom: 25px;
            border-radius: 10px;
        }
        .profile-header {
            border-bottom: 1px solid #eaeaea;
            padding-bottom: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .profile-header h4 {
            margin-bottom: 0;
            font-weight: 600;
        }
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #fff;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            position: relative;
        }
        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-image .edit-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.6);
            padding: 5px;
            color: white;
            text-align: center;
            cursor: pointer;
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .profile-image:hover .edit-overlay {
            opacity: 1;
        }
        .profile-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .profile-id {
            color: #6c757d;
        }
        .info-row {
            margin-bottom: 20px;
        }
        .info-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .info-value {
            font-weight: 500;
        }
        .sidebar {
            position: sticky;
            top: 30px;
        }
        .sidebar .btn {
            width: 100%;
            text-align: left;
            margin-bottom: 10px;
            padding: 12px 15px;
            border-radius: 8px;
            font-weight: 500;
        }
        .qr-section {
            text-align: center;
        }
        .qr-code {
            max-width: 100%;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .qr-validity {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .edit-icon {
            cursor: pointer;
            color: #0d6efd;
        }
        .edit-form-container {
            display: none;
        }
        .form-group {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include '../../Components/client-header.php'; ?>

    <!-- Main Content -->
    <div class="main-content p-2">
        <div class="container">
            <!-- Page Header & Breadcrumbs -->
            <div class="page-header d-flex justify-content-between align-items-center">
                <h1 class="page-title">Account Settings</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="patientAI.php">Medic AI</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Account Settings</li>
                    </ol>
                </nav>
            </div>

            <div class="row">
             
                
                <!-- Main Content Area -->
                <div class="col-lg-12">
                    <!-- Success/Error messages -->
                    <?php if(!empty($updateSuccess) || !empty($uploadSuccess)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo !empty($updateSuccess) ? $updateSuccess : $uploadSuccess; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($updateError) || !empty($uploadError)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo !empty($updateError) ? $updateError : $uploadError; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Profile Card -->
                    <div class="card profile-card border p-3 shadow-sm mb-3">
                        <div class="profile-header">
                            <h4>My Profile</h4>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="toggle-edit-mode">
                                <i class="bi bi-pencil-square"></i> Edit Profile
                            </button>
                        </div>
                        <div class="profile-body">
                            <div class="row mb-4">
                                <div class="col-auto">
                                    <div class="profile-image">
                                        <img src="<?php echo $profileImage; ?>" alt="Profile Picture" id="profile-image-preview">
                                        <div class="edit-overlay" id="change-photo-trigger">
                                            <i class="bi bi-camera"></i> Change Photo
                                        </div>
                                        <form id="profile-image-form" action="" method="post" enctype="multipart/form-data" style="display: none;">
                                            <input type="file" name="profile_image" id="profile-image-upload" accept="image/*">
                                        </form>
                                    </div>
                                </div>
                                <div class="col profile-info">
                                    <div class="profile-name"><?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></div>
                                    <div class="profile-id">Patient ID: <?php echo $userId; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- View Mode -->
                    <div id="view-profile-container">
                        <div class="card profile-card p-3 shadow-sm">
                            <div class="profile-header">
                                <h4>Personal Information</h4>
                            </div>
                            <div class="profile-body">
                                <div class="row info-row">
                                    <div class="col-md-4">
                                        <div class="info-label">First Name</div>
                                        <div class="info-value"><?php echo htmlspecialchars($firstName); ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-label">Middle Name</div>
                                        <div class="info-value"><?php echo !empty($middleName) ? htmlspecialchars($middleName) : 'Not provided'; ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-label">Last Name</div>
                                        <div class="info-value"><?php echo htmlspecialchars($lastName); ?></div>
                                    </div>
                                </div>
                                <div class="row info-row">
                                    <div class="col-md-4">
                                        <div class="info-label">Email Address</div>
                                        <div class="info-value"><?php echo htmlspecialchars($email); ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-label">Facebook Link</div>
                                        <div class="info-value">
                                            <?php if (!empty($facebookLink)): ?>
                                                <a href="<?php echo htmlspecialchars($facebookLink); ?>" target="_blank"><?php echo htmlspecialchars($facebookLink); ?></a>
                                            <?php else: ?>
                                                Not provided
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-label">Phone Number</div>
                                        <div class="info-value"><?php echo !empty($phone) ? htmlspecialchars($phone) : 'Not provided'; ?></div>
                                    </div>
                                </div>
                                <div class="row info-row">
                                    <div class="col-md-4">
                                        <div class="info-label">Birthday</div>
                                        <div class="info-value"><?php echo !empty($birthday) ? date('F j, Y', strtotime($birthday)) : 'Not provided'; ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-label">Status</div>
                                        <div class="info-value"><?php echo !empty($status) ? htmlspecialchars($status) : 'Not provided'; ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-label">Occupation</div>
                                        <div class="info-value"><?php echo !empty($occupation) ? htmlspecialchars($occupation) : 'Not provided'; ?></div>
                                    </div>
                                </div>
                                <div class="row info-row">
                                    <div class="col-md-6">
                                        <div class="info-label">Home Address</div>
                                        <div class="info-value"><?php echo !empty($address) ? htmlspecialchars($address) : 'Not provided'; ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Office Address</div>
                                        <div class="info-value"><?php echo !empty($officeAddress) ? htmlspecialchars($officeAddress) : 'Not provided'; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Mode -->
                    <div id="edit-profile-container" class="edit-form-container">
                        <div class="card profile-card p-3 shadow-sm">
                            <div class="profile-header">
                                <h4>Edit Personal Information</h4>
                            </div>
                            <div class="profile-body">
                                <form action="" method="post" id="profile-edit-form">
                                    <input type="hidden" name="update_profile" value="1">
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="firstName" class="form-label">First Name</label>
                                                <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo htmlspecialchars($firstName); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="middleName" class="form-label">Middle Name</label>
                                                <input type="text" class="form-control" id="middleName" name="middleName" value="<?php echo htmlspecialchars($middleName); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="lastName" class="form-label">Last Name</label>
                                                <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo htmlspecialchars($lastName); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="email" class="form-label">Email Address</label>
                                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="facebookLink" class="form-label">Facebook Link</label>
                                                <input type="url" class="form-control" id="facebookLink" name="facebookLink" value="<?php echo htmlspecialchars($facebookLink); ?>" placeholder="https://facebook.com/yourusername">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="phone" class="form-label">Phone Number</label>
                                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="birthday" class="form-label">Birthday</label>
                                                <input type="date" class="form-control" id="birthday" name="birthday" value="<?php echo $birthday; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="status" class="form-label">Status</label>
                                                <select class="form-select" id="status" name="status">
                                                    <option value="single" <?php echo ($status == 'single') ? 'selected' : ''; ?>>Single</option>
                                                    <option value="married" <?php echo ($status == 'married') ? 'selected' : ''; ?>>Married</option>
                                                    <option value="divorced" <?php echo ($status == 'divorced') ? 'selected' : ''; ?>>Divorced</option>
                                                    <option value="widowed" <?php echo ($status == 'widowed') ? 'selected' : ''; ?>>Widowed</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="occupation" class="form-label">Occupation</label>
                                                <input type="text" class="form-control" id="occupation" name="occupation" value="<?php echo htmlspecialchars($occupation); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="address" class="form-label">Home Address</label>
                                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($address); ?></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="officeAddress" class="form-label">Office Address</label>
                                                <textarea class="form-control" id="officeAddress" name="officeAddress" rows="3"><?php echo htmlspecialchars($officeAddress); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end mt-4">
                                        <button type="button" class="btn btn-outline-secondary me-2" id="cancel-edit">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include JavaScript -->
    <?php include '../js/scripts.html'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle between view and edit mode
        const toggleEditBtn = document.getElementById('toggle-edit-mode');
        const viewContainer = document.getElementById('view-profile-container');
        const editContainer = document.getElementById('edit-profile-container');
        const cancelEditBtn = document.getElementById('cancel-edit');
        
        toggleEditBtn.addEventListener('click', function() {
            viewContainer.style.display = 'none';
            editContainer.style.display = 'block';
        });
        
        cancelEditBtn.addEventListener('click', function() {
            editContainer.style.display = 'none';
            viewContainer.style.display = 'block';
        });
        
        // Handle profile image selection and upload
        const profileImageInput = document.getElementById('profile-image-upload');
        const profileForm = document.getElementById('profile-image-form');
        const changePhotoTrigger = document.getElementById('change-photo-trigger');
        const imagePreview = document.getElementById('profile-image-preview');
        
        changePhotoTrigger.addEventListener('click', function() {
            profileImageInput.click();
        });
        
        profileImageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                // Show image preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
                
                // Show loading indicator
                Swal.fire({
                    title: 'Uploading...',
                    text: 'Please wait while we upload your new profile picture.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Submit the form
                profileForm.submit();
            }
        });
        
        // Handle form submission with validation
        const profileEditForm = document.getElementById('profile-edit-form');
        profileEditForm.addEventListener('submit', function(event) {
            const email = document.getElementById('email').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailRegex.test(email)) {
                event.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Email',
                    text: 'Please enter a valid email address.'
                });
                return;
            }
            
            // Show loading while form is submitting
            Swal.fire({
                title: 'Saving...',
                text: 'Please wait while we update your profile.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        });
        
        // Display success message
        <?php if(!empty($uploadSuccess) || !empty($updateSuccess)): ?>
        Swal.fire({
            icon: 'success',
            title: 'Profile Updated',
            text: '<?php echo !empty($updateSuccess) ? $updateSuccess : $uploadSuccess; ?>'
        });
        <?php endif; ?>
        
        // Display error message
        <?php if(!empty($uploadError) || !empty($updateError)): ?>
        Swal.fire({
            icon: 'error',
            title: 'Update Failed',
            text: '<?php echo !empty($updateError) ? $updateError : $uploadError; ?>'
        });
        <?php endif; ?>
    });
    </script>
</body>
</html>