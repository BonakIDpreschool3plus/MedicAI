<?php
// filepath: c:\xampp\htdocs\clinicManagement\Components\client-header.php

$isLoggedIn = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
$userName = $isLoggedIn ? $_SESSION['full_name'] : '';

// Get current page to highlight active nav item
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<header class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top" style="background-color: rgb(255, 255, 255);">
    <div class="container">
        <!-- Brand -->
            <img src="../../Assets/images/logo-label.png" alt="logo2" width="100" height="30" class="d-inline-block align-text-top">
        
        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Nav Items -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <!-- Other nav items can go here -->
            </ul>
            
            <!-- Right-aligned items -->
            <div class="d-flex align-items-center ms-auto">
                <a href="patientDashboard.php" class="btn btn-primary">
                    <i class="bi bi-person-plus me-1"></i> Patient Dashboard
                </a>
            </div>
        </div>
    </div>
</header>

<style>
    .nav-link.active {
        font-weight: 500;
        color: #007bff !important;
    }
</style>