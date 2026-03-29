<?php
session_start();

class SessionManager {
    public function logout() {
        // Clear all session variables
        session_unset();
        // Destroy the session
        session_destroy();

        // Redirect to login page
        echo "<script>
            window.location.replace('login.php');
        </script>";
        exit();
    }
}

$session = new SessionManager();
$session->logout();
?>