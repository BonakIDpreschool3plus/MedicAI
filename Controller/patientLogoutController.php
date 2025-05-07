<?php
session_start();

// Get username before clearing session for use in redirect
$username = '';
if (isset($_SESSION['is_doctor']) && $_SESSION['is_doctor'] === true) {
    $username = isset($_SESSION['doctor_username']) ? $_SESSION['doctor_username'] : '';
} else {
    $username = isset($_SESSION['patient_username']) ? $_SESSION['patient_username'] : '';
}

// Clear all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear remember me cookies if they exist
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('remember_user', '', time() - 3600, '/');
    setcookie('remember_type', '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: ../index.php?logout=success&user=" . urlencode($username));
exit();
?>