<?php
// filepath: c:\xampp\htdocs\clinicManagement\View\ai_chat_handler.php

header('Content-Type: application/json');

// Read the incoming request and extract the user's message
$data = json_decode(file_get_contents("php://input"), true);
$userMessage = $data['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(["reply" => "Please enter a message."]);
    exit();
}

// Together.ai API information
$apiKey = '64306136c591e5b146f2aeaffe88a6acbc7b825d420b20c78c02bc81d9081c9b';
$apiUrl = 'https://api.together.xyz/inference';

// Construct the dynamic prompt for the AI to focus on specific categories
$prompt = <<<PROMPT
You are Medic AI, a friendly and smart virtual medical assistant. You can understand symptoms even if the user writes them informally, like "my eyes are red" or "I have a headache."

Your task is to help users with the following 5 categories:
1. Skin issues (e.g., rashes, acne, eczema)
2. Common viruses (e.g., cold, flu, fever)
3. Headaches (e.g., migraines, tension headaches)
4. Stomach pain (e.g., cramps, bloating, indigestion)
5. Basic eye problems (e.g., red eyes, dryness, irritation)

- Please interpret informal language, and try to understand symptoms from user messages.
- If the symptom doesn't fit the above categories, kindly inform the user: "I can only help with skin issues, viruses, headaches, stomach pain, or eye problems."

User says: "$userMessage"
Medic AI:
PROMPT;

// Prepare the payload to send to the API
$payload = json_encode([
    'model' => 'mistralai/Mixtral-8x7B-Instruct-v0.1',
    'prompt' => $prompt,
    'max_tokens' => 200,
    'temperature' => 0.7
]);

// Send POST request to the AI API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $apiKey"
]);

$response = curl_exec($ch);
curl_close($ch);

// Debugging: Save the raw response to a log file for analysis
file_put_contents('debug_log.txt', print_r($response, true), FILE_APPEND);

// Check if the response is correctly formatted and contains the AI's message
$responseData = json_decode($response, true);

// Debugging: Log the entire response data for further inspection
file_put_contents('debug_log.txt', print_r($responseData, true), FILE_APPEND);

// If the 'choices' field exists, extract the response
if (isset($responseData['choices'][0]['text'])) {
    $aiText = $responseData['choices'][0]['text'];
} else {
    // If response is unexpected, return a default message
    $aiText = "I'm sorry, I couldn't understand that.";
}

// Return the AI's response as JSON
echo json_encode(["reply" => trim($aiText)]);
?>
