<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$fullName = $_SESSION['full_name'];
$profileImage = $_SESSION['profile_image'];

// Load chat history once here and use it both in sidebar and chat display
try {
    $pdo = new PDO('mysql:host=sql201.infinityfree.com;dbname=if0_38748893_admin', 'if0_38748893', 'Lemuel07');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT id, message, response FROM patient_chat_history WHERE patient_id = ? ORDER BY timestamp ASC");
    $stmt->execute([$userId]);
    $history = $stmt->fetchAll();
} catch (PDOException $e) {
    $history = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Medic AI Chat</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php include '../../View/css/links.html'; ?>
  <style>
    body, html {
      margin: 0;
      padding: 0;
      height: 100%;
      font-family: 'Segoe UI', Arial, sans-serif;
      overflow: hidden;
      background: #ececec;
    }

    .sidebar {
      height: 100vh;
      width: 250px;
      position: fixed;
      left: -250px;
      top: 0;
      background: #202123;
      color: #fff;
      padding: 1rem;
      transition: left 0.35s ease;
      z-index: 999;
      overflow-y: auto;
    }

    .sidebar.active {
      left: 0;
    }

    .sidebar h4 {
      margin-bottom: 1rem;
    }

    #closeSidebar {
      position: absolute;
      top: 10px;
      right: 10px;
      background: none;
      border: none;
      color: #fff;
      font-size: 22px;
      cursor: pointer;
    }

    .sidebar a, .sidebar button {
      color: #eee;
      text-decoration: none;
      padding: 0.6rem 0.8rem;
      display: block;
      border-radius: 5px;
      margin-bottom: 10px;
      border: none;
      text-align: left;
      background: #333;
      cursor: pointer;
      transition: background 0.2s ease;
    }

    .sidebar a:hover, .sidebar button:hover {
      background: #444;
    }

    .chat-container {
      display: flex;
      flex-direction: column;
      height: 100vh;
      margin-left: 0;
      transition: margin-left 0.35s ease;
    }

    .chat-container.sidebar-active {
      margin-left: 250px;
    }

    .chat-header {
      height: 60px;
      background: #f8f9fa;
      border-bottom: 1px solid #ccc;
      display: flex;
      align-items: center;
      padding: 0 1rem;
    }

    #sidebarToggle {
      background: none;
      border: none;
      font-size: 26px;
      cursor: pointer;
    }

    .chat-messages {
      flex: 1;
      overflow-y: auto;
      padding: 1rem;
      background: #ececec;
      display: flex;
      flex-direction: column;
    }

    .message {
      background: white;
      padding: 0.8rem 1rem;
      border-radius: 10px;
      margin-bottom: 0.8rem;
      max-width: 85%;
      word-wrap: break-word;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .message.user {
      background: #d1f7c4;
      align-self: flex-end;
    }

    .message.ai {
      background: #fff;
      align-self: flex-start;
    }

    .chat-input {
      display: flex;
      padding: 1rem;
      background: #f8f9fa;
      border-top: 1px solid #ccc;
    }

    .chat-input textarea {
      flex: 1;
      resize: none;
      padding: 0.5rem;
      border-radius: 5px;
      border: 1px solid #ccc;
    }

    .chat-input button {
      margin-left: 10px;
      padding: 0.5rem 1rem;
      background: #28a745;
      color: white;
      border: none;
      border-radius: 5px;
      transition: background 0.2s ease;
    }

    .chat-input button:hover {
      background: #218838;
    }

    .loading {
      display: flex;
      gap: 6px;
      align-items: center;
      margin: 10px 0;
    }

    .dot {
      width: 8px;
      height: 8px;
      background: #999;
      border-radius: 50%;
      animation: blink 1.2s infinite ease-in-out both;
    }

    .dot:nth-child(2) { animation-delay: 0.2s; }
    .dot:nth-child(3) { animation-delay: 0.4s; }

    @keyframes blink {
      0%, 80%, 100% { opacity: 0.3; transform: scale(0.9); }
      40% { opacity: 1; transform: scale(1.2); }
    }

    /* Profile image & dropdown styles */
    .profile-container {
      position: fixed;
      top: 10px;
      right: 10px;
      z-index: 1000;
    }

    .profile-img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      cursor: pointer;
      object-fit: cover;
    }

    .dropdown-menu {
      display: none;
      position: absolute;
      right: 0;
      top: 50px;
      background: #fff;
      border: 1px solid #ccc;
      border-radius: 5px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      z-index: 20;
    }

    .dropdown-menu a {
      display: block;
      padding: 10px;
      color: #333;
      text-decoration: none;
    }

    .dropdown-menu a:hover {
      background: #f0f0f0;
    }

    @media (max-width: 768px) {
      .sidebar {
        width: 220px;
        left: -220px;
      }

      .sidebar.active {
        left: 0;
      }

      .chat-header {
        padding: 0 0.5rem;
      }

      #sidebarToggle {
        font-size: 24px;
      }
    }

  </style>
