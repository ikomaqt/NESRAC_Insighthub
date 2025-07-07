<?php
include('config.php');

// Handle AJAX request to update status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['userid']) && isset($_POST['new_status'])) {
    $userid = mysqli_real_escape_string($conn, $_POST['userid']);
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);

    // Update the membership status in the `user_membership` table
    $update_query = "UPDATE `user_membership` SET status = '$new_status' WHERE userid = '$userid'";
    if (mysqli_query($conn, $update_query)) {
        // Send success response with the new status
        echo json_encode(['success' => true, 'new_status' => $new_status]);
    } else {
        // If there's an error updating the status, send an error response
        echo json_encode(['success' => false, 'error' => 'Error updating status']);
    }
    mysqli_close($conn);
    exit; // Stop further execution after handling the AJAX request
}

// Set the number of results per page
$results_per_page = 10; // Display 10 orders per page

// Check if userid is provided in the URL
if (isset($_GET['userid'])) {
    $userid = mysqli_real_escape_string($conn, $_GET['userid']);

    // Fetch the user's profile information, including membership status
    $query = "
        SELECT u.*, ua.emailAdd, um.status
        FROM `user` u
        LEFT JOIN `user_account` ua ON u.userID = ua.userID
        LEFT JOIN `user_membership` um ON u.userID = um.userID
        WHERE u.userID = '$userid'
    ";
    $result = mysqli_query($conn, $query);

    // Check if the query returned a result
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
    } else {
        echo "User not found.";
        exit;
    }

    // Get the total number of orders for the user
    $order_count_query = "SELECT COUNT(*) as total FROM `order` WHERE userid = '$userid'";
    $order_count_result = mysqli_query($conn, $order_count_query);
    $total_orders = mysqli_fetch_assoc($order_count_result)['total'];

    // Calculate the number of pages needed
    $number_of_pages = ceil($total_orders / $results_per_page);

    // Get the current page number from the URL or set to 1 if not present
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1; // Prevent invalid page numbers

    // Determine the starting limit for the SQL query
    $starting_limit = ($page - 1) * $results_per_page;

    // Fetch user's order history with pagination
    $order_query = "
        SELECT o.*, p.payment_status, p.total_amount, t.tracking_number
        FROM `order` o
        LEFT JOIN `payment` p ON o.order_id = p.order_id
        LEFT JOIN `tracking` t ON o.order_id = t.order_id
        WHERE o.userid = '$userid'
        ORDER BY o.order_date DESC
        LIMIT $starting_limit, $results_per_page
    ";
    $order_result = mysqli_query($conn, $order_query);
    
} else {
    echo "User ID not provided.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - 
        <?php 
            echo isset($user['firstName']) ? $user['firstName'] : 'N/A'; 
            echo ' ';
            echo isset($user['lastName']) ? $user['lastName'] : 'N/A'; 
        ?>'s Profile
    </title>
    <link rel="stylesheet" href="style/user_profile.css">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
   <style>

body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4;
}

.wrapper {
    display: flex;
    flex-direction: row;
}


.content {
    flex: 1;
    padding: 20px;
}

