<?php
session_start();
require 'config.php'; // Include your database connection file

function loginAdminOrCourier($conn, $username, $password) {
    // Prepare the SQL query to check if the username belongs to an admin or courier from the admin_account table
    $stmt = $conn->prepare("
        SELECT aa.*, a.role_id, r.role_name 
        FROM admin_account aa
        JOIN admin a ON aa.admin_id = a.admin_id
        JOIN role r ON a.role_id = r.role_id
        WHERE aa.username = ?
    ");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt_result = $stmt->get_result();

    if ($stmt_result->num_rows > 0) {
        $data = $stmt_result->fetch_assoc();

        // Verify password
        if (password_verify($password, $data['password'])) {
            // Store necessary data in session
            $_SESSION['admin_id'] = $data['admin_id'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['role'] = $data['role_name'];

            // Set a cookie to keep the admin/courier logged in for 1 hour
            setcookie('adminLoggedIn', 'true', time() + 3600, '/');

            // Redirect based on role
            if ($data['role_name'] === 'Admin' || $data['role_name'] === 'SuperAdmin') {
                header("Location: dashboard.php"); // Admin dashboard
                exit();
            } elseif ($data['role_name'] === 'Courier') {
                header("Location: courier_dashboard.php"); // Courier dashboard
                exit();
            } else {
                // If role is neither Admin nor Courier, throw an error
                echo "Unknown role: " . $data['role_name']; // For debugging purposes
            }
        } else {
            // Invalid password
            return array("status" => "error", "message" => "Invalid Username or Password");
        }
    } else {
        // No admin or courier found with that username
        return array("status" => "error", "message" => "Invalid Username or Password");
    }

    // Close the statement
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Call the dedicated admin/courier login function
    $response = loginAdminOrCourier($conn, $username, $password);

    // Handle the response (e.g., show error message)
    if ($response['status'] === 'error') {
        $error = $response['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin/Courier Login</title>
    <link rel="stylesheet" href="style/admin_login.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h2>Login</h2>
            <?php if (isset($error)): ?>
                <p class="auth-error"><?php echo $error; ?></p>
            <?php endif; ?>
            <form action="admin_login.php" method="post">
                <div class="auth-input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="auth-input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="auth-btn">Login</button>
            </form>
        </div>
    </div>
</body>
</html>