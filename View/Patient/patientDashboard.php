<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../Model/config.php';

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

$userId   = $_SESSION['user_id'];
$username = $_SESSION['username'];
$fullName = $_SESSION['full_name'];

$firstLogin = false;
if (isset($_SESSION['first_login']) && $_SESSION['first_login'] === true) {
    $firstLogin = true;
    $_SESSION['first_login'] = false;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../css/links.html'; ?>
    <title><?php echo htmlspecialchars($fullName); ?> - Clinic Management</title>
    <style>
        .page-header {
            background-color: #f8f9fa;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .profile-image {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            object-fit: cover;
            border: 2px solid black;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .profile-image:hover {
            transform: scale(1.05);
        }
        .dropdown-menu {
            margin-top: 10px;
            min-width: 250px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <?php include '../../Components/client-header.php'; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-1" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                Welcome, <?php echo htmlspecialchars($fullName); ?>!
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                </ol>
            </nav>
        </div>

        <!-- Profile Dropdown -->
        <div class="dropdown" style="position: relative;">
            <img src="<?php echo isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : '../../Assets/images/default-profile.png'; ?>" 
                 alt="Profile" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false"
                 class="profile-image">
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                <li>
                    <div class="dropdown-item text-center">
                        <img src="<?php echo isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : '../../Assets/images/default-profile.png'; ?>"
                             alt="Profile" width="80" height="80" class="rounded-circle mb-2">
                        <p class="mb-0 fw-bold" style="word-wrap: break-word; max-width: 180px;">
                            <?php echo htmlspecialchars($fullName); ?>
                        </p>
                        <p class="text-muted small mb-2" style="word-wrap: break-word; max-width: 180px;">
                            <?php echo htmlspecialchars($username); ?>
                        </p>
                    </div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="profileDashboard.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../../Controller/patientLogoutController.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</div>


    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Additional dashboard sections can go here -->
    </div>
<!-- New Section Below the Header -->
<div class="container text-center mt-4">
    <h2>Your Health, Our Top Priority</h2>
    <p class="lead">Your one-stop clinic in Luzon Ave..</p>
    <p>We provide quality and affordable outpatient medical services for walk-in and scheduled patients.</p>
    <p><strong>Open DAILY from 8am to 5pm</strong></p>
</div>

<!-- Centered Appointment Button -->
<div class="container text-center mt-4">
    <a href="bookAppointment.php" class="btn btn-primary">
        <i class="bi bi-calendar-plus me-1"></i> Book an Up Appointment
    </a>
</div>

    <?php include '../js/scripts.html'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($firstLogin): ?>
        Swal.fire({
            icon: 'success',
            title: 'Login Successful!',
            text: 'Welcome back, <?php echo htmlspecialchars($fullName); ?>!',
            timer: 3000,
            timerProgressBar: true
        });
        <?php endif; ?>
    });
    </script>
</body>
</html>
