<?php
session_start();
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    if (isset($_SESSION['is_doctor']) && $_SESSION['is_doctor'] === true) {
        header("Location: Doctor/doctorDashboard.php");
    } else {
        header("Location: Patient/patientAI.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'css/links.html'; ?>
    <title>Medic AI - Welcome</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .main-content {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 20px;
        }
        .chat-container {
            width: 100%;
            max-width: 700px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            height: 80vh;
        }
        .chat-header {
            background-color: #007bff;
            color: white;
            padding: 15px;
            font-size: 18px;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            text-align: center;
        }
        #chatBox {
            flex-grow: 1;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
            background-color: #f1f1f1;
            border-bottom: 1px solid #ddd;
        }
        .chat-message {
            padding: 10px 15px;
            border-radius: 20px;
            font-size: 16px;
            line-height: 1.4;
            word-wrap: break-word;
            display: inline-block;
            margin-bottom: 10px;
        }
        .user-message {
            background-color: #dcf8c6;
            align-self: flex-end;
            border-radius: 20px;
            color: #333;
        }
        .ai-message {
            background-color: #ffffff;
            align-self: flex-start;
            border: 1px solid #ddd;
            color: #333;
        }
        .loading-message {
            text-align: center;
            color: #007bff;
            font-style: italic;
        }
        .input-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-top: 1px solid #ddd;
        }
        #userInput {
            width: 85%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 30px;
            font-size: 16px;
            outline: none;
            box-sizing: border-box;
        }
        .btn-send {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 24px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-left: 10px;
        }
        .btn-send:hover {
            background-color: #0056b3;
        }
        .error-message {
            color: red;
            font-style: italic;
            text-align: center;
        }
        @media (max-width: 768px) {
            .chat-container { width: 100%; padding: 10px; }
            #userInput { padding: 10px; font-size: 14px; }
            .btn-send { width: 40px; height: 40px; font-size: 18px; }
        }
    </style>
</head>
<body>
    <?php include '../Components/guest-header.php'; ?>
    <div class="main-content">
        <div class="chat-container">
            <div class="chat-header">Chat with Medic AI</div>
            <div id="chatBox"></div>
            <div class="input-container">
                <input type="text" id="userInput" placeholder="Enter your symptoms..." />
                <button class="btn-send" onclick="sendMessage()">➤</button>
            </div>
        </div>
    </div>
    <script>
    function sendMessage() {
        const input = document.getElementById('userInput');
        const message = input.value;
        if (!message) return;
        const chatBox = document.getElementById('chatBox');
        const userMessageDiv = document.createElement('div');
        userMessageDiv.classList.add('chat-message', 'user-message');
        userMessageDiv.innerHTML = message;
        chatBox.appendChild(userMessageDiv);
        input.value = '';
        chatBox.scrollTop = chatBox.scrollHeight;

        // Show loading message while waiting for AI response
        const loadingMessageDiv = document.createElement('div');
        loadingMessageDiv.classList.add('loading-message');
        loadingMessageDiv.innerHTML = "Loading...";
        chatBox.appendChild(loadingMessageDiv);
        chatBox.scrollTop = chatBox.scrollHeight;

        fetch('https://medic-ai-handler.onrender.com/ai_chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message })
        })
        .then(res => res.json())
        .then(data => {
            // Remove loading message and display AI response
            loadingMessageDiv.remove();
            const aiMessageDiv = document.createElement('div');
            aiMessageDiv.classList.add('chat-message', 'ai-message');
            aiMessageDiv.innerHTML = data.reply;
            chatBox.appendChild(aiMessageDiv);
            chatBox.scrollTop = chatBox.scrollHeight;
        })
        .catch(err => {
            // Remove loading message and show error message
            loadingMessageDiv.remove();
            const errorMessageDiv = document.createElement('div');
            errorMessageDiv.classList.add('chat-message', 'error-message');
            errorMessageDiv.innerHTML = `<em>Error: Could not contact AI.</em>`;
            chatBox.appendChild(errorMessageDiv);
            console.error(err);
        });
    }
    </script>
    <?php include 'js/scripts.html'; ?>
</body>
</html>
