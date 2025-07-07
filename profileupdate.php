<?php
// Include database connection
session_start();
include("config.php");
include("navbar.php");

// Retrieve user data from the database
$userid = $_SESSION['userid'];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update profile data
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $middlename = $_POST['middlename'];
    $bday = $_POST['bday'];
    $gender = $_POST['gender'];
    $province = $_POST['province'];
    $municipality = $_POST['municipality'];
    $barangay = $_POST['barangay'];
    $street = $_POST['street'];
    $number = $_POST['number'];
    $email = $_POST['email'];

    // Prepare SQL update query
    $query = "UPDATE user SET firstname = ?, lastname = ?, middlename = ?, bday = ?, gender = ?, province = ?, municipality = ?, barangay = ?, street = ?, number = ?, email = ? WHERE userid = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssssssssi", $firstname, $lastname, $middlename, $bday, $gender, $province, $municipality, $barangay, $street, $number, $email, $userid);

    if ($stmt->execute()) {
        $message = "Profile updated successfully!";
    } else {
        $message = "Error updating profile.";
    }
    $stmt->close();
}

// Retrieve current user data to pre-fill the form
$query = "SELECT firstname, lastname, middlename, bday, gender, province, municipality, barangay, street, number, email FROM user WHERE userid = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userid);
$stmt->execute();
$stmt->bind_result($firstname, $lastname, $middlename, $bday, $gender, $province, $municipality, $barangay, $street, $number, $email);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="stylesheet" href="style/updateprofile.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile</title>
</head>
<body>
    <div class="container">
        <h2>Update Profile</h2>
        <?php if (isset($message)): ?>
            <p class="message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <form action="profileupdate.php" method="post">
            <div class="form-group">
                <label for="firstname">Firstname:</label>
                <input type="text" name="firstname" id="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required>
            </div>
            <div class="form-group">
                <label for="lastname">Lastname:</label>
                <input type="text" name="lastname" id="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required>
            </div>
            <div class="form-group">
                <label for="middlename">Middlename:</label>
                <input type="text" name="middlename" id="middlename" value="<?php echo htmlspecialchars($middlename); ?>" required>
            </div>
            <div class="form-group">
                <label for="bday">Birthday:</label>
                <input type="date" name="bday" id="bday" value="<?php echo htmlspecialchars($bday); ?>" required>
            </div>
            <div class="form-group">
                <label for="gender">Gender:</label>
                <select name="gender" id="gender" required>
                    <option value="male" <?php if ($gender == 'male') echo 'selected'; ?>>Male</option>
                    <option value="female" <?php if ($gender == 'female') echo 'selected'; ?>>Female</option>
                    <option value="other" <?php if ($gender == 'other') echo 'selected'; ?>>Other</option>
                </select>
            </div>

            <!-- Address Fields Split into Province, Municipality, Barangay, Street -->
            <div class="form-group">
                <label for="province">Province:</label>
                <input type="text" name="province" id="province" value="<?php echo htmlspecialchars($province); ?>" required>
            </div>
            <div class="form-group">
                <label for="municipality">Municipality:</label>
                <input type="text" name="municipality" id="municipality" value="<?php echo htmlspecialchars($municipality); ?>" required>
            </div>
            <div class="form-group">
                <label for="barangay">Barangay:</label>
                <input type="text" name="barangay" id="barangay" value="<?php echo htmlspecialchars($barangay); ?>" required>
            </div>
            <div class="form-group">
                <label for="street">Street:</label>
                <input type="text" name="street" id="street" value="<?php echo htmlspecialchars($street); ?>" required>
            </div>

            <div class="form-group">
                <label for="number">Phone Number:</label>
                <input type="text" name="number" id="number" value="<?php echo htmlspecialchars($number); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <button type="submit" class="btn">Save Changes</button>
        </form>
        <a href="profile.php" class="btn">Back to Profile</a>
    </div>
</body>
</html>
