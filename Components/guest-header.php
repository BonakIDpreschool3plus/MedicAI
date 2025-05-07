<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<header class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top" style="background-color: rgb(255, 255, 255);">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo 'index.php'; ?>">
            <img src="../Assets/images/logo-label.png" alt="logo2" width="100" height="30" class="d-inline-block align-text-top">
        </a>
        
        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Nav Items -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0"></ul>
            <div class="d-flex">
                <a href="login.php" class="btn btn-outline-primary me-2">Login</a>
                <a href="signUp.php" class="btn btn-primary">Sign Up</a>
            </div>
        </div>
    </div>
</header>

<style>
    .nav-link.active {
        font-weight: 500;
        color: #007bff !important;
    }
    .navbar {
        padding: 10px 20px;
    }
    .btn-outline-primary {
        border-color: #007bff;
        color: #007bff;
        transition: background-color 0.3s, color 0.3s;
    }
    .btn-outline-primary:hover {
        background-color: #007bff;
        color: white;
    }
    .btn-primary {
        transition: background-color 0.3s;
    }
    .btn-primary:hover {
        background-color: #0056b3;
    }
</style>