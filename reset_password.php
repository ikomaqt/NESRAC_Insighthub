<?php
session_start();
require 'config.php';

if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot_password.php');
    exit();
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($password === $confirmPassword) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $email = $_SESSION['reset_email'];

        // Update password in the user_account table
        $stmt = $conn->prepare("UPDATE user_account SET password = ? WHERE emailAdd = ?");
        $stmt->bind_param("ss", $hashedPassword, $email);

        if ($stmt->execute()) {
            // Clear session data
            unset($_SESSION['reset_email']);
            unset($_SESSION['verification_code']);

            // Success message
            $success = 'Password reset successful. You can now log in with your new password.';
        } else {
            $error = 'Error: ' . $stmt->error;
        }
    } else {
        // Passwords do not match
        $error = 'Passwords do not match. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="style/reset_password.css">
</head>
<body>
    <div class="container">
        <div class="box">
            <div class="form reset-password">
                <h3>Reset Password</h3>
                <?php if (!empty($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                    <a href="reset_password.php" class="btn bkg">Try Again</a>
                <?php elseif (!empty($success)): ?>
                    <div class="success"><?php echo $success; ?></div>
                    <a href="index.php" class="btn bkg">Go to Home</a>
                <?php else: ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="type">
                            <label for="password">New Password:</label>
                            <input type="password" name="password" placeholder="Enter New Password" id="password" required>
                        </div>
                        <div class="type">
                            <label for="confirm_password">Confirm Password:</label>
                            <input type="password" name="confirm_password" placeholder="Confirm New Password" id="confirm_password" required>
                        </div>
                        <button type="submit" class="btn bkg">Reset Password</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