</head>
<body>

<div class="profile-container">
  <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile Image" class="profile-img" id="profileImg">
  <div class="dropdown-menu" id="dropdownMenu">
    <a href="profileAI.php">View Profile</a>
    <a href="../../Controller/patientLogoutController.php">Logout</a>
  </div>
</div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <button id="closeSidebar">✖</button>
  <h4>MedicAI</h4>
  <a href="patientDashboard.php">Patient Dashboard</a>
  <form action="../../Controller/download_pdf.php" method="POST" target="_blank">
    <!-- ✅ Hidden patient_id input added -->
    <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($userId); ?>">
    <?php
    if ($history) {
      foreach ($history as $chat) {
        echo '<div class="form-check">';
        echo '<input class="form-check-input" type="checkbox" name="selected_chats[]" value="' . $chat['id'] . '" id="chatCheck' . $chat['id'] . '">';
        echo '<label class="form-check-label text-light" for="chatCheck' . $chat['id'] . '">' . htmlspecialchars(substr($chat['message'], 0, 20)) . '...</label>';
        echo '</div>';
      }
    } else {
      echo '<p class="text-muted">No messages yet.</p>';
    }
    ?>
    <button type="submit" class="btn btn-primary btn-sm mt-2 w-100">Download PDF</button>
  </form>
</div>

<!-- Chat Container -->
<div class="chat-container" id="chatContainer">
  <div class="chat-header">
    <button id="sidebarToggle">☰</button>
  </div>

  <div id="chatMessages" class="chat-messages">
    <?php
    if ($history) {
      foreach ($history as $chat) {
        echo '<div class="message user"><strong>You:</strong><br>' . htmlspecialchars($chat['message']) . '</div>';
        echo '<div class="message ai"><strong>Medic AI:</strong><br>' . htmlspecialchars($chat['response']) . '</div>';
      }
    } else {
      echo '<p class="text-muted">Start a new conversation below.</p>';
    }
    ?>
  </div>

  <form id="aiChatForm" class="chat-input">
    <textarea id="userMessage" name="message" rows="2" class="form-control" placeholder="Describe your symptoms..."></textarea>
    <button type="submit" class="btn btn-success">Send</button>
  </form>
</div>

<script>
document.getElementById('aiChatForm').addEventListener('submit', function(e) {
  e.preventDefault();

  const message = document.getElementById('userMessage').value.trim();
  if (message === "") return;

  const loading = document.createElement('div');
  loading.classList.add('loading');
  loading.innerHTML = '<span class="dot"></span><span class="dot"></span><span class="dot"></span>';
  document.getElementById('chatMessages').appendChild(loading);

  const formData = new FormData();
  formData.append('message', message);

  fetch('../../Controller/aiHandler.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    loading.remove();

    const userMessageDiv = document.createElement('div');
    userMessageDiv.classList.add('message', 'user');
    userMessageDiv.innerHTML = `<strong>You:</strong><br>${message}`;
    document.getElementById('chatMessages').appendChild(userMessageDiv);

    const aiMessageDiv = document.createElement('div');
    aiMessageDiv.classList.add('message', 'ai');
    aiMessageDiv.innerHTML = `<strong>Medic AI:</strong><br>${data.response}`;
    document.getElementById('chatMessages').appendChild(aiMessageDiv);

    document.getElementById('chatMessages').scrollTop = document.getElementById('chatMessages').scrollHeight;
    document.getElementById('userMessage').value = '';
  })
  .catch(error => {
    console.error('Error:', error);
    loading.remove();
    alert('There was an issue processing your request. Please try again.');
  });
});

document.getElementById("profileImg").addEventListener("click", () => {
  document.getElementById("dropdownMenu").classList.toggle("show");
});

document.getElementById("sidebarToggle").addEventListener("click", () => {
  const sidebar = document.getElementById("sidebar");
  const chatContainer = document.getElementById("chatContainer");
  const hamburgerIcon = document.getElementById("sidebarToggle");

  sidebar.classList.toggle("active");
  chatContainer.classList.toggle("sidebar-active");

  hamburgerIcon.innerHTML = sidebar.classList.contains("active") ? "✖" : "☰";
});

document.getElementById("closeSidebar").addEventListener("click", () => {
  document.getElementById("sidebar").classList.remove("active");
  document.getElementById("chatContainer").classList.remove("sidebar-active");
  document.getElementById("sidebarToggle").innerHTML = "☰";
});
</script>
</body>
</html>
