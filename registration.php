<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include 'db_connection.php';

class UserRegistration {
    private $conn;
    private $errors = [];
    private $data = [];

    public function __construct($conn) {
        $this->conn = $conn;
        $this->initializeData();
        $this->handleSessionRedirect();
    }
    // Redirect if already logged in
    private function handleSessionRedirect() {
        if (isset($_SESSION['role'])) {
            $dashboard = (strtolower($_SESSION['role']) === 'admin') ? 'admin_dashboard.php' : 'user_dashboard.php';
            header("Location: $dashboard");
            exit();
        }
    }
    // Initialize form data
    private function initializeData() {
        $this->data = [
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'password' => '',
            'confirm_password' => '',
            'gender' => '',
            'role' => 'user',
            'address' => ''
        ];
    }
    //Add new user to database after validating input and checking for duplicate email
    public function processRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->collectInput();
            $this->validateInput();
            if (empty($this->errors)) {
                $this->checkEmailExists();
            }
            if (empty($this->errors)) {
                $this->insertUser();
            }
        } else {
            // Clear form data on GET (refresh)
            $this->initializeData();
        }
    }
    // Collect input data from POST request
    private function collectInput() {
        foreach ($this->data as $key => $value) {
            $this->data[$key] = $_POST[$key] ?? '';
        }
    }
    //Validate user input for registration form
    private function validateInput() {
        extract($this->data);

        if (empty($first_name) || empty($last_name) || empty($gender) || empty($role) ||
            empty($email) || empty($address) || empty($password) || empty($confirm_password)) {
            $this->errors[] = "All fields are required.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Email format is invalid.";
        }
        if (!empty($password) && strlen($password) < 8) {
            $this->errors[] = "Password must be at least 8 characters.";
        }
        if ($password !== $confirm_password) {
            $this->errors[] = "Passwords do not match.";
        }
    }
    //To prevent duplicate email registrations
    private function checkEmailExists() {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $this->data['email']);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $this->errors[] = "Email is already registered.";
        }
        $stmt->close();
    }
    // Insert new user into database
    private function insertUser() {
        $hashed_password = password_hash($this->data['password'], PASSWORD_DEFAULT);
        $status = 'active';

        $sql = "INSERT INTO users (first_name, last_name, email, password, gender, role, address, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);

        $stmt->bind_param('ssssssss',
            $this->data['first_name'],
            $this->data['last_name'],
            $this->data['email'],
            $hashed_password,
            $this->data['gender'],
            $this->data['role'],
            $this->data['address'],
            $status
        );

        if ($stmt->execute()) {
            // Clear data before redirect
            $this->initializeData();
            header("Location: login.php");
            exit();
        } else {
            $this->errors[] = "Database Error: " . $this->conn->error;
        }
        $stmt->close();
    }
    //Check errors if there are any
    public function getErrors() {
        return $this->errors;
    }
    //Show previously entered data in the form fields after validation errors
    public function getData($key) {
        return htmlspecialchars($this->data[$key] ?? '');
    }
}

$db = new Database();
$conn = $db->getConnection();
$registration = new UserRegistration($conn);
$registration->processRequest();
$errors = $registration->getErrors();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row w-100 shadow-lg rounded overflow-hidden bg-white">
            <div class="col-md-5 bg-primary text-white d-flex flex-column justify-content-center align-items-center p-5 text-center">
                <h1 class="fw-bold">Already have an account?</h1>
                <a href="login.php" class="btn btn-outline-light rounded-pill px-5 py-2 fw-bold fs-5" style="background-color: white; color: black">Login</a>
            </div>
            <div class="col-md-7 p-5">
                <h2>Register Your Account</h2>
                <?php if (!empty($errors)): ?>
                    <div style="color: red; margin-bottom: 15px;">
                        <?php foreach ($errors as $error): ?>
                            <p style="margin: 0;">• <?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form method="post">
                    <div style="display:flex;gap:10px; margin-bottom: 10px">
                        <div style="flex:1">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="<?php echo $registration->getData('first_name'); ?>" style="width: 100%; padding: 12px; margin-bottom: 10px; border: 1px solid #dddfe2; border-radius: 6px; box-sizing: border-box;">
                        </div>
                        <div style="flex:1">
                            <label>Gender</label>
                            <select name="gender" style="width: 100%; padding: 12px; margin-bottom: 10px; border: 1px solid #dddfe2; border-radius: 6px; box-sizing: border-box;">
                                <option value="" <?php echo empty($registration->getData('gender')) ? 'selected' : ''; ?>></option>
                                <option value="Male" <?php echo ($registration->getData('gender') === 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($registration->getData('gender') === 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($registration->getData('gender') === 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    <div style="display:flex;gap:10px; margin-bottom: 10px">
                        <div style="flex:1">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="<?php echo $registration->getData('last_name'); ?>" style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #dddfe2; border-radius: 6px; box-sizing: border-box;">
                        </div>
                        <div style="flex:1">
                            <label>Role</label>
                            <select name="role" style="width: 100%; padding: 12px; margin-bottom: 10px; border: 1px solid #dddfe2; border-radius: 6px; box-sizing: border-box;">
                                <option value="" <?php echo empty($registration->getData('role')) ? 'selected' : ''; ?>></option>
                                <option value="user" <?php echo ($registration->getData('role') === 'user') ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo ($registration->getData('role') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                    </div>
                    <div style="display:flex;gap:10px; margin-bottom: 10px">
                        <div style="flex:1">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo $registration->getData('email'); ?>" style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #dddfe2; border-radius: 6px; box-sizing: border-box;">
                            <div style="flex:1">
                            <label>Address</label>
                            <input type="text" name="address" value="<?php echo $registration->getData('address'); ?>" style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #dddfe2; border-radius: 6px; box-sizing: border-box;">
                 </div>
             </div>
                     </div>
                            <label>Password</label>
                            <input type="password" name="password" style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #dddfe2; border-radius: 6px; box-sizing: border-box;">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #dddfe2; border-radius: 6px; box-sizing: border-box;">
                            <button type="submit" style="width: 100%; background-color: #0088ff; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; font-size: 20px; cursor: pointer;">
                        Register
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>