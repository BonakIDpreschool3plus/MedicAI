<?php
// Include database connection
require_once '../../Model/config.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['is_logged_in']) || !isset($_SESSION['is_doctor']) || $_SESSION['is_doctor'] !== true) {
    // Redirect to login page if not logged in
    header("Location: ../doctorLogin.php");
    exit();
}

// Get doctor ID from session
$doctorId = $_SESSION['doctor_id'];
// Get doctor information from session
$doctorName = $_SESSION['doctor_name'];

// Fetch all appointments assigned to this doctor
try {
    $stmt = $conn->prepare("
        SELECT a.*, pc.firstName, pc.lastName, pc.email, pc.phone_number 
        FROM appointments a
        JOIN patient_creds pc ON a.user_id = pc.id
        WHERE a.doctor_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt->bind_param("i", $doctorId);
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
    <?php include '../css/links.html'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>My Appointments - Medic AI</title>
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
        
        .status-filter {
            cursor: pointer;
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 0.875rem;
        }
        
        .status-filter.active {
            background-color: #0d6efd;
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../../Components/doctor-header.php'; ?>
    
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="h3 mb-2">My Assigned Appointments</h1>
                <p class="text-muted">View and manage appointments assigned to you</p>
            </div>
        </div>
        
        <?php if(isset($_SESSION['appointment_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php 
        unset($_SESSION['appointment_success']);
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
        
        <!-- Status Filter Pills -->
        <div class="mb-4">
            <div class="d-flex flex-wrap gap-2">
                <span class="status-filter active" data-status="all">All</span>
                <span class="status-filter" data-status="pending">Pending</span>
                <span class="status-filter" data-status="confirmed">Confirmed</span>
                <span class="status-filter" data-status="completed">Completed</span>
                <span class="status-filter" data-status="cancelled">Cancelled</span>
            </div>
        </div>
        
        <!-- Appointments Table -->
        <div class="card shadow">
            <div class="card-body">
                <?php if(empty($appointments)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">No Appointments Found</h4>
                        <p class="text-muted">You don't have any appointments assigned to you yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="appointmentsTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Contact</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Medical Records</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($appointments as $appointment): ?>
                                <tr>
                                    <td>#<?php echo $appointment['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($appointment['firstName'] . ' ' . $appointment['lastName']); ?>
                                    </td>
                                    <td>
                                        <div>
                                            <a href="mailto:<?php echo $appointment['email']; ?>" class="text-decoration-none">
                                                <i class="bi bi-envelope-fill me-1 text-muted small"></i><?php echo $appointment['email']; ?>
                                            </a>
                                        </div>
                                        <div>
                                            <a href="tel:<?php echo $appointment['phone_number']; ?>" class="text-decoration-none">
                                                <i class="bi bi-telephone-fill me-1 text-muted small"></i><?php echo $appointment['phone_number']; ?>
                                            </a>
                                        </div>
                                    </td>
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
                                        <?php if($appointment['status'] === 'confirmed'): ?>
                                            <button 
                                                class="btn btn-sm btn-success btn-action complete-btn" 
                                                data-id="<?php echo $appointment['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($appointment['firstName'] . ' ' . $appointment['lastName']); ?>"
                                            >
                                                <i class="bi bi-check-all me-1"></i> Mark as Completed
                                            </button>
                                            
                                            <button 
                                                class="btn btn-sm btn-danger btn-action cancel-btn" 
                                                data-id="<?php echo $appointment['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($appointment['firstName'] . ' ' . $appointment['lastName']); ?>"
                                                data-date="<?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>"
                                                data-time="<?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>"
                                            >
                                                <i class="bi bi-x-circle me-1"></i> Cancel
                                            </button>
                                        <?php elseif($appointment['status'] === 'completed'): ?>
                                            <a href="addPatientRecord.php?patient_id=<?php echo $appointment['user_id']; ?>&appointment_id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-primary btn-action">
                                                <i class="bi bi-journal-medical me-1"></i> Add Medical Record
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="viewPatient.php?id=<?php echo $appointment['user_id']; ?>" class="btn btn-sm btn-outline-info btn-action">
                                            <i class="bi bi-person-badge me-1"></i> View Patient
                                        </a>
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
    
    <!-- Hidden forms for appointment actions -->
    <form id="completeForm" action="../../Controller/doctorAppointmentController.php" method="post" style="display:none;">
        <input type="hidden" name="action" value="complete">
        <input type="hidden" name="id" id="complete_appointment_id">
    </form>
    
    <form id="cancelForm" action="../../Controller/doctorAppointmentController.php" method="post" style="display:none;">
        <input type="hidden" name="action" value="cancel">
        <input type="hidden" name="id" id="cancel_appointment_id">
    </form>
    
    <!-- DataTables and Bootstrap JS -->
    <?php include '../js/scripts.html'; ?>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            const table = $('#appointmentsTable').DataTable({
                order: [[3, 'desc'], [4, 'desc']], // Sort by date and time (descending)
                language: {
                    search: "Search appointments:",
                    lengthMenu: "Show _MENU_ appointments per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ appointments",
                    emptyTable: "No appointments found",
                    zeroRecords: "No matching appointments found"
                },
                columnDefs: [
                    { targets: [6, 7], orderable: false } // Disable sorting for medical records and actions columns
                ]
            });
            
            // Filter by status functionality
            $('.status-filter').click(function() {
                $('.status-filter').removeClass('active');
                $(this).addClass('active');
                
                const status = $(this).data('status');
                
                if (status === 'all') {
                    table.column(5).search('').draw();
                } else {
                    table.column(5).search(status, true, false).draw();
                }
            });
            
            // Complete appointment functionality
            $('.complete-btn').click(function() {
                const appointmentId = $(this).data('id');
                const patientName = $(this).data('name');
                
                Swal.fire({
                    title: 'Mark as Completed?',
                    html: `Are you sure you want to mark the appointment with <strong>${patientName}</strong> as completed?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Complete It',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Set the appointment ID in the hidden form and submit
                        $('#complete_appointment_id').val(appointmentId);
                        $('#completeForm').submit();
                    }
                });
            });
            
            // Cancel appointment functionality
            $('.cancel-btn').click(function() {
                const appointmentId = $(this).data('id');
                const patientName = $(this).data('name');
                const appointmentDate = $(this).data('date');
                const appointmentTime = $(this).data('time');
                
                Swal.fire({
                    title: 'Cancel Appointment?',
                    html: `Are you sure you want to cancel the appointment for <strong>${patientName}</strong> on <strong>${appointmentDate}</strong> at <strong>${appointmentTime}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Cancel It',
                    cancelButtonText: 'No, Keep It'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Set the appointment ID in the hidden form and submit
                        $('#cancel_appointment_id').val(appointmentId);
                        $('#cancelForm').submit();
                    }
                });
            });
            
            // Handle success and error messages with SweetAlert
            <?php if(isset($_SESSION['appointment_success'])): ?>
            Swal.fire({
                title: 'Success',
                text: "<?php echo $_SESSION['success_message']; ?>",
                icon: 'success',
                confirmButtonColor: '#3085d6'
            });
            <?php 
            unset($_SESSION['appointment_success']);
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