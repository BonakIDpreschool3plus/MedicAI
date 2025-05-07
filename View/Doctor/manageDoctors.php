<?php

require_once '../../Model/config.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['is_logged_in']) || !isset($_SESSION['is_doctor']) || $_SESSION['is_doctor'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Use the correct session variable for doctor_id
$doctorId = $_SESSION['doctor_id']; // This was using 'user_id' instead of 'doctor_id'

// Check if user is admin directly from the session
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: doctorDashboard.php");
    exit();
}

// Get doctor name for header
$doctorName = $_SESSION['doctor_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../css/links.html'; ?>
    <title>Manage Doctors</title>
</head>
<body>
    <?php include '../../Components/doctor-header.php'; ?>
    
    <div class="container mt-4">
        <h2>Doctor Management</h2>
        
        <?php if(isset($_SESSION['admin_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['admin_message_type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['admin_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['admin_message']); unset($_SESSION['admin_message_type']); ?>
        <?php endif; ?>
        
        <ul class="nav nav-tabs mb-4" id="doctorTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="true">
                    Pending Approvals
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" type="button" role="tab" aria-controls="approved" aria-selected="false">
                    Approved Doctors
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected" type="button" role="tab" aria-controls="rejected" aria-selected="false">
                    Rejected Applications
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="doctorTabsContent">
            <!-- Pending Approvals Tab -->
            <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0">Pending Doctor Applications</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Specialty</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Registration Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Get pending doctor applications
                                    $pendingStmt = $conn->prepare("SELECT id, firstName, lastName, specialty, email, phone, created_at FROM doctor_creds WHERE status = 'pending' ORDER BY created_at DESC");
                                    $pendingStmt->execute();
                                    $pendingResult = $pendingStmt->get_result();
                                    
                                    if ($pendingResult->num_rows > 0) {
                                        while ($row = $pendingResult->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>Dr. " . htmlspecialchars($row['firstName']) . " " . htmlspecialchars($row['lastName']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['specialty']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                                            echo "<td>" . date("M d, Y", strtotime($row['created_at'])) . "</td>";
                                            echo "<td>
                                                <form action='../../Controller/manageDoctorsController.php' method='post' style='display:inline;'>
                                                    <input type='hidden' name='doctor_id' value='" . $row['id'] . "'>
                                                    <input type='hidden' name='action' value='approve'>
                                                    <button type='submit' class='btn btn-success btn-sm'>Approve</button>
                                                </form>
                                                <form action='../../Controller/manageDoctorsController.php' method='post' style='display:inline;' class='ms-1'>
                                                    <input type='hidden' name='doctor_id' value='" . $row['id'] . "'>
                                                    <input type='hidden' name='action' value='reject'>
                                                    <button type='submit' class='btn btn-danger btn-sm'>Reject</button>
                                                </form>
                                              </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center'>No pending applications</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Approved Doctors Tab -->
            <div class="tab-pane fade" id="approved" role="tabpanel" aria-labelledby="approved-tab">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Approved Doctors</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Specialty</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Admin Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Get approved doctors
                                    $approvedStmt = $conn->prepare("SELECT id, firstName, lastName, specialty, email, phone, is_admin FROM doctor_creds WHERE status = 'approved' ORDER BY lastName ASC");
                                    $approvedStmt->execute();
                                    $approvedResult = $approvedStmt->get_result();
                                    
                                    if ($approvedResult->num_rows > 0) {
                                        while ($row = $approvedResult->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>Dr. " . htmlspecialchars($row['firstName']) . " " . htmlspecialchars($row['lastName']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['specialty']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                                            echo "<td>" . ($row['is_admin'] ? '<span class="badge bg-primary">Admin</span>' : '<span class="badge bg-secondary">Regular</span>') . "</td>";
                                            echo "<td>";
                                            
                                            // Don't show admin toggle for self
                                            if ($row['id'] != $doctorId) {
                                                echo "<form action='../../Controller/manageDoctorsController.php' method='post' style='display:inline;'>";
                                                echo "<input type='hidden' name='doctor_id' value='" . $row['id'] . "'>";
                                                
                                                if ($row['is_admin']) {
                                                    echo "<input type='hidden' name='action' value='remove_admin'>";
                                                    echo "<button type='submit' class='btn btn-warning btn-sm'>Remove Admin</button>";
                                                } else {
                                                    echo "<input type='hidden' name='action' value='make_admin'>";
                                                    echo "<button type='submit' class='btn btn-info btn-sm'>Make Admin</button>";
                                                }
                                                
                                                echo "</form>";
                                            } else {
                                                echo "<span class='text-muted'>Current User</span>";
                                            }
                                            
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center'>No approved doctors</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Rejected Applications Tab -->
            <div class="tab-pane fade" id="rejected" role="tabpanel" aria-labelledby="rejected-tab">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">Rejected Applications</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Specialty</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Rejection Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Get rejected applications
                                    $rejectedStmt = $conn->prepare("SELECT id, firstName, lastName, specialty, email, phone, updated_at FROM doctor_creds WHERE status = 'rejected' ORDER BY updated_at DESC");
                                    $rejectedStmt->execute();
                                    $rejectedResult = $rejectedStmt->get_result();
                                    
                                    if ($rejectedResult->num_rows > 0) {
                                        while ($row = $rejectedResult->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>Dr. " . htmlspecialchars($row['firstName']) . " " . htmlspecialchars($row['lastName']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['specialty']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                                            echo "<td>" . date("M d, Y", strtotime($row['updated_at'])) . "</td>";
                                            echo "<td>
                                                <form action='../../Controller/manageDoctorsController.php' method='post'>
                                                    <input type='hidden' name='doctor_id' value='" . $row['id'] . "'>
                                                    <input type='hidden' name='action' value='approve'>
                                                    <button type='submit' class='btn btn-success btn-sm'>Reconsider & Approve</button>
                                                </form>
                                              </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center'>No rejected applications</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../js/scripts.html'; ?>
</body>
</html>