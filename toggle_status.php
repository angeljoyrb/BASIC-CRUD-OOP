<?php
session_start();

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

include 'db_connection.php'; // OOP Database class
include 'functions.php';      // Reusable functions and User class
class ToggleUserStatus {
    private $conn;
    private $userModel;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->userModel = new User($conn);
        $this->checkAccess();
    }
    // Check if user is logged in and is an admin
    private function checkAccess() {
        if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
            header("Location: login.php");
            exit();
        }
    }
    // Toggle user status between active and inactive
    public function toggle($id) {
        if ($this->userModel->toggleStatus($id)) {
            header("Location: admin_dashboard.php?msg=status_updated");
            exit();
        } else {
            header("Location: admin_dashboard.php?error=update_failed");
            exit();
        }
    }
     
}
    

// Instantiate Database and ToggleUserStatus
$db = new Database();
$conn = $db->getConnection();
$toggler = new ToggleUserStatus($conn);

// Perform toggle if ID is provided
if (isset($_GET['id'])) {
    $user_id = (int) $_GET['id']; // sanitize ID
    $toggler->toggle($user_id);
} else {
    header("Location: admin_dashboard.php");
    exit();
}