<?php
// filepath: c:\xampp\htdocs\clinicManagement\Components\client-header.php

$isLoggedIn = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
$userName = $isLoggedIn ? $_SESSION['full_name'] : '';

// Get current page to highlight active nav item
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<header class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top" style="background-color:rgb(255, 255, 255);">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <!-- Brand -->
                <img src="../../Assets/images/logo-label.png" alt="logo2" width="100" height="30" class="d-inline-block align-text-top">
        </div>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
            aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Center Nav Items -->
        <div class="collapse navbar-collapse justify-content-center" id="navbarContent">
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link custom-link <?php echo $currentPage === 'myAppointments.php' ? 'active' : ''; ?>" href="myAppointments.php">
                        <i class="bi bi-calendar-check me-1"></i> My Appointments
                    </a>
                </li>
                <li class="nav-item ms-3">
                    <a class="nav-link custom-link <?php echo $currentPage === 'myHistory.php' ? 'active' : ''; ?>" href="myHistory.php">
                        <i class="bi bi-calendar-check me-1"></i> My History
                    </a>
                </li>
            </ul>
        </div>
        <a href="patientAI.php" class="btn btn-primary me-4">
    <i class="bi bi-chat-dots me-1"></i> MedicAI
</a>

        </div>
    </div>
</header>

<style>
    .nav-link.custom-link {
        color: #000 !important;
    }

    .nav-link.custom-link.active {
        font-weight: 500;
        color: #007bff !important;
    }
</style>
