<?php
// Include your database connection file
include 'config.php';
include 'admin.php';

// Check if an accept request was made
if (isset($_POST['accept'])) {
    $userid = $_POST['userid'];

    // Fetch the data of the selected user from pending_old_members
    $query = "SELECT * FROM pending_old_members WHERE userid = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Check if the required user data is present
    if ($user && !empty($user['lastname']) && !empty($user['firstname']) && !empty($user['email']) && !empty($user['password'])) {
        // Check if the email already exists in the user table
        $email_check_query = "SELECT COUNT(*) as count FROM user WHERE email = ?";
        $email_check_stmt = $conn->prepare($email_check_query);
        $email_check_stmt->bind_param("s", $user['email']);
        $email_check_stmt->execute();
        $email_check_result = $email_check_stmt->get_result();
        $email_check_row = $email_check_result->fetch_assoc();

        if ($email_check_row['count'] > 0) {
            echo "Error: The email address '{$user['email']}' already exists in the user table.";
        } else {
            // Check if the same userid exists in the user table
            $check_query = "SELECT COUNT(*) as count FROM user WHERE userid = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("i", $userid);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $check_row = $check_result->fetch_assoc();

            if ($check_row['count'] > 0) {
                // If userid exists, let the database handle the auto-increment
                $insert_query = "INSERT INTO user (lastname, firstname, middlename, bday, gender, address, number, email, password, is_verified, status)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'Member')";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("ssssssss", $user['lastname'], $user['firstname'], $user['middlename'], $user['bday'], $user['gender'], $user['address'], $user['number'], $user['email'], $user['password']);
            } else {
                // If userid does not exist, insert with the original userid
                $insert_query = "INSERT INTO user (userid, lastname, firstname, middlename, bday, gender, address, number, email, password, is_verified, status)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'Member')";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("isssssssss", $userid, $user['lastname'], $user['firstname'], $user['middlename'], $user['bday'], $user['gender'], $user['address'], $user['number'], $user['email'], $user['password']);
            }

            if ($insert_stmt->execute()) {
                // Delete the user from pending_old_members after successful insertion
                $delete_query = "DELETE FROM pending_old_members WHERE userid = ?";
                $delete_stmt = $conn->prepare($delete_query);
                $delete_stmt->bind_param("i", $userid);
                $delete_stmt->execute();

                echo "User accepted successfully!";
            } else {
                echo "Error: " . $insert_stmt->error;
            }

            $check_stmt->close();
            $insert_stmt->close();
            $delete_stmt->close();
        }

        $email_check_stmt->close();
    } // No else condition to prevent error message from showing

    $stmt->close();
}

// Check if a remove request was made
if (isset($_POST['remove'])) {
    $userid = $_POST['userid'];

    // Delete the user from pending_old_members
    $delete_query = "DELETE FROM pending_old_members WHERE userid = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $userid);

    if ($delete_stmt->execute()) {
        echo "User removed successfully!";
    } else {
        echo "Error: " . $delete_stmt->error;
    }

    $delete_stmt->close();
}

// Fetch all users from pending_old_members table
$query = "SELECT * FROM pending_old_members";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Accept Users</title>
    <link rel="stylesheet" href="style/admin_accept.css">
</head>
<body>
    <div class="container">
        <h2>Pending Users</h2>
        <?php if ($result->num_rows > 0) { ?>
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Birthday</th>
                        <th>Gender</th>
                        <th>Address</th>
                        <th>Phone Number</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['userid']; ?></td>
                        <td><?php echo $row['lastname']; ?></td>
                        <td><?php echo $row['firstname']; ?></td>
                        <td><?php echo $row['middlename']; ?></td>
                        <td><?php echo $row['bday']; ?></td>
                        <td><?php echo $row['gender']; ?></td>
                        <td><?php echo $row['address']; ?></td>
                        <td><?php echo $row['number']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td>
                            <form action="admin_accept.php" method="POST" style="display: inline;">
                                <input type="hidden" name="userid" value="<?php echo $row['userid']; ?>">
                                <input type="submit" name="accept" value="Accept" class="accept-btn">
                            </form>
                            <form action="admin_accept.php" method="POST" style="display: inline;">
                                <input type="hidden" name="userid" value="<?php echo $row['userid']; ?>">
                                <input type="submit" name="remove" value="Remove" class="remove-btn">
                            </form>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p class="no-users">No users to accept.</p>
        <?php } ?>
    </div>
</body>
</html>
