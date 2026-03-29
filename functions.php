<?php
class User {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Redirect based on role
    public function redirectByRole($role) {
        $clean_role = strtolower(trim($role));
        if ($clean_role === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: user_dashboard.php");
        }
        exit();
    }

    // Get user by ID
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Update user info (admin)
    public function updateInfoAdmin($id, $fname, $lname, $email, $gender, $role, $address) {
        $stmt = $this->conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, gender=?, role=?, address=? WHERE id=?");
        $stmt->bind_param("ssssssi", $fname, $lname, $email, $gender, $role, $address, $id);
        return $stmt->execute();
    }

    // Update user info (user)
    public function updateInfoUser($id, $fname, $lname, $email, $gender, $address) {
        $stmt = $this->conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, gender=?, address=? WHERE id=?");
        $stmt->bind_param("sssssi", $fname, $lname, $email, $gender, $address, $id);
        return $stmt->execute();
    }

    // Update password
    public function updatePassword($id, $new_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $hashed_password, $id);
        return $stmt->execute();
    }

    // Toggle user status
    public function toggleStatus($id) {
        $stmt = $this->conn->prepare("UPDATE users SET status = IF(status='active','inactive','active') WHERE id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Delete user
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}