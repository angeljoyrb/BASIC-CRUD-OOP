<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

include 'db_connection.php'; // database connection

class AdminDashboard {
    private $conn;
    private $errors = [];
    private $search = '';
    private $users = [];

    public function __construct($conn) {
        $this->conn = $conn;
        $this->checkAccess();
        $this->handleSearch();
        $this->fetchUsers();
    }
    // Check if user is logged in and is an admin
    private function checkAccess() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php");
            exit();
        }
        //Check if user is admin
        if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
            header("Location: login.php");
            exit();
        }
        //Check if user is a regular user and redirect to user dashboard
        if (strtolower($_SESSION['role']) === 'user') {
            header("Location: user_dashboard.php");
            exit();
        }
    }
    // Handle search input
    private function handleSearch() {
        $this->search = $_GET['search'] ?? '';
    }
    //Show all users from database
    private function fetchUsers() {
        if (!empty($this->search)) {
            $search_safe = $this->conn->real_escape_string($this->search);
            $sql = "SELECT * FROM users WHERE 
                    first_name LIKE '%$search_safe%' OR
                    last_name LIKE '%$search_safe%' OR
                    email LIKE '%$search_safe%'";
        } else {
            $sql = "SELECT * FROM users";
        }

        $result = $this->conn->query($sql);
        if ($result) {
            $this->users = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            $this->errors[] = "Database Error: " . $this->conn->error;
        }
    }
    //Shows users in the dashboard
    public function getUsers() {
        return $this->users;
    }
    //Shows search query in the search input
    public function getSearch() {
        return htmlspecialchars($this->search);
    }
    //Shows errors if there are any
    public function getErrors() {
        return $this->errors;
    }
}

// Instantiate Database and AdminDashboard
$db = new Database();
$conn = $db->getConnection();
$dashboard = new AdminDashboard($conn);
$users = $dashboard->getUsers();
$errors = $dashboard->getErrors();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="header-container">
        <h2>User Management System - Admin Dashboard</h2>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
    
    <div class="main-content">
        <div class="search-container">
            <form method="GET" style="width: 100%; display: flex; gap: 10px;">
                <input type="text" name="search" class="search-input" placeholder="Search by name or email..." value="<?php echo $dashboard->getSearch(); ?>">
                <button type="submit" class="search-button">Search</button>
            </form>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted_successfully'): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #c3e6cb; font-weight: bold; text-align: center;">
                Deleted successfully!
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div style="color:red; margin-bottom:15px;">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Gender</th>
                    <th>Role</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['gender']); ?></td>
                        <td><?php echo ucfirst($user['role']); ?></td>
                        <td><?php echo htmlspecialchars($user['address']); ?></td>
                        <td>
                            <span style="color: <?php echo ($user['status'] == 'active') ? '#28a745' : '#dc3545'; ?>; font-weight: bold;">
                                <?php echo strtoupper($user['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="admin_edituser.php?id=<?php echo $user['id']; ?>" class="btn">Edit</a>
                            <a href="toggle_status.php?id=<?php echo $user['id']; ?>" class="btn btn-activate">
                                <?php echo (strtolower($user['status']) == 'active') ? 'Deactivate' : 'Activate'; ?>
                            </a>
                            <a href="admin_deleteuser.php?id=<?php echo $user['id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>