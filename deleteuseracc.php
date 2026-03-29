<?php
session_start();

include_once 'db_connection.php';  // Dattabase connection
include_once 'functions.php';      // Reusable functions

// Only proceed if a user is logged in
if (isset($_SESSION['user_id'])) {
    $db = new Database();
    $conn = $db->getConnection();
    $userModel = new User($conn);

    $user_id = $_SESSION['user_id'];

    if ($userModel->delete($user_id)) {
        // Clear session
        session_unset();
        // Destroy session  
        session_destroy();

        // Redirect to login page with success message
        echo "<script>
            alert('Your account has been successfully deleted.');
            window.location.replace('login.php');
        </script>";
        exit();
    } else {
        echo "Error deleting record.";
    }
} else {
    header("Location: login.php");
    exit();
}
?>