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

// Check if patient ID is provided
if (!isset($_GET['patient_id'])) {
    $_SESSION['error'] = "No patient selected.";
    header("Location: patientManagement.php");
    exit();
}
// Get doctor information from session
$doctorId = $_SESSION['doctor_id'];
$doctorName = $_SESSION['doctor_name'];
$patientId = filter_input(INPUT_GET, 'patient_id', FILTER_VALIDATE_INT);
if (!$patientId) {
    $_SESSION['error'] = "Invalid patient ID.";
    header("Location: patientManagement.php");
    exit();
}

// Get appointment ID if provided (optional)
$appointmentId = filter_input(INPUT_GET, 'appointment_id', FILTER_VALIDATE_INT);

// Fetch patient information
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

// If appointment ID was provided, fetch appointment details
$appointment = null;
if ($appointmentId) {
    $stmt = $conn->prepare("SELECT * FROM appointments WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $appointmentId, $patientId);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../css/links.html'; ?>
    <title>Add Medical Record - Clinic System</title>
</head>
<body>
    <?php include '../../Components/doctor-header.php'; ?>
    
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="patientManagement.php">Patient Management</a></li>
                        <li class="breadcrumb-item"><a href="viewPatient.php?id=<?php echo $patientId; ?>">Patient Profile</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Add Medical Record</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-2">Add Medical Record</h1>
                <p class="text-muted">Patient: <?php echo htmlspecialchars($patient['firstName'] . ' ' . $patient['lastName']); ?></p>
            </div>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); endif; ?>
        
        <div class="card shadow">
            <div class="card-body">
                <?php if ($appointment): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Adding record for appointment on <strong><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></strong> at <strong><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></strong>
                </div>
                <?php endif; ?>
                
                <form action="../../Controller/patientRecordsController.php" method="post">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="patient_id" value="<?php echo $patientId; ?>">
                    <input type="hidden" name="doctor_id" value="<?php echo $doctorId; ?>">
                    
                    <?php if ($appointment): ?>
                    <input type="hidden" name="appointment_id" value="<?php echo $appointmentId; ?>">
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="record_date" class="form-label">Record Date</label>
                            <input type="date" class="form-control" id="record_date" name="record_date" 
                                value="<?php echo $appointment ? $appointment['appointment_date'] : date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="examination" class="form-label">Examination/Diagnosis</label>
                        <textarea class="form-control" id="examination" name="examination" rows="5" required></textarea>
                        <div class="form-text">Enter detailed information about the patient's condition, symptoms, and diagnosis.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="direction" class="form-label">Treatment Directions</label>
                        <textarea class="form-control" id="direction" name="direction" rows="5" required></textarea>
                        <div class="form-text">Enter treatment plan, medication, follow-up instructions, and other recommendations.</div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="viewPatient.php?id=<?php echo $patientId; ?>" class="btn btn-outline-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Medical Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../js/scripts.html'; ?>
</body>
</html>