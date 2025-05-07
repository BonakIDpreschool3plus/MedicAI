<?php
// filepath: c:\xampp\htdocs\clinicManagement\Controller\checkEmail.php
require_once '../Model/config.php';

header('Content-Type: application/json');

if (isset($_GET['email'])) {
    $email = filter_input(INPUT_GET, 'email', FILTER_SANITIZE_EMAIL);
    
    $stmt = $conn->prepare("SELECT email FROM patient_creds WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo json_encode(['exists' => $result->num_rows > 0]);
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['error' => 'No email provided']);
}
?>