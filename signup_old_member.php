<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/signup_old_member.css">
    <title>Signup Old Member</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <form action="signup_old_member.php" method="POST">
        <h2>Signup Form for Old Members</h2>
        
        <label for="lastname">Last Name:</label>
        <input type="text" id="lastname" name="lastname" required><br><br>

        <label for="firstname">First Name:</label>
        <input type="text" id="firstname" name="firstname" required><br><br>

        <label for="middlename">Middle Name:</label>
        <input type="text" id="middlename" name="middlename"><br><br>

        <label for="bday">Birthday:</label>
        <input type="date" id="bday" name="bday" required><br><br>

        <label for="gender">Gender:</label>
        <select id="gender" name="gender" required>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </select><br><br>

        <label for="address">Address:</label>
        <input type="text" id="address" name="address" required><br><br>

        <label for="number">Contact Number:</label>
        <input type="text" id="number" name="number" required><br><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br><br>

        <input type="submit" value="Signup">
    </form>

    <?php
    session_start();

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require 'vendor/autoload.php'; // Adjust the path as per your setup
    require 'config.php'; // Include your database connection file

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Sanitize and validate input data
        $lastname = $_POST['lastname'];
        $firstname = $_POST['firstname'];
        $middlename = $_POST['middlename'] ?? null;
        $bday = $_POST['bday'];
        $gender = $_POST['gender'];
        $address = $_POST['address'];
        $number = $_POST['number'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Check if the email already exists in the user or pending_member tables
        if (emailExists($email, $conn, 'user')) {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Email Already Exists',
                    text: 'Please use a different email address.',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'signup_old_member.php';
                    }
                });
            </script>";
            exit();
        } elseif (emailExists($email, $conn, 'pending_old_members')) {
            echo "<script>
                Swal.fire({
                    icon: 'warning',
                    title: 'Email Already in Request',
                    text: 'The email is already in request, Wait for the admin to check your account.',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'signup_old_member.php';
                    }
                });
            </script>";
            exit();
        }

        // Generate a verification code
        $verification_code = rand(100000, 999999);
        $_SESSION['verification_code'] = $verification_code;
        $_SESSION['email'] = $email;
        $_SESSION['lastname'] = $lastname;
        $_SESSION['firstname'] = $firstname;
        $_SESSION['middlename'] = $middlename;
        $_SESSION['bday'] = $bday;
        $_SESSION['gender'] = $gender;
        $_SESSION['address'] = $address;
        $_SESSION['number'] = $number;
        $_SESSION['password'] = $password;

        // Send verification code via email
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
            $mail->isHTML(true);
            $mail->Subject = 'Your Verification Code';
            $mail->Body    = 'Your verification code is: ' . $verification_code;

            $mail->send();
            header('Location: verify_code_oldmember.php');
            exit();
        } catch (Exception $e) {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Mailer Error',
                    text: 'Mailer Error: " . $mail->ErrorInfo . "',
                    confirmButtonText: 'OK'
                });
            </script>";
        }
    }

    // Function to check if email exists in the specified table
    function emailExists($email, $conn, $table) {
        $stmt = $conn->prepare("SELECT * FROM $table WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
    ?>
</body>
</html>