/* Profile Card */
.profile-card {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

.profile-header {
    text-align: center;
    margin-bottom: 20px;
}

.profile-header h2 {
    font-size: 1.5rem;
    color: #333;
}

.profile-header .email {
    color: #7f8c8d;
    font-size: 0.9rem;
}

.profile-header .status {
    margin-top: 10px;
    font-size: 1rem;
}

.edit-status-btn {
    background-color: #3498db;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 10px;
}

.edit-status-btn:hover {
    background-color: #2980b9;
}

/* Profile Details */
.profile-details {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.detail-item {
    flex: 1 1 45%;
    background-color: #f9f9f9;
    padding: 10px 15px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.detail-item strong {
    color: #333;
    display: block;
    margin-bottom: 5px;
}

/* Order History */
.order-history {
    margin-top: 30px;
}

.order-history h3 {
    font-size: 1.2rem;
    color: #333;
    margin-bottom: 10px;
}

.order-history table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    background-color: #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border-radius: 10px;
    overflow: hidden;
}

.order-history table th,
.order-history table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.order-history table th {
    background-color: #3498db;
    color: #fff;
}

.order-history table td {
    color: #555;
}

.order-history table tr:last-child td {
    border-bottom: none;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 10px;
}

.pagination a {
    text-decoration: none;
    background-color: #ecf0f1;
    padding: 8px 15px;
    border-radius: 5px;
    color: #333;
    font-size: 0.9rem;
}

.pagination a:hover,
.pagination a.active {
    background-color: #3498db;
    color: #fff;
}
</style>
</head>
<body>

<div class="wrapper">
    <div class="navbar">
        <?php include('admin.php'); ?> <!-- Include sidebar/navbar -->
    </div>

    <div class="content">
        <div class="profile-card">
            <div class="profile-header">
                <h2><?php echo isset($user['firstName']) ? $user['firstName'] : 'N/A'; ?> <?php echo isset($user['lastName']) ? $user['lastName'] : 'N/A'; ?></h2>
                <p class="email"><?php echo isset($user['emailAdd']) ? $user['emailAdd'] : 'N/A'; ?></p>
                <p class="status">Status: <span id="status-text"><?php echo isset($user['status']) ? $user['status'] : 'N/A'; ?></span></p>
                <!-- Edit Status Button -->
                <button class="edit-status-btn" onclick="editStatus(<?php echo $userid; ?>)">Edit Status</button>
            </div>
            
            <div class="profile-details">
                <div class="detail-item">
                    <strong>User ID:</strong> <?php echo isset($user['userID']) ? $user['userID'] : 'N/A'; ?>
                </div>
                <div class="detail-item">
                    <strong>Birthday:</strong> <?php echo isset($user['bday']) ? $user['bday'] : 'N/A'; ?>
                </div>
                <div class="detail-item">
                    <strong>Gender:</strong> <?php echo isset($user['gender']) ? $user['gender'] : 'N/A'; ?>
                </div>
                <div class="detail-item">
                    <strong>Contact Number:</strong> <?php echo isset($user['contactNumber']) ? $user['contactNumber'] : 'N/A'; ?>
                </div>
            </div>

            <div class="order-history">
                <h3>Order History</h3>
                <?php if (mysqli_num_rows($order_result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Order Date</th>
                            <th>Total Amount</th>
                            <th>Payment Status</th>
                            <th>Order Status</th>
                            <th>Tracking Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($order = mysqli_fetch_assoc($order_result)): ?>
                        <tr>
                            <td><?php echo $order['order_id']; ?></td>
                            <td><?php echo $order['order_date']; ?></td>
                            <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><?php echo $order['payment_status']; ?></td>
                            <td><?php echo $order['order_status']; ?></td>
                            <td><?php echo $order['tracking_number']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p>No orders found for this user.</p>
                <?php endif; ?>

                <!-- Pagination -->
                <div class="pagination">
                    <?php for ($i = 1; $i <= $number_of_pages; $i++): ?>
                        <a href="?userid=<?php echo $userid; ?>&page=<?php echo $i; ?>" class="<?php if ($i == $page) echo 'active'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Function to edit the status using SweetAlert2
function editStatus(userid) {
    Swal.fire({
        title: 'Change Membership Status',
        html:
            '<select id="newStatus" class="swal2-input">' +
            '<option value="Member">Member</option>' +
            '<option value="Non-Member">Non-Member</option>' +
            '</select>',
        showCancelButton: true,
        confirmButtonText: 'Submit',
        preConfirm: () => {
            const newStatus = Swal.getPopup().querySelector('#newStatus').value;
            if (!newStatus) {
                Swal.showValidationMessage('Please select a status');
            }
            return newStatus;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const newStatus = result.value;

            // Make an AJAX request to update the status
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '', true); // The form will POST to the same PHP file
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (this.status === 200) {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        Swal.fire(
                            'Updated!',
                            'The membership status has been updated.',
                            'success'
                        );
                        // Update the status text on the page
                        document.getElementById('status-text').innerText = response.new_status;
                    } else {
                        Swal.fire(
                            'Error!',
                            response.error,
                            'error'
                        );
                    }
                }
            };
            xhr.send('userid=' + userid + '&new_status=' + newStatus);
        }
    });
}
</script>

</body>
</html>