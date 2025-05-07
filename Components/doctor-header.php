<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #57DE7B">
    <div class="container">
        <a class="navbar-brand" href="../Doctor/doctorDashboard.php">
            <img src="../../Assets/images/logo-label.png" alt="logo2" width="100" height="30" class="d-inline-block align-text-top">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'doctorDashboard.php' ? 'active' : ''; ?>" href="../Doctor/doctorDashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'appointmentManagement.php' ? 'active' : ''; ?>" href="../Doctor/appointmentManagement.php">Appointments</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'myDoctorAppointment.php' ? 'active' : ''; ?>" href="../Doctor/myDoctorAppointment.php">My Appointments</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'patientManagement.php' ? 'active' : ''; ?>" href="../Doctor/patientManagement.php">Patients</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'myTime.php' ? 'active' : ''; ?>" href="../Doctor/myTime.php">My Time</a>
                </li>
                <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manageDoctors.php' ? 'active' : ''; ?>" href="../Doctor/manageDoctors.php">Manage Doctors</a>
                </li>
                <?php endif; ?>
            </ul>
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i> Dr. <?php echo htmlspecialchars($doctorName); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="../../Controller/doctorLogoutController.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>