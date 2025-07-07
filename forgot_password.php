<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'config.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Check if email exists in user_account table
    $stmt = $conn->prepare("SELECT * FROM user_account WHERE emailAdd = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Email exists, generate verification code
        $verificationCode = substr(md5(rand()), 0, 6);
        $_SESSION['reset_email'] = $email;
        $_SESSION['verification_code'] = $verificationCode;

        // Initialize PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'nesrac22@gmail.com';
            $mail->Password = 'cegq qqrk jjdw xwbs';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('nesrac22@gmail.com', 'NESRAC');
            $mail->addAddress($email);
            $mail->addReplyTo('nesrac22@gmail.com', 'NESRAC');

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Verification';
            $mail->Body    = 'Your password reset verification code is: ' . $verificationCode;

            $mail->send();

            // Redirect to verification code input page
            header('Location: verify_reset_code.php');
            exit();
        } catch (Exception $e) {
            echo 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
        }
    } else {
        echo 'Email does not exist.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
     <link rel="stylesheet" href="style/verify_code.css">
</head>
<body>
    <div class="container">
        <div class="box">
            <div class="form">
                <h3>Enter your email:</h3>
                <?php if (isset($error_message)) : ?>
                    <p class="error"><?= $error_message; ?></p>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="type">
                        <input type="text" id="email" name="email" required>
                    </div>
                    <button type="submit" class="btn">Send Verification Code</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>