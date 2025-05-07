<?php
// Include database connection
require_once '../../Model/config.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['is_logged_in']) || !isset($_SESSION['is_doctor']) || $_SESSION['is_doctor'] !== true) {
    // Redirect to login page if not logged in
    header("Location: ../doctorLogin.php");
    exit();
}

// Check if record ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No record selected.";
    header("Location: patientManagement.php");
    exit();
}

$recordId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$recordId) {
    $_SESSION['error'] = "Invalid record ID.";
    header("Location: patientManagement.php");
    exit();
}

// Fetch record details with patient information
$stmt = $conn->prepare("
    SELECT pr.*, pc.firstName, pc.lastName
    FROM patient_records pr
    JOIN patient_creds pc ON pr.patient_id = pc.id
    WHERE pr.id = ?
");
$stmt->bind_param("i", $recordId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Record not found.";
    header("Location: patientManagement.php");
    exit();
}
// Get doctor information from session
$doctorId = $_SESSION['doctor_id'];
$doctorName = $_SESSION['doctor_name'];
$record = $result->fetch_assoc();
$patientId = $record['patient_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../css/links.html'; ?>
    <title>Edit Medical Record - Clinic System</title>
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
                        <li class="breadcrumb-item active" aria-current="page">Edit Medical Record</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-2">Edit Medical Record</h1>
                <p class="text-muted">Patient: <?php echo htmlspecialchars($record['firstName'] . ' ' . $record['lastName']); ?></p>
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
                <form action="../../Controller/patientRecordsController.php" method="post">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="record_id" value="<?php echo $recordId; ?>">
                    <input type="hidden" name="patient_id" value="<?php echo $patientId; ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="record_date" class="form-label">Record Date</label>
                            <input type="date" class="form-control" id="record_date" name="record_date" 
                                value="<?php echo $record['record_date']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="examination" class="form-label">Examination/Diagnosis</label>
                        <textarea class="form-control" id="examination" name="examination" rows="5" required><?php echo htmlspecialchars($record['examination']); ?></textarea>
                        <div class="form-text">Enter detailed information about the patient's condition, symptoms, and diagnosis.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="direction" class="form-label">Treatment Directions</label>
                        <textarea class="form-control" id="direction" name="direction" rows="5" required><?php echo htmlspecialchars($record['direction']); ?></textarea>
                        <div class="form-text">Enter treatment plan, medication, follow-up instructions, and other recommendations.</div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="viewPatient.php?id=<?php echo $patientId; ?>" class="btn btn-outline-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Medical Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../js/scripts.html'; ?>
</body>
</html>