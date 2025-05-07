<?php
// Include database connection
require_once '../../Model/config.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a doctor
if (!isset($_SESSION['is_logged_in']) || !isset($_SESSION['is_doctor']) || $_SESSION['is_doctor'] !== true) {
    // Redirect to login page if not logged in
    header("Location: ../login.php");
    exit();
}

// Get doctor information from session
$doctorId = $_SESSION['doctor_id'];
$username = $_SESSION['doctor_username'];
$doctorName = $_SESSION['doctor_name'];
$specialty = $_SESSION['doctor_specialty'];

// Check if this is first login after authentication
$firstLogin = false;
if (isset($_SESSION['first_login']) && $_SESSION['first_login'] === true) {
    $firstLogin = true;
    // Reset the flag
    $_SESSION['first_login'] = false;
}

// Get today's date
$today = date('Y-m-d');

// Fetch statistics for doctor dashboard
try {
    // Total patients seen by this doctor
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT patient_id) as total_patients 
        FROM patient_records 
        WHERE doctor_id = ?
    ");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $result = $stmt->get_result();
    $totalPatients = $result->fetch_assoc()['total_patients'];
    
    // Total appointments today for this doctor
    $stmt = $conn->prepare("
        SELECT COUNT(*) as today_appointments 
        FROM appointments 
        WHERE appointment_date = ? AND doctor_id = ? AND status != 'cancelled'
    ");
    $stmt->bind_param("si", $today, $doctorId);
    $stmt->execute();
    $result = $stmt->get_result();
    $todayAppointments = $result->fetch_assoc()['today_appointments'];

    $stmt = $conn->prepare("
        SELECT a.id, a.appointment_date, a.appointment_time, a.status, 
               pc.id as patient_id, pc.firstName, pc.lastName, pc.phone_number, pc.profile_image 
        FROM appointments a
        JOIN patient_creds pc ON a.user_id = pc.id
        WHERE a.appointment_date >= ? 
        AND a.doctor_id = ?
        AND a.status = 'confirmed'
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
        LIMIT 5
    ");
    $stmt->bind_param("si", $today, $doctorId);
    $stmt->execute();
    $upcomingAppointments = $stmt->get_result();
    
    // Recent patients for this doctor
    $stmt = $conn->prepare("
        SELECT DISTINCT pr.patient_id, pc.firstName, pc.lastName, pc.profile_image, 
               MAX(pr.record_date) as last_visit
        FROM patient_records pr
        JOIN patient_creds pc ON pr.patient_id = pc.id
        WHERE pr.doctor_id = ?
        GROUP BY pr.patient_id
        ORDER BY last_visit DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $recentPatients = $stmt->get_result();
    
    // Total records created by this doctor
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_records 
        FROM patient_records 
        WHERE doctor_id = ?
    ");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $result = $stmt->get_result();
    $totalRecords = $result->fetch_assoc()['total_records'];
    
    // Pending appointments
    // If admin, show all pending appointments, otherwise only show those assigned to this doctor
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as pending_appointments 
            FROM appointments 
            WHERE status = 'pending'
        ");
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as pending_appointments 
            FROM appointments 
            WHERE status = 'pending' AND doctor_id = ?
        ");
        $stmt->bind_param("i", $doctorId);
        $stmt->execute();
    }
    $result = $stmt->get_result();
    $pendingAppointments = $result->fetch_assoc()['pending_appointments'];
    
} catch (Exception $e) {
    $error = "Error fetching dashboard data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<!-- Rest of your HTML code remains the same -->
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../css/links.html'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Doctor Dashboard - Clinic Management</title>
    <style>
        .welcome-banner {
            
            color: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
        }
        
        .dashboard-card {
            border-radius: 12px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .dashboard-card .card-body {
            flex: 1;
        }
        
        .stat-card {
            border-left: 4px solid;
            border-radius: 8px;
        }
        
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .stat-primary {
            border-color: #0d6efd;
        }
        
        .stat-primary .stat-icon {
            color: #0d6efd;
        }
        
        .stat-success {
            border-color: #198754;
        }
        
        .stat-success .stat-icon {
            color: #198754;
        }
        
        .stat-warning {
            border-color: #ffc107;
        }
        
        .stat-warning .stat-icon {
            color: #ffc107;
        }
        
        .stat-danger {
            border-color: #dc3545;
        }
        
        .stat-danger .stat-icon {
            color: #dc3545;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 600;
        }
        
        .stat-title {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .appointment-item {
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .appointment-item:hover {
            background-color: #f8f9fa;
        }
        
        .appointment-time {
            border-radius: 20px;
            font-size: 0.8rem;
            padding: 4px 12px;
            font-weight: 500;
            background-color: #e9ecef;
        }
        
        .patient-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: #6c757d;
            overflow: hidden;
        }
        
        .patient-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }
        
        .section-action {
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .quick-action-btn {
            border-radius: 12px;
            text-align: center;
            padding: 1.25rem;
            transition: all 0.2s;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-5px);
        }
        
        .quick-action-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        @media (max-width: 992px) {
            .welcome-banner h1 {
                font-size: 1.75rem;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
            
            .stat-icon {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../../Components/doctor-header.php'; ?>
    
    <!-- Main Content -->
    <div class="container py-5">
        <!-- Welcome Banner -->
        <div class="welcome-banner" style="background-color: #57DE7B">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>Welcome back, Dr. <?php echo htmlspecialchars($doctorName); ?>!</h1>
                    <p class="mb-0">
                        <i class="bi bi-calendar-check"></i> Today is <?php echo date('l, F j, Y'); ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div class="btn-group">
                        <a href="patientManagement.php" class="btn btn-outline-light">
                            <i class="bi bi-people"></i> Patients
                        </a>
                        <a href="appointmentManagement.php" class="btn btn-outline-light">
                            <i class="bi bi-calendar-week"></i> Appointments
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Row -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
                <div class="card stat-card stat-primary h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon me-3">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?php echo number_format($totalPatients); ?></div>
                            <div class="stat-title">Total Patients</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
                <div class="card stat-card stat-success h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon me-3">
                            <i class="bi bi-calendar2-check"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?php echo number_format($todayAppointments); ?></div>
                            <div class="stat-title">Today's Appointments</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
                <div class="card stat-card stat-warning h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon me-3">
                            <i class="bi bi-journal-medical"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?php echo number_format($totalRecords); ?></div>
                            <div class="stat-title">Medical Records</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
                <div class="card stat-card stat-danger h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon me-3">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?php echo number_format($pendingAppointments); ?></div>
                            <div class="stat-title">Pending Requests</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- First Row: Appointments & Recent Patients -->
        <div class="row mb-4">
            <!-- Upcoming Appointments -->
            <div class="col-lg-8 mb-4 mb-lg-0">
    <div class="card dashboard-card h-100">
        <div class="card-body">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="bi bi-calendar-event me-2 text-primary"></i> 
                    My Upcoming Appointments
                </h3>
                <a href="appointmentManagement.php" class="section-action text-primary">View all</a>
            </div>
            
            <?php if ($upcomingAppointments && $upcomingAppointments->num_rows > 0): ?>
                <div class="list-group">
                    <?php while ($appointment = $upcomingAppointments->fetch_assoc()): 
                        $appointmentDate = new DateTime($appointment['appointment_date']);
                        $isToday = $appointmentDate->format('Y-m-d') === $today;
                        $dateLabel = $isToday ? 'Today' : $appointmentDate->format('D, M j');
                    ?>
                        <div class="appointment-item p-3 mb-3 border">
                            <div class="row align-items-center">
                                <div class="col-md-2 col-sm-3 mb-3 mb-sm-0">
                                    <div class="patient-avatar mx-auto">
                                        <?php if (!empty($appointment['profile_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($appointment['profile_image']); ?>" alt="Patient">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($appointment['firstName'], 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-9 mb-3 mb-md-0">
                                    <h6 class="mb-1">
                                        <a href="viewPatient.php?id=<?php echo $appointment['patient_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($appointment['firstName'] . ' ' . $appointment['lastName']); ?>
                                        </a>
                                    </h6>
                                    <div class="text-muted small">
                                        <i class="bi bi-telephone me-1"></i> 
                                        <?php echo htmlspecialchars($appointment['phone_number']); ?>
                                    </div>
                                    <!-- Added appointment reason if available -->
                                    <?php if (!empty($appointment['reason'])): ?>
                                    <div class="text-muted small mt-1">
                                        <i class="bi bi-clipboard-plus me-1"></i> 
                                        <?php echo htmlspecialchars($appointment['reason']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <span class="badge bg-<?php echo $isToday ? 'danger' : 'primary'; ?> mb-2">
                                        <?php echo $dateLabel; ?>
                                    </span>
                                    <div class="appointment-time">
                                        <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                    </div>
                                    <!-- Added action buttons -->
                                    
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">No Upcoming Appointments</h5>
                    <p class="text-muted">You don't have any appointments assigned to you yet.</p>
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                    <a href="appointmentManagement.php?status=pending" class="btn btn-primary mt-2">
                        <i class="bi bi-calendar-plus"></i> Review Pending Appointments
                    </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
            
            <!-- Recent Patients -->
            <div class="col-lg-4">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="bi bi-clock-history me-2 text-success"></i> 
                                Recent Patients
                            </h3>
                            <a href="patientManagement.php" class="section-action text-success">View all</a>
                        </div>
                        
                        <?php if ($recentPatients && $recentPatients->num_rows > 0): ?>
                            <div class="list-group">
                                <?php while ($patient = $recentPatients->fetch_assoc()): ?>
                                    <a href="viewPatient.php?id=<?php echo $patient['patient_id']; ?>" class="list-group-item list-group-item-action p-3">
                                        <div class="d-flex align-items-center">
                                            <div class="patient-avatar me-3">
                                                <?php if (!empty($patient['profile_image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($patient['profile_image']); ?>" alt="Patient">
                                                <?php else: ?>
                                                    <?php echo strtoupper(substr($patient['firstName'], 0, 1)); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($patient['firstName'] . ' ' . $patient['lastName']); ?></h6>
                                                <div class="text-muted small">
                                                    <i class="bi bi-calendar-check me-1"></i> 
                                                    Last visit: <?php echo date('M j, Y', strtotime($patient['last_visit'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">No Recent Patients</h5>
                                <p class="text-muted">You haven't seen any patients recently.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
    
    <!-- Include JavaScript -->
    <?php include '../js/scripts.html'; ?>
    
    <script>
        // Show welcome message on first login
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($firstLogin): ?>
            Swal.fire({
                icon: 'success',
                title: 'Login Successful!',
                text: 'Welcome back, Dr. <?php echo htmlspecialchars($doctorName); ?>!',
                timer: 3000,
                timerProgressBar: true
            });
            <?php endif; ?>
            
            // Sample data for visits chart - In a real app, fetch this from the database
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const currentMonth = new Date().getMonth();
            const lastSixMonths = months.slice(Math.max(0, currentMonth - 5), currentMonth + 1);
            
            // Sample patient visit counts (in a real app, get this from database)
            const visitData = [28, 45, 35, 50, 32, 48];
            
            // Patient visits chart
            const ctx = document.getElementById('visitsChart').getContext('2d');
            const visitsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: lastSixMonths,
                    datasets: [{
                        label: 'Patient Visits',
                        data: visitData,
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        borderColor: 'rgba(220, 53, 69, 0.8)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: 'rgba(220, 53, 69, 1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
        
        // Patient search function
        function searchPatients() {
            const query = document.getElementById('patientSearch').value;
            if (query.trim() === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Empty Search',
                    text: 'Please enter a search term to find patients.',
                });
                return;
            }
            
            // Redirect to patient management page with search query
            window.location.href = `patientManagement.php?search=${encodeURIComponent(query)}`;
        }
    </script>
</body>
</html>