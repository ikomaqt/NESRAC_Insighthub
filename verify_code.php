<?php
session_start();
include("config.php");

// Check if verification code and email are stored in session
if (!isset($_SESSION['verification_code']) || !isset($_SESSION['email'])) {
    header('Location: index.php'); // Redirect to the signup page if session data is missing
    exit();
}

// Initialize variables for JavaScript output
$success = false;
$error = '';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and validate the entered verification code
    $enteredCode = $_POST['verification_code'];
    $storedCode = $_SESSION['verification_code'];

    if ($enteredCode === $storedCode) {
        // Verification successful, proceed to save data to the database

        // Prepare and bind parameters
        $email = $_SESSION['email'];
        $checkStmt = $conn->prepare("SELECT * FROM pending_users WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows == 0) {
            // No existing user with the same email, insert new user
            $lastname = $_SESSION['lastname'];
            $firstname = $_SESSION['firstname'];
            $middlename = isset($_SESSION['middlename']) ? $_SESSION['middlename'] : NULL;
            $bday = $_SESSION['bday'];
            $gender = $_SESSION['gender'];
            $municipality = $_SESSION['municipality'];
            $barangay = $_SESSION['barangay'];
            $street = $_SESSION['street'];
            $number = $_SESSION['number'];
            $password = $_SESSION['password']; // Already hashed password stored in session
            $province = 'Nueva Ecija'; // Default province
            $status = 'Non-Member'; // Default status
            $category = 'New User'; // Set category to New User
            
            // Move the file from the temporary folder to the final destination
            $photo = NULL;
            if (isset($_SESSION['photoTempPath'])) {
                $targetDir = "img/validid/";

                // Check if the target directory exists, if not, create it
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true); // Create the directory with write permissions
                }

                // Generate a unique file name if a file with the same name already exists
                $targetFilePath = $targetDir . $_SESSION['fileName'];
                $fileExtension = pathinfo($targetFilePath, PATHINFO_EXTENSION);
                $fileNameWithoutExt = pathinfo($targetFilePath, PATHINFO_FILENAME);

                // Check if the file already exists
                $counter = 1;
                while (file_exists($targetFilePath)) {
                    $targetFilePath = $targetDir . $fileNameWithoutExt . time() . $counter . "." . $fileExtension;
                    $counter++;
                }

                // Move the file from the temp directory to the final directory
                if (file_exists($_SESSION['photoTempPath'])) {
                    if (rename($_SESSION['photoTempPath'], $targetFilePath)) {
                        $photo = $targetFilePath; // Save the file path to the photo variable
                    } else {
                        $error = 'Error moving the uploaded file.';
                    }
                } else {
                    $error = 'Temporary file not found: ' . $_SESSION['photoTempPath'];
                }
            }

            // Insert data into the pending_users table with 'New User' category
            $stmt = $conn->prepare(
                "INSERT INTO pending_users (lastname, firstname, middlename, bday, gender, number, email, password, status, photo, province, municipality, barangay, street, category) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "sssssssssssssss", // 15 placeholders for the 15 fields
                $lastname, 
                $firstname, 
                $middlename, 
                $bday, 
                $gender, 
                $number, 
                $email, 
                $password, 
                $status, 
                $photo, 
                $province, 
                $municipality, 
                $barangay, 
                $street, 
                $category
            );

            if ($stmt->execute()) {
                // Clear session data after successful insert
                session_unset();
                session_destroy();

                // Set success flag for SweetAlert
                $success = true;

                // Close statement and connection
                $stmt->close();
                $conn->close();
            } else {
                $error = 'Error: ' . $stmt->error;
            }
        } else {
            $error = 'User already exists with this email.';
        }
    } else {
        // Verification code did not match
        $error = 'Invalid verification code. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <link rel="stylesheet" href="style/verify_code.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container">
        <div class=" box">
            <div class="form verify">
                <h3>Email Verification</h3>
                <?php if (!empty($error)): ?>
                    <script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Verification Failed',
                            text: '<?php echo $error; ?>',
                        });
                    </script>
                <?php endif; ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="type">
                        <label for="verification_code">Enter Verification Code:</label>
                        <input type="text" name="verification_code" placeholder="Verification Code" id="verification_code" required>
                    </div>
                    <button type="submit" class="btn bkg">Verify</button>
                </form>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Verification Successful',
                text: 'Your email has been verified. Redirecting...',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'index.php';
            });
        </script>
    <?php endif; ?>
</body>
</html>