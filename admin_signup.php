<?php
session_start();
include("admin.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if required fields are set
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $lastName = isset($_POST['lastName']) ? $_POST['lastName'] : '';
    $middleName = isset($_POST['middleName']) ? $_POST['middleName'] : ''; // Middle name is optional
    $firstName = isset($_POST['firstName']) ? $_POST['firstName'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : '';

    // Hash the password before storing it
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Connect to the database
    $conn = new mysqli("localhost", "root", "", "nesrac");
    if ($conn->connect_error) {
        die('Connection Failed: ' . $conn->connect_error);
    }

    // Check if the username already exists in admin_account table
    $stmt = $conn->prepare("SELECT username FROM admin_account WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt_result = $stmt->get_result();

    if ($stmt_result->num_rows > 0) {
        // Username already exists
        $error = "Username already taken, please choose another one.";
    } else {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Check if the role exists in the role table
            $stmt = $conn->prepare("SELECT role_id FROM role WHERE role_name = ?");
            $stmt->bind_param("s", $role);
            $stmt->execute();
            $stmt_result = $stmt->get_result();

            if ($stmt_result->num_rows == 0) {
                // If the role doesn't exist, insert it into the role table
                $stmt = $conn->prepare("INSERT INTO role (role_name) VALUES (?)");
                $stmt->bind_param("s", $role);
                $stmt->execute();
                
                // Get the inserted role ID
                $role_id = $stmt->insert_id;
            } else {
                // Get the existing role ID
                $row = $stmt_result->fetch_assoc();
                $role_id = $row['role_id'];
            }

            // Step 1: Insert into the admin table
            $stmt = $conn->prepare("INSERT INTO admin (lastName, middleName, firstName, created_at, updated_at, role_id) 
                                    VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, ?)");
            $stmt->bind_param("sssi", $lastName, $middleName, $firstName, $role_id);
            $stmt->execute();
            
            // Get the inserted admin's ID
            $admin_id = $conn->insert_id;

            // Step 2: Insert into the admin_account table
            $stmt = $conn->prepare("INSERT INTO admin_account (username, password, admin_id) 
                                    VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $username, $hashed_password, $admin_id);
            $stmt->execute();

            // Commit transaction
            $conn->commit();

            $success = "Sign-up successful! You can now log in.";

        } catch (Exception $e) {
            // Rollback transaction if any query fails
            $conn->rollback();
            $error = "There was an error signing up. Please try again later.";
        }
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/admin_login.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Admin/Courier Sign Up</title>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h2>Add New Admin</h2>

            <?php if (isset($error)): ?>
                <p class="auth-error"><?php echo $error; ?></p>
            <?php endif; ?>
            
            <form action="admin_signup.php" method="post">
                <div class="auth-input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="auth-input-group">
                    <label for="lastName">Last Name</label>
                    <input type="text" id="lastName" name="lastName" required>
                </div>
                <div class="auth-input-group">
                    <label for="middleName">Middle Name</label>
                    <input type="text" id="middleName" name="middleName">
                </div>
                <div class="auth-input-group">
                    <label for="firstName">First Name</label>
                    <input type="text" id="firstName" name="firstName" required>
                </div>
                <div class="auth-input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="auth-input-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="Admin">Admin</option>
                        <option value="SuperAdmin">SuperAdmin</option>
                        <option value="Courier">Courier</option>
                    </select>
                </div>
                <button type="submit" class="auth-btn">Create</button>
            </form>
        </div>
    </div>

    <?php if (isset($success)): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '<?php echo $success; ?>',
            confirmButtonText: 'OK'
        }).then(() => {
            window.location.href = 'admin_signup.php'; // Redirect to login or any other page
        });
    </script>
    <?php endif; ?>
</body>

</html>