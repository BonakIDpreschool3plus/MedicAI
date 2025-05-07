<?php
// filepath: c:\xampp\htdocs\clinicManagement\Model\checkRememberMe.php
// This file should be included at the top of pages where auto-login is needed

function checkRememberMe($conn) {
    // Check if user is already logged in
    if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in']) {
        return;
    }
    
    // Check if remember me cookie exists
    if (isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
        $token = $_COOKIE['remember_token'];
        $userId = $_COOKIE['remember_user'];
        
        // Get token from database
        $stmt = $conn->prepare("SELECT t.token_hash, t.expiry, u.id, u.username, u.firstName, u.lastName 
                              FROM auth_tokens t 
                              JOIN patient_creds u ON t.user_id = u.id 
                              WHERE t.user_id = ? AND t.expiry > NOW()");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Verify token
            if (password_verify($token, $row['token_hash'])) {
                // Token is valid, create session
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['full_name'] = $row['firstName'] . ' ' . $row['lastName'];
                $_SESSION['is_logged_in'] = true;
                
                // Optional: Refresh token
                $newToken = bin2hex(random_bytes(32));
                $newTokenHash = password_hash($newToken, PASSWORD_DEFAULT);
                $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                // Update token in database
                $updateStmt = $conn->prepare("UPDATE auth_tokens SET token_hash = ?, expiry = ? WHERE user_id = ?");
                $updateStmt->bind_param("ssi", $newTokenHash, $expiry, $userId);
                $updateStmt->execute();
                
                // Update cookie
                setcookie('remember_token', $newToken, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                
                $updateStmt->close();
            }
        }
        
        $stmt->close();
    }
}
?>