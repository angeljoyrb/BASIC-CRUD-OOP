<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 
// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connection.php';  // Database connection
include 'functions.php';      // Reusable functions

// Instantiate Database and User model 
$db = new Database();
$conn = $db->getConnection();
$userModel = new User($conn);

$message = "";

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id     = $_POST['id'];
    $fname  = $_POST['first_name'];
    $lname  = $_POST['last_name'];
    $email  = $_POST['email'];
    $gender = $_POST['gender'];
    $address= $_POST['address'];   
    $role   = $_POST['role'];

    if (!empty($fname) && !empty($lname) && !empty($email)) {
        if ($userModel->updateInfoAdmin($id, $fname, $lname, $email, $gender, $role, $address)) {
            echo "<script>
                alert('User updated successfully!');
                window.location.replace('admin_dashboard.php');
            </script>";
            exit();
        } else {
            $message = "<div style='color: red; margin-bottom: 10px;'>Error updating user.</div>";
        }
    }
}

// Get user data
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
    $user = $userModel->getById($user_id);

    if (!$user) {
        header("Location: admin_dashboard.php");
        exit();
    }
} else {
    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header-container">
        <h2>Edit User Details</h2>
        <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>

    <div class="main-content">
        <div class="form-container">
            <?php echo $message; ?>
            <form action="admin_edituser.php?id=<?php echo $user['id']; ?>" method="POST">
                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">  

                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" class="form-control">
                        <option value="Male" <?php echo ($user['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($user['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo ($user['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" class="form-control">
                        <option value="admin" <?php echo (strtolower($user['role']) === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="user" <?php echo (strtolower($user['role']) === 'user') ? 'selected' : ''; ?>>User</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($user['address']); ?>" required>
                </div>

                <button type="submit" name="update_user" class="save-btn">Update User Info</button>
            </form>
        </div>
    </div>
</body>
</html>