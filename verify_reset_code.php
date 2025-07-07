<?php
session_start();
if (!isset($_SESSION['verification_code']) || !isset($_SESSION['reset_email'])) {
    header('Location: forgot_password.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredCode = $_POST['verification_code'];
    $storedCode = $_SESSION['verification_code'];

    if ($enteredCode === $storedCode) {
        header('Location: reset_password.php');
        exit();
    } else {
        $error = 'Invalid verification code. Please try again.';
    }
}
?>
<!-- HTML Form for code verification -->


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code</title>
    <link rel="stylesheet" href="style/verify_code.css">
</head>
<body>
    <div class="container">
        <div class="box">
            <h3>Verify Code</h3>
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <label for="verification_code">Enter Verification Code:</label>
                <input type="text" name="verification_code" id="verification_code" required>
                <button type="submit" class="btn bkg">Verify</button>
            </form>
        </div>
    </div>
</body>
</html>
