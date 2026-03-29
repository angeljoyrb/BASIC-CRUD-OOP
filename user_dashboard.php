<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Prevent admins from accessing user dashboard
if (strtolower($_SESSION['role']) === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

include 'db_connection.php';  // Database connection
include 'functions.php';      // Reusable functions

// Instantiate Database and User model
$db = new Database();
$conn = $db->getConnection();
$userModel = new User($conn);

$user_id = $_SESSION['user_id'];
$message = "";

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if ($userModel->updateInfoUser(
        $user_id,
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['email'],
        $_POST['gender'],
        $_POST['address']
    )) {
        $message = "<div style='color: green; background: #e6ffed; padding: 10px; margin-bottom: 15px; border-radius: 5px;'>Profile updated successfully!</div>";
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $pw = $_POST['new_password'];
    if (strlen($pw) >= 8 && $pw === $_POST['confirm_password']) {
        if ($userModel->updatePassword($user_id, $pw)) {
            $message = "<div style='color: green; background: #e6ffed; padding: 10px; margin-bottom: 15px; border-radius: 5px;'>Password changed successfully!</div>";
        }
    } else {
        $message = "<div style='color: red; background: #ffeef0; padding: 10px; margin-bottom: 15px; border-radius: 5px;'>Passwords must match and be at least 8 characters.</div>";
    }
}

// Fetch user data
$user = $userModel->getById($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body style="background-color:#f0f2f5; margin:0">
    <div class="dashboard-wrapper">
        <div class="main-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2>User Management System - User Dashboard</h2>
                <a href="logout.php" class="back-btn" style="background: #666;">Logout</a>
            </div>

            <?php echo $message; ?>

            <h3 class="section-header">Update Information</h3>
            <form method="POST">
                <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                    <div style="flex: 1;">
                        <label>First Name</label>
                        <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                </div>
                <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                    <div style="flex: 1;">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label>Gender</label>
                        <select name="gender" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="Male" <?php echo ($user['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($user['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($user['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label>Address</label>
                        <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($user['address']); ?>" required>
                    </div>
                </div>
                <button type="submit" name="update_profile" class="save-btn" style="width: auto; padding: 10px 30px;">Update Profile</button>
            </form>

            <h3 class="section-header">Security</h3>
            <form method="POST">
                <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                    <div style="flex: 1;">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Minimum of 8 characters" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                <button type="submit" name="change_password" class="save-btn" style="width: auto; padding: 10px 30px; background-color: #28a745;">Change Password</button>
            </form>

            <p style="margin-top: 30px;">Once you delete your account, you will lose access to all your data. This cannot be undone.</p>
            <a href="deleteuseracc.php?id=<?php echo $user_id; ?>" 
               onclick="return confirm('WARNING: Are you sure you want to PERMANENTLY delete your account?');" 
               style="background: #c53030; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; margin-top: 10px;">
               Delete My Account
            </a>
        </div>
    </div>
</body>
</html>