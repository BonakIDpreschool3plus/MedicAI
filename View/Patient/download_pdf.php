<?php
// Enable detailed error reporting (for development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Autoload Dompdf
require 'C:\xampp\htdocs\medicAI\vendor\autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Check for required data
if (!isset($_POST['patient_id']) || !isset($_POST['selected_chats'])) {
    die("Missing patient ID or selected chats.");
}

$patientId = $_POST['patient_id'];
$selectedChats = $_POST['selected_chats'];

// Validate selected chat IDs
if (!is_array($selectedChats) || empty($selectedChats)) {
    die("No chat entries selected.");
}

// Database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=doctor_appointment_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Prepare placeholders for selected chat IDs
$placeholders = implode(',', array_fill(0, count($selectedChats), '?'));
$sql = "SELECT message, response, timestamp FROM patient_chat_history WHERE id IN ($placeholders) AND patient_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([...$selectedChats, $patientId]);

$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$history) {
    die("No chat history found for selected entries.");
}

// Build HTML content
$html = "
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h2 { text-align: center; margin-bottom: 30px; }
        .chat-entry { margin-bottom: 20px; }
        .chat-entry strong { color: #333; }
        .chat-entry small { color: #666; display: block; margin-top: 5px; }
        hr { border: none; border-top: 1px solid #ccc; margin: 10px 0; }
    </style>
    <h2>Medic AI Chat Report</h2>
";

foreach ($history as $chat) {
    $html .= "<div class='chat-entry'>";
    $html .= "<p><strong>You:</strong> " . htmlspecialchars($chat['message']) . "</p>";
    $html .= "<p><strong>Medic AI:</strong> " . htmlspecialchars($chat['response']) . "</p>";
    $html .= "<small>Time: " . htmlspecialchars($chat['timestamp']) . "</small>";
    $html .= "<hr></div>";
}

// Initialize Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output to browser
$dompdf->stream("MedicAI_Selected_Report.pdf", ["Attachment" => 1]);
