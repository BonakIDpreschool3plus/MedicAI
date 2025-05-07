<?php
session_start();

// Ensure user is logged in before proceeding
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['response' => 'User not logged in']);
    exit();
}

// Retrieve patient ID from session
$patientId = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate user message input
    $userMessage = trim($_POST['message'] ?? '');

    if (empty($userMessage)) {
        echo json_encode(['response' => 'Message cannot be empty']);
        exit();
    }

    if (!$patientId) {
        echo json_encode(['response' => 'Patient ID is missing']);
        exit();
    }

    // Get AI response by calling external handler
    $aiResponse = getAIResponse($userMessage);

    // Insert chat history into the database
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=doctor_appointment_db', '', 'root');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("INSERT INTO patient_chat_history (patient_id, message, response) VALUES (?, ?, ?)");
        $stmt->execute([$patientId, $userMessage, $aiResponse]);

        echo json_encode(['response' => $aiResponse]);

    } catch (PDOException $e) {
        echo json_encode(['response' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Function to get AI response via cURL from external API
 */
function getAIResponse($message) {
    $apiUrl = 'https://medic-ai-handler.onrender.com/ai_chat_handler.php';

    $payload = json_encode(['message' => $message]);

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);

    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return "Sorry, there was an issue connecting to the AI service.";
    }

    curl_close($ch);

    $responseData = json_decode($result, true);

    return $responseData['reply'] ?? "Sorry, no AI response received.";
}
?>
