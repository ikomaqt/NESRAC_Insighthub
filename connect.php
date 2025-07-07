<?php
require 'vendor/autoload.php'; // Include PHPMailer autoload file

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $lastname = $_POST['lastname'];
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $bday = $_POST['bday'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];
    $number = $_POST['number'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Generate a verification token
    $token = bin2hex(random_bytes(16));

    // Connect to database
    $conn = mysqli_connect("localhost", "root", "", "nesrac");
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Connection Failed: ' . $conn->connect_error]);
        exit;
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Email already registered.']);
        exit;
    }

    // Insert user data into database
    $stmt = $conn->prepare("INSERT INTO user (lastname, firstname, middlename, bday, gender, address, number, email, password, token, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
    $stmt->bind_param("ssssssssss", $lastname, $firstname, $middlename, $bday, $gender, $address, $number, $email, $hashed_password, $token);

    if ($stmt->execute()) {
        // Send verification email
        $mail = new PHPMailer(true);
        try {
            // Server settings for Gmail SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // SMTP server address
            $mail->SMTPAuth = true;
            $mail->Username = 'nesrac22@gmail.com'; // Your Gmail address
            $mail->Password = 'dim83qtie'; // Your Gmail password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
            $mail->Port = 587; // TCP port to connect to

            // Set email sender and recipient
            $mail->setFrom('no-reply@gmail.com', 'Your App Name');
            $mail->addAddress($email);

            // Email content
            $mail->isHTML(true); // Set email format to HTML
            $mail->Subject = 'Email Verification';
            $verification_code = bin2hex(random_bytes(4)); // Generate a random verification code
            $_SESSION['verification_code'] = $verification_code; // Store verification code in session
            $mail->Body = 'Your verification code is: ' . $verification_code; // Modify email body as needed

            // Send email
            $mail->send();

            // Redirect to verify_email.php
            header("Location: verify_email.php?email=" . urlencode($email));
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Registration Successful, but failed to send verification email.']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $stmt->error]);
    }

    // Close statements and database connection
    $stmt->close();
    $conn->close();
}
?>
