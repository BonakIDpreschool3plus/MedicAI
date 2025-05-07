<?php

// Include database connection
require_once '../../Model/config.php';

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Get user ID from session
$userId = $_SESSION['user_id'];


// Get all appointments for the user
try {
    $stmt = $conn->prepare("SELECT * FROM appointments WHERE user_id = ? ORDER BY appointment_date DESC, appointment_time DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
} catch (Exception $e) {
    $error = "Error fetching appointments: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - Medic AI</title>
    <?php include '../css/links.html'; ?>
    

    
    <style>
        .badge {
            font-size: 0.8rem;
            font-weight: 500;
            padding: 0.35em 0.65em;
        }
        
        .badge-pending {
            background-color: #fd7e14;
            color: white;
        }
        
        .badge-confirmed {
            background-color: #0d6efd;
            color: white;
        }
        
        .badge-completed {
            background-color: #198754;
            color: white;
        }
        
        .badge-cancelled {
            background-color: #dc3545;
            color: white;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }
        
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        
        .dataTables_wrapper .dataTables_length select {
            min-width: 60px;
        }
    </style>
</head>
<body>
    <?php include '../../Components/client-header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1 class="mb-0">My Appointments</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="patientDashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Appointments</li>
                </ol>
            </nav>
        </div>
    </div>
        
        <!-- Display success/error messages -->
        <?php if(isset($_SESSION['cancel_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message'] ?? "Your appointment has been cancelled successfully."; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php 
        unset($_SESSION['cancel_success']);
        unset($_SESSION['success_message']);
        endif; ?>
        
        <?php if(isset($_SESSION['reschedule_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message'] ?? "Your appointment has been rescheduled successfully."; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php 
        unset($_SESSION['reschedule_success']);
        unset($_SESSION['success_message']);
        endif; ?>
        
        <?php if(isset($_SESSION['appointment_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['appointment_error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php 
        unset($_SESSION['appointment_error']);
        endif; ?>
        
        <!-- Appointments Table -->
        <div class="card shadow">
            <div class="card-body">
                <?php if(empty($appointments)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">No Appointments Found</h4>
                        <p class="text-muted">You don't have any appointments scheduled yet.</p>
                        <a href="bookAppointment.php" class="btn btn-success mt-2">Book Your First Appointment</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="appointmentsTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Medical Records</th>
                                    <th>Booked On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($appointments as $appointment): ?>
                                <tr>
                                    <td>#<?php echo $appointment['id']; ?></td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                        <div class="small text-muted">
                                            <?php echo date('l', strtotime($appointment['appointment_date'])); ?>
                                        </div>
                                    </td>
                                    <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $appointment['status']; ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($appointment['medical_records']): ?>
                                            <a href="../../uploads/medical_records/<?php echo $appointment['medical_records']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-file-earmark-pdf"></i> View
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($appointment['created_at'])); ?>
                                        <div class="small text-muted">
                                            <?php echo date('h:i A', strtotime($appointment['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($appointment['status'] === 'pending'): ?>
                                            <button 
                                                class="btn btn-sm btn-danger btn-action cancel-btn" 
                                                data-id="<?php echo $appointment['id']; ?>"
                                                data-date="<?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>"
                                                data-time="<?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>"
                                            >
                                                <i class="bi bi-x-circle me-1"></i> Cancel
                                            </button>
                                            <a href="rescheduleAppointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-primary btn-action">
                                                <i class="bi bi-calendar-plus me-1"></i> Reschedule
                                            </a>
                                      
                                       
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Cancel Form (Hidden) -->
    <form id="cancelForm" action="../../Controller/appointmentController.php" method="post" style="display:none;">
        <input type="hidden" name="action" value="cancel">
        <input type="hidden" name="id" id="appointment_id">
    </form>
    
    <!-- DataTables and Bootstrap JS -->
    <?php include '../js/scripts.html'; ?>
    
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#appointmentsTable').DataTable({
                order: [[1, 'desc'], [2, 'desc']], // Sort by date and time (descending)
                language: {
                    search: "Search appointments:",
                    lengthMenu: "Show _MENU_ appointments per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ appointments",
                    emptyTable: "No appointments found",
                    zeroRecords: "No matching appointments found"
                },
                columnDefs: [
                    { targets: [4, 6], orderable: false } // Disable sorting for medical records and actions columns
                ]
            });
            
            // Cancel appointment functionality
            $('.cancel-btn').click(function() {
                const appointmentId = $(this).data('id');
                const appointmentDate = $(this).data('date');
                const appointmentTime = $(this).data('time');
                
                Swal.fire({
                    title: 'Cancel Appointment?',
                    html: `Are you sure you want to cancel your appointment on <strong>${appointmentDate}</strong> at <strong>${appointmentTime}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Cancel It',
                    cancelButtonText: 'No, Keep It'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Set the appointment ID in the hidden form and submit
                        $('#appointment_id').val(appointmentId);
                        $('#cancelForm').submit();
                    }
                });
            });
            
            // Handle success and error messages with SweetAlert
            <?php if(isset($_SESSION['cancel_success'])): ?>
            Swal.fire({
                title: 'Appointment Cancelled',
                text: "<?php echo $_SESSION['success_message'] ?? 'Your appointment has been cancelled successfully.'; ?>",
                icon: 'success',
                confirmButtonColor: '#3085d6'
            });
            <?php 
            unset($_SESSION['cancel_success']);
            unset($_SESSION['success_message']);
            endif; ?>
            
            <?php if(isset($_SESSION['reschedule_success'])): ?>
            Swal.fire({
                title: 'Appointment Rescheduled',
                text: "<?php echo $_SESSION['success_message'] ?? 'Your appointment has been rescheduled successfully.'; ?>",
                icon: 'success',
                confirmButtonColor: '#3085d6'
            });
            <?php 
            unset($_SESSION['reschedule_success']);
            unset($_SESSION['success_message']);
            endif; ?>
            
            <?php if(isset($_SESSION['appointment_error'])): ?>
            Swal.fire({
                title: 'Error',
                text: "<?php echo $_SESSION['appointment_error']; ?>",
                icon: 'error',
                confirmButtonColor: '#3085d6'
            });
            <?php 
            unset($_SESSION['appointment_error']);
            endif; ?>
        });
    </script>
</body>
</html>