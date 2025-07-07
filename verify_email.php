<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'config.php';

header('Content-Type: application/json'); // Set JSON response header

// Check if required form data is set
if (empty($_POST['email']) || empty($_POST['firstname']) || empty($_POST['lastname']) || empty($_POST['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please complete all required fields.']);
    exit();
}

$email = $_POST['email'];

// Check if the email already exists in the user table or pending_users table
$checkEmailInUsers = $conn->prepare("SELECT * FROM USER_Account WHERE emailAdd = ?");
$checkEmailInUsers->bind_param("s", $email);
$checkEmailInUsers->execute();
$resultUser = $checkEmailInUsers->get_result();

$checkEmailInPendingUsers = $conn->prepare("SELECT * FROM pending_users WHERE email = ?");
$checkEmailInPendingUsers->bind_param("s", $email);
$checkEmailInPendingUsers->execute();
$resultPending = $checkEmailInPendingUsers->get_result();

if ($resultUser->num_rows > 0) {
    // Email already exists in the user table, return error message
    echo json_encode(['status' => 'error', 'message' => 'The email you entered is already registered.']);
    exit();
} elseif ($resultPending->num_rows > 0) {
    // Email exists in the pending_users table, return a different message
    echo json_encode(['status' => 'error', 'message' => 'Your request is pending approval. Please check your email for further instructions.']);
    exit();
}

// Handle the image upload and store it in a temporary directory
$photoTempPath = '';
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $fileName = basename($_FILES['photo']['name']);
    $fileType = pathinfo($fileName, PATHINFO_EXTENSION);
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array(strtolower($fileType), $allowedTypes)) {
        // Define a temporary folder for storing the uploaded file
        $tempDir = "img/temp/";
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true); // Create the directory if it doesn't exist
        }

        // Generate a unique file name to avoid overwriting
        $tempFilePath = $tempDir . uniqid() . "_" . $fileName;

        // Move the file to the temporary directory
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $tempFilePath)) {
            $_SESSION['photoTempPath'] = $tempFilePath; // Save the temporary path in the session
            $_SESSION['fileName'] = $fileName;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error uploading the file.']);
            exit();
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPG, JPEG, PNG, & GIF files are allowed.']);
        exit();
    }
}

// Proceed with sending the verification email if the email doesn't exist in both tables
$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 2; // Enable verbose debug output (set to 0 in production)
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'nesrac22@gmail.com';
    $mail->Password = 'cegq qqrk jjdw xwbs'; // Use your actual app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('nesrac22@gmail.com', 'NESRAC');
    $mail->addAddress($email);
    $mail->addReplyTo('nesrac22@gmail.com', 'NESRAC');

    // Generate and send verification code
    $verificationCode = generateVerificationCode();
    $mail->isHTML(true);
    $mail->Subject = 'Email Verification';
    $mail->Body = 'Your verification code is: <b>' . $verificationCode . '</b>';

    if ($mail->send()) {
        $_SESSION['lastname'] = $_POST['lastname'];
        $_SESSION['firstname'] = $_POST['firstname'];
        $_SESSION['middlename'] = $_POST['middlename'] ?? null;
        $_SESSION['bday'] = $_POST['bday'];
        $_SESSION['gender'] = $_POST['gender'];
        $_SESSION['province'] = 'Nueva Ecija';
        $_SESSION['municipality'] = $_POST['municipality'];
        $_SESSION['barangay'] = $_POST['barangay'];
        $_SESSION['street'] = $_POST['street'];
        $_SESSION['number'] = $_POST['number'];
        $_SESSION['email'] = $_POST['email'];
        $_SESSION['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $_SESSION['verification_code'] = $verificationCode;

        // Return success response
        echo json_encode(['status' => 'success', 'message' => 'Verification email sent.']);
    } else {
        // Return error if email sending failed
        echo json_encode(['status' => 'error', 'message' => 'Error sending verification email.']);
    }
} catch (Exception $e) {
    // Return error if exception occurs
    echo json_encode(['status' => 'error', 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
}

// Function to generate verification code
function generateVerificationCode() {
    return substr(md5(uniqid(rand(), true)), 0, 6);
}

?>