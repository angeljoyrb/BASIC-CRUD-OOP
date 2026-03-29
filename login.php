<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect if already logged in
if (isset($_SESSION['role'])) {
    $dashboard = (strtolower($_SESSION['role']) === 'admin') ? 'admin_dashboard.php' : 'user_dashboard.php';
    header("Location: $dashboard");
    exit();
}

include 'db_connection.php'; // Database connection
include 'functions.php';      // Reusable functions

class UserLogin {
    private $conn;
    private $errors = [];

    public function __construct($conn) {
        $this->conn = $conn;
    }
    // Process login form submission
    public function processRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            // Validate input
            if (empty($email) || empty($password)) {
                $this->errors[] = "All fields are required.";
            } else {
                $this->authenticate($email, $password);
            }
        }
    }
    //Ensure that only active users can log in
    private function authenticate($email, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            if (strtolower($user['status']) === 'inactive') {
                $this->errors[] = "Access denied. Account is inactive.";
            } elseif (password_verify($password, $user['password'])) {
                // Reset login attempts
                $update = $this->conn->prepare("UPDATE users SET login_attempts = 0 WHERE id = ?");
                $update->bind_param("i", $user['id']);
                $update->execute();
                $update->close();

                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'];
                $_SESSION['role'] = $user['role'];

                //Use redirectByRole from functions.php to redirect user to the appropriate dashboard based on their role
                $userModel = new User($this->conn);
                $userModel->redirectByRole($user['role']);
            } else {
                $this->handleFailedLogin($user);
            }
        } else {
            $this->errors[] = "No account found with that email.";
        }
    }
    // Handle failed login attempts and lock account after 3 attempts
    private function handleFailedLogin($user) {
        $max_limit = 3;
        $attempts = $user['login_attempts'] + 1;

        if ($attempts >= $max_limit) {
            $update = $this->conn->prepare("UPDATE users SET login_attempts = ?, status = 'inactive' WHERE id = ?");
            $update->bind_param("ii", $attempts, $user['id']);
            $update->execute();
            $update->close();
            $this->errors[] = "Account deactivated.";
        } else {
            $update = $this->conn->prepare("UPDATE users SET login_attempts = ? WHERE id = ?");
            $update->bind_param("ii", $attempts, $user['id']);
            $update->execute();
            $update->close();
            $remaining = $max_limit - $attempts;
            $this->errors[] = "Invalid password. Attempts left: $remaining";
        }
    }
    //Show errors if there are any
    public function getErrors() {
        return $this->errors;
    }
}

// Instantiate Database and Login
$db = new Database();
$conn = $db->getConnection();
$login = new UserLogin($conn);
$login->processRequest();
$errors = $login->getErrors();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body style="background-color:#f0f2f5; margin:0">
    <div class="login-container" style="display: flex; justify-content: center; align-items: center; min-height: 90vh; width: 100%; font-family: Arial, sans-serif">
        <div class="login-box" style="display: flex; flex-direction: column; background-color: #ffffff; padding: 20px; border-radius: 20px; box-shadow: 0 4px 8px rgba(0, 0, 0, .1), 0 8px 16px rgba(0, 0, 0, .1); width: 400px; border: 1px solid #dddfe2;">
            <h2 class="login-title" style="text-align: center; margin-bottom: 20px;">Login to Your Account</h2>

            <?php foreach ($errors as $error): ?>
                <p style="color:red;"><?php echo $error; ?></p>
            <?php endforeach; ?>

            <form method="post">
                <div style="margin-bottom: 15px;">
                    <input type="email" id="email" name="email" placeholder="Email Address" style="width: 95%; padding: 8px; margin-top: 10px; border-radius: 20px; border: 1px solid #ccc;">
                    <input type="password" id="password" name="password" placeholder="Password" style="width: 95%; padding: 8px; margin-top: 10px; border-radius: 20px; border: 1px solid #ccc;">
                    <button type="submit" style="margin-top: 10px; width: 100%; padding: 10px; background-color: #0088ff; color: white; border: none; border-radius: 20px; cursor: pointer;">Login</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>