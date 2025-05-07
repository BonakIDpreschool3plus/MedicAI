<?php
// Include database connection
require_once '../../Model/config.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['is_logged_in']) || !isset($_SESSION['is_doctor']) || $_SESSION['is_doctor'] !== true) {
    // Redirect to login page if not logged in
    header("Location: ../doctorLogin.php");
    exit();
}

// Check if patient ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No patient selected.";
    header("Location: patientManagement.php");
    exit();
}

$patientId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$patientId) {
    $_SESSION['error'] = "Invalid patient ID.";
    header("Location: patientManagement.php");
    exit();
}

// Fetch patient details
$stmt = $conn->prepare("SELECT * FROM patient_creds WHERE id = ?");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();

if (!$patient) {
    $_SESSION['error'] = "Patient not found.";
    header("Location: patientManagement.php");
    exit();
}

// Calculate patient age
$birthDate = new DateTime($patient['birthday']);
$today = new DateTime();
$age = $birthDate->diff($today)->y;

// Fetch patient records
$stmt = $conn->prepare("
    SELECT pr.*, dc.firstName as doctor_first_name, dc.lastName as doctor_last_name 
    FROM patient_records pr
    JOIN doctor_creds dc ON pr.doctor_id = dc.id
    WHERE pr.patient_id = ?
    ORDER BY pr.record_date DESC
");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$records = $stmt->get_result();

// Get doctor information from session
$doctorId = $_SESSION['doctor_id'];
$doctorName = $_SESSION['doctor_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../css/links.html'; ?>
    <title>Patient Details - Clinic System</title>
    <style>
         /* Add these styles to your existing CSS */
    .patient-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: #6c757d;
        overflow: hidden; /* This ensures the image doesn't overflow the circle */
    }
    
    .patient-avatar .profile-image {
        width: 100%;
        height: 100%;
        object-fit: cover; /* This ensures the image covers the full area */
    }
        .patient-header {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .patient-name {
            margin: 0;
            font-size: 1.5rem;
        }
        .patient-id {
            margin: 0;
            color: #6c757d;
        }
        .detail-label {
            font-weight: bold;
            margin-bottom: 0;
        }
        .detail-value {
            margin-bottom: 1rem;
        }
        .patient-details-section {
            background-color: #fff;
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 20px;
        }
        .patient-history-section {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .section-title {
            margin: 0;
            font-size: 1.25rem;
        }
    </style>
</head>
<body>
    <?php include '../../Components/doctor-header.php'; ?>
    
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="patientManagement.php">Patient Management</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Patient Details</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); endif; ?>
        
        <div class="card shadow">
            <div class="card-body">
                <!-- Patient Header with Avatar -->
               <!-- Patient Header with Avatar/Profile Image -->
            <div class="patient-header">
                <?php if (!empty($patient['profile_image'])): ?>
                    <div class="patient-avatar">
                        <img src="<?php echo htmlspecialchars($patient['profile_image']); ?>" alt="Patient Profile" class="profile-image">
                    </div>
                <?php else: ?>
                    <div class="patient-avatar">
                        <?php echo strtoupper(substr($patient['firstName'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <div>
                    <h2 class="patient-name"><?php echo htmlspecialchars($patient['firstName'] . ' ' . $patient['lastName']); ?></h2>
                    <p class="patient-id">Patient ID: <?php echo $patient['id']; ?></p>
                </div>
            </div>
                            
                <!-- Patient Details -->
                <div class="patient-details-section">
                    <h3 class="section-title mb-4">PATIENT DETAILS:</h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <p class="detail-label">Full Name:</p>
                            <p class="detail-value">
                                <?php echo htmlspecialchars($patient['firstName'] . ' ' . 
                                    ($patient['middleName'] ? $patient['middleName'] . ' ' : '') . 
                                    $patient['lastName']); ?>
                            </p>
                            
                            <p class="detail-label">Age:</p>
                            <p class="detail-value"><?php echo $age; ?></p>
                            
                            <p class="detail-label">Address:</p>
                            <p class="detail-value">
                                <?php echo $patient['address'] ? htmlspecialchars($patient['address']) : 'Not specified'; ?>
                            </p>
                            
                            <p class="detail-label">Telephone Number:</p>
                            <p class="detail-value"><?php echo htmlspecialchars($patient['phone_number']); ?></p>
                        </div>
                        
                        <div class="col-md-6">
                            <p class="detail-label">Status:</p>
                            <p class="detail-value"><?php echo ucfirst(htmlspecialchars($patient['status'])); ?></p>
                            
                            <p class="detail-label">Occupation:</p>
                            <p class="detail-value">
                                <?php echo $patient['occupation'] ? htmlspecialchars($patient['occupation']) : 'Not specified'; ?>
                            </p>
                            
                            <p class="detail-label">Office Address:</p>
                            <p class="detail-value">
                                <?php echo $patient['office_address'] ? htmlspecialchars($patient['office_address']) : 'Not specified'; ?>
                            </p>
                            
                            <p class="detail-label">Email:</p>
                            <p class="detail-value"><?php echo htmlspecialchars($patient['email']); ?></p>
                        </div>
                    </div>
                </div>
                
             <!-- Patient History Section -->
                <div class="patient-history-section">
                    <div class="section-header">
                        <h3 class="section-title">PATIENT HISTORY</h3>
                        <a href="addPatientRecord.php?patient_id=<?php echo $patientId; ?>" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> ADD RECORD
                        </a>
                    </div>
                    
                    <?php if ($records->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table id="recordsTable" class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>History and Physical Examination</th>
                                        <th>Physician's Direction</th>
                                        <th>Actions</th> <!-- Added Actions column -->
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
                                            <td> <!-- Added actions cell -->
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="editPatientRecord.php?id=<?php echo $record['id']; ?>" class="btn btn-outline-secondary">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </a>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="confirmDelete(<?php echo $record['id']; ?>, <?php echo $patientId; ?>)">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-journal-x" style="font-size: 3rem; color: #6c757d;"></i>
                            <h4 class="mt-3">No Medical Records</h4>
                            <p class="text-muted">This patient doesn't have any medical records yet.</p>
                            <a href="addPatientRecord.php?patient_id=<?php echo $patientId; ?>" class="btn btn-primary mt-2">
                                Create First Record
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../js/scripts.html'; ?>
    
    <script>
      

        $(document).ready(function() {
        $('#recordsTable').DataTable({
            responsive: true,
            order: [[0, 'desc']], // Sort by date (newest first)
            pageLength: 5, // Show 5 records per page
            lengthMenu: [[5, 10, 25, -1], [5, 10, 25, "All"]]
        });
    });
    
    // Function to confirm record deletion
    function confirmDelete(recordId, patientId) {
        Swal.fire({
            title: 'Delete Medical Record?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `../../Controller/patientRecordsController.php?action=delete&id=${recordId}&patient_id=${patientId}`;
            }
        });
    }
    </script>
</body>
</html>