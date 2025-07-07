<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'config.php';

$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Check if email exists
    $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
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
            $mail->Username = 'your_email@gmail.com';
            $mail->Password = 'your_password'; // Use an App Password if 2FA is enabled
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('your_email@gmail.com', 'Your Name or Company');
            $mail->addAddress($email);
            $mail->addReplyTo('your_email@gmail.com', 'Your Name or Company');

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Verification';
            $mail->Body    = 'Your password reset verification code is: <strong>' . $verificationCode . '</strong>';

            $mail->send();
            $response['success'] = true;
            $response['message'] = 'Verification code sent.';
        } catch (Exception $e) {
            $response['message'] = 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
        }
    } else {
        $response['message'] = 'Email does not exist.';
    }

    echo json_encode($response);
}
