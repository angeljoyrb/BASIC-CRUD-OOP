<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

include 'db_connection.php'; // Database connection

class AdminDeleteUser {
    private $conn;
    private $errors = [];

    public function __construct($conn) {
        $this->conn = $conn;
        $this->checkAccess();
    }

    // Check if user is logged in and is an admin
    private function checkAccess() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php");
            exit();
        }//Check if user is admin
        if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
            header("Location: login.php");
            exit();
        }
    }
    //Allows admin to delete users from the database
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: admin_dashboard.php?msg=deleted_successfully");
            exit();
        } else {
            $this->errors[] = "Database Error: " . $this->conn->error;
            $stmt->close();
            header("Location: admin_dashboard.php?error=db_error");
            exit();
        }
    }
    //Check errors if there are any
    public function getErrors() {
        return $this->errors;
    }
}

// Instantiate Database and AdminDeleteUser
$db = new Database();
$conn = $db->getConnection();
$deleter = new AdminDeleteUser($conn);

// Allow deletion only via GET request with user ID
if (isset($_GET['id'])) {
    $target_id = (int) $_GET['id']; 
    $deleter->delete($target_id);
}

exit();