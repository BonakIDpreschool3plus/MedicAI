<?php
// Include database connection
require_once '../../Model/config.php';

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    // Redirect to login page if not logged in
    header("Location: ../login.php");
    exit();
}

// Get user information from session
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$fullName = $_SESSION['full_name'];

// Fetch the patient's medical records
$stmt = $conn->prepare("
    SELECT pr.*, dc.firstName as doctor_first_name, dc.lastName as doctor_last_name 
    FROM patient_records pr
    JOIN doctor_creds dc ON pr.doctor_id = dc.id
    WHERE pr.patient_id = ?
    ORDER BY pr.record_date DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$records = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../css/links.html'; ?>
    <title>My Medical History - Clinic Management</title>
    <style>
        .page-header {
            background-color: #f8f9fa;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }
        
        #recordsTable td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <?php include '../../Components/client-header.php'; ?>
    
    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1 class="mb-0">My Medical History</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="patientDashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Medical History</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Patient History Section -->
        <div class="patient-history-section">
            <div class="section-header">
                <h3 class="section-title">PATIENT HISTORY</h3>
            </div>
            
            <?php if ($records->num_rows > 0): ?>
                <div class="table-responsive">
                    <table id="recordsTable" class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>History and Physical Examination</th>
                                <th>Physician's Direction</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($record = $records->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('n/j/Y', strtotime($record['record_date'])); ?></td>
                                    <td>
                                        <?php echo nl2br(htmlspecialchars($record['examination'])); ?>
                                        <div class="mt-2 text-muted small">
                                            <i class="bi bi-person-badge"></i> Dr. <?php echo htmlspecialchars($record['doctor_first_name'] . ' ' . $record['doctor_last_name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo nl2br(htmlspecialchars($record['direction'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-journal-x" style="font-size: 3rem; color: #6c757d;"></i>
                    <h4 class="mt-3">No Medical Records</h4>
                    <p class="text-muted">You don't have any medical records yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Include JavaScript -->
    <?php include '../js/scripts.html'; ?>
    <script>
        $(document).ready(function() {
            // If jQuery and DataTables are available, initialize the table
            if ($.fn.DataTable) {
                $('#recordsTable').DataTable({
                    "ordering": false,
                    "info": false,
                    "lengthChange": false
                });
            }
        });
    </script>
</body>
</html>