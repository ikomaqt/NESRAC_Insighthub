<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // If using Composer

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);
    
    // PHPMailer setup
    $mail = new PHPMailer(true);  // Create a new PHPMailer instance

    try {
        // SMTP server configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // Specify SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'nesrac22@gmail.com'; // Your Gmail address (replace with yours)
        $mail->Password = 'cegq qqrk jjdw xwbs'; // Your Gmail password (replace with yours or use environment variable)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Email headers and content
        $mail->setFrom($email);  // From user email
        $mail->addAddress('nesrac22@gmail.com', 'Feedback');  // Your email address

        $mail->isHTML(false);  // Set email format to plain text
        $mail->Subject = "New Feedback from $email";
        $mail->Body = "Email: $email\n\nMessage:\n$message";

        // Send the email
        $mail->send();
        echo "<script>alert('Thank you for your feedback!'); window.location.href='contact.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('Message could not be sent. Error: {$mail->ErrorInfo}'); window.location.href='contact.php';</script>";
    }
}
