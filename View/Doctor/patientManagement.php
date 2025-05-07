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
$username = $_SESSION['doctor_username'];
$doctorName = $_SESSION['doctor_name'];
$specialty = $_SESSION['doctor_specialty'];
// Fetch all patients
$query = "SELECT id, firstName, lastName, birthday, address, phone_number, status, occupation, office_address FROM patient_creds ORDER BY lastName, firstName";
$result = $conn->query($query);
$patients = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Calculate age from birthday
        $birthDate = new DateTime($row['birthday']);
        $today = new DateTime();
        $age = $birthDate->diff($today)->y;
        
        $row['age'] = $age; // Add age to the patient data
        $patients[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../css/links.html'; ?>
    <title>Patient Management - Clinic System</title>
    <style>
        .action-buttons .btn {
            margin-right: 5px;
        }
        .table-container {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <?php include '../../Components/doctor-header.php'; ?>
    
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="h3 mb-2">Patient Management</h1>
                <p class="text-muted">View and manage all patient information</p>
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
                <div class="table-container">
                    <table id="patientsTable" class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Address</th>
                                <th>Telephone No.</th>
                                <th>Status</th>
                                <th>Occupation</th>
                                <th>Office Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($patients)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No patients found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($patients as $patient): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($patient['firstName'] . ' ' . $patient['lastName']); ?></td>
                                    <td><?php echo $patient['age']; ?></td>
                                    <td><?php echo $patient['address'] ? htmlspecialchars($patient['address']) : 'N/a'; ?></td>
                                    <td><?php echo htmlspecialchars($patient['phone_number']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($patient['status'])); ?></td>
                                    <td><?php echo $patient['occupation'] ? htmlspecialchars($patient['occupation']) : 'N/a'; ?></td>
                                    <td><?php echo $patient['office_address'] ? htmlspecialchars($patient['office_address']) : 'N/a'; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="viewPatient.php?id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye-fill"></i> View
                                            </a>
                                        
                                            <a href="addPatientRecord.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-success">
                                                <i class="bi bi-plus-circle"></i> Add Record
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../js/scripts.html'; ?>
    
    <script>
        $(document).ready(function() {
            $('#patientsTable').DataTable({
                responsive: true,
                order: [[0, 'asc']], // Sort by name
                language: {
                    search: "Search patients:",
                    lengthMenu: "Show _MENU_ patients per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ patients",
                    emptyTable: "No patients found",
                    zeroRecords: "No matching patients found"
                },
                columnDefs: [
                    { orderable: false, targets: 7 } // Disable sorting on the Actions column
                ]
            });
        });
    </script>
</body>
</html>