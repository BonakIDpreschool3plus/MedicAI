<?php
// filepath: c:\xampp\htdocs\clinicManagement\Controller\checkUsername.php
require_once '../Model/config.php';

header('Content-Type: application/json');

if (isset($_GET['username'])) {
    $username = filter_input(INPUT_GET, 'username', FILTER_SANITIZE_STRING);
    
    $stmt = $conn->prepare("SELECT username FROM patient_creds WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo json_encode(['exists' => $result->num_rows > 0]);
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['error' => 'No username provided']);
}
?>