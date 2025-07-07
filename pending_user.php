<?php
session_start();
include("config.php");
include("admin.php");

// Fetch pending users from the database
$pendingUsers = [];
$sql = "SELECT * FROM pending_users";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pendingUsers[] = $row;
    }
}

// Accept a user and move them to the users table
if (isset($_POST['action']) && $_POST['action'] === 'accept') {
    $userId = $_POST['user_id'];
    
    // Fetch the user from the pending_users table
    $stmt = $conn->prepare("SELECT * FROM pending_users WHERE pendingUserID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        // Insert the user into the `USER` table, including location fields
        $stmt = $conn->prepare("INSERT INTO USER (lastName, firstName, middleName, bday, gender, contactNumber, municipality, barangay, street) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", 
            $user['lastname'], 
            $user['firstname'], 
            $user['middlename'], 
            $user['bday'], 
            $user['gender'], 
            $user['number'], 
            $user['municipality'], 
            $user['barangay'], 
            $user['street']);
        $stmt->execute();
        $newUserID = $stmt->insert_id; // Get the newly inserted userID

        // Insert into USER_Account table
        $stmt = $conn->prepare("INSERT INTO USER_Account (emailAdd, password, userID) 
                                VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", 
            $user['email'], 
            $user['password'], 
            $newUserID);
        $stmt->execute();

        // Insert into USER_Membership table
        $stmt = $conn->prepare("INSERT INTO USER_Membership (status, userID) 
                                VALUES (?, ?)");
        $stmt->bind_param("si", 
            $user['status'], 
            $newUserID);
        $stmt->execute();

        // Set the default profile photo and last_survey value
        $defaultProfilePhoto = 'img/default_profile.jpg'; // Adjust the path as needed
        $validid = isset($user['validid']) && !empty($user['validid']) ? $user['validid'] : 'img/default_validid.jpg'; // Default valid ID
        $defaultLastSurvey = "no survey"; // Placeholder for new users

        // Insert into USER_Assets table, including profile_photo and last_survey
        $stmt = $conn->prepare("INSERT INTO USER_Assets (validid, profile_photo, last_survey, userID) 
                                VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", 
            $validid, 
            $defaultProfilePhoto, 
            $defaultLastSurvey, 
            $newUserID);
        $stmt->execute();

        // Delete the user from the pending_users table
        $stmt = $conn->prepare("DELETE FROM pending_users WHERE pendingUserID = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }

    header('Location: pending_user.php');
    exit();
}

// Reject a user and remove them from the pending_users table
if (isset($_POST['action']) && $_POST['action'] === 'reject') {
    $userId = $_POST['user_id'];

    $stmt = $conn->prepare("DELETE FROM pending_users WHERE pendingUserID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    header('Location: pending_user.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Users</title>
    <link rel="stylesheet" href="style/pending_users.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="container">
    <h1>Pending Users</h1>

    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($pendingUsers)): ?>
                <?php foreach ($pendingUsers as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['pendingUserID'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($user['firstname'], ENT_QUOTES, 'UTF-8') . " " . htmlspecialchars($user['lastname'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($user['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <!-- View button with SweetAlert -->
                            <button class="view-info-btn" onclick="viewUserDetails(
                                '<?= htmlspecialchars($user['pendingUserID'], ENT_QUOTES, 'UTF-8'); ?>',
                                '<?= htmlspecialchars($user['firstname'], ENT_QUOTES, 'UTF-8'); ?>', 
                                '<?= htmlspecialchars($user['middlename'], ENT_QUOTES, 'UTF-8'); ?>', 
                                '<?= htmlspecialchars($user['lastname'], ENT_QUOTES, 'UTF-8'); ?>', 
                                '<?= htmlspecialchars($user['bday'], ENT_QUOTES, 'UTF-8'); ?>', 
                                '<?= htmlspecialchars($user['gender'], ENT_QUOTES, 'UTF-8'); ?>', 
                                '<?= htmlspecialchars($user['number'], ENT_QUOTES, 'UTF-8'); ?>', 
                                '<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>', 
                                '<?= htmlspecialchars($user['province'], ENT_QUOTES, 'UTF-8'); ?>', 
                                '<?= htmlspecialchars($user['municipality'], ENT_QUOTES, 'UTF-8'); ?>', 
                                '<?= htmlspecialchars($user['barangay'], ENT_QUOTES, 'UTF-8'); ?>', 
                                '<?= htmlspecialchars($user['street'], ENT_QUOTES, 'UTF-8'); ?>', 
                                '<?= htmlspecialchars($user['photo'], ENT_QUOTES, 'UTF-8'); ?>'
                            )">View</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5">No pending users found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    // SweetAlert to view all user details with Accept and Reject buttons, and no OK button
    function viewUserDetails(pendingUserID, firstname, middlename, lastname, bday, gender, number, email, province, municipality, barangay, street, validid) {
        Swal.fire({
            title: 'User Details',
            html: `
                <div style="max-height: 220px; overflow-y: auto; padding-right: 10px;">
                    <table style="width: 100%; text-align: left; border-collapse: collapse;">
                        <tr>
                            <td><strong>First Name:</strong></td>
                            <td>${firstname}</td>
                        </tr>
                        <tr>
                            <td><strong>Middle Name:</strong></td>
                            <td>${middlename}</td>
                        </tr>
                        <tr>
                            <td><strong>Last Name:</strong></td>
                            <td>${lastname}</td>
                        </tr>
                        <tr>
                            <td><strong>Birthday:</strong></td>
                            <td>${bday}</td>
                        </tr>
                        <tr>
                            <td><strong>Gender:</strong></td>
                            <td>${gender}</td>
                        </tr>
                        <tr>
                            <td><strong>Contact Number:</strong></td>
                            <td>${number}</td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td>${email}</td>
                        </tr>
                        <tr>
                            <td><strong>Province:</strong></td>
                            <td>${province}</td>
                        </tr>
                        <tr>
                            <td><strong>Municipality:</strong></td>
                            <td>${municipality}</td>
                        </tr>
                        <tr>
                            <td><strong>Barangay:</strong></td>
                            <td>${barangay}</td>
                        </tr>
                        <tr>
                            <td><strong>Street:</strong></td>
                            <td>${street}</td>
                        </tr>
                    </table>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <img src="${validid}" alt="Valid ID" style="width: 150px; height: 150px; border-radius: 10px; object-fit: cover;">
                    <p>Valid ID</p>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <button class="accept-btn" onclick="handleAction('accept', ${pendingUserID})">Accept</button>
                    <button class="reject-btn" onclick="handleAction('reject', ${pendingUserID})">Reject</button>
                </div>
            `,
            showConfirmButton: false,
            width: 600
        });
    }

    // Handle Accept and Reject actions
    function handleAction(action, userId) {
        const actionText = action === 'accept' ? 'accept' : 'reject';
        Swal.fire({
            title: `Are you sure you want to ${actionText} this user?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: `Yes, ${actionText} it!`,
            cancelButtonText: 'No, cancel!',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Create a form and submit it to the server for action
                const form = document.createElement('form');
                form.method = 'post';
                form.innerHTML = `
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="action" value="${action}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>

</body>
</html>