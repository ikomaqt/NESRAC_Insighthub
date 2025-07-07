<?php
// Include the database connection
include('config.php');

// Initialize variables for search and sort
$search = '';
$sort = '';

// Capture search input
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
}

// Capture sort input
if (isset($_GET['sort'])) {
    $sort = mysqli_real_escape_string($conn, $_GET['sort']);
}

// Main query
$query = "
    SELECT 
        u.userID, 
        u.lastName, 
        u.firstName, 
        u.middleName, 
        u.bday, 
        u.gender, 
        u.contactNumber,
        u.municipality, 
        u.barangay, 
        u.street, 
        ua.emailAdd, 
        ua.password, 
        um.status, 
        um.created_at, 
        um.updated_at,
        us.profile_photo AS profile_photo, 
        us.last_survey,
        ps.total_piglets AS latest_total_piglets, 
        COALESCE(SUM(oi.quantity), 0) AS total_quantity_ordered,
        (
            SELECT COALESCE(SUM(psr.piglet_count), 0)
            FROM pig_sell_requests psr
            LEFT JOIN pig_survey ps ON psr.survey_id = ps.id
            WHERE ps.userid = u.userID AND psr.status = 'accepted'
        ) AS total_member_sold_pigs
    FROM USER u
    LEFT JOIN USER_Account ua ON u.userID = ua.userID
    LEFT JOIN USER_Membership um ON u.userID = um.userID
    LEFT JOIN USER_Assets us ON u.userID = us.userID
    LEFT JOIN (
        SELECT *
        FROM pig_survey
        WHERE id IN (
            SELECT MAX(id)
            FROM pig_survey
            GROUP BY userid
        )
    ) ps ON u.userID = ps.userid
    LEFT JOIN `order` o ON u.userID = o.userid
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    WHERE (u.lastName LIKE '%$search%' 
        OR u.firstName LIKE '%$search%' 
        OR ua.emailAdd LIKE '%$search%')";

// Filter by membership status if specified
if ($sort) {
    $query .= " AND um.status = '$sort'";
}

// Group by user ID to aggregate data
$query .= " GROUP BY u.userID ORDER BY u.userID";

// Execute the query
$result = mysqli_query($conn, $query);

// Handle query execution errors
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// Check if export is triggered
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=member_monitoring_export.csv');

    // Output CSV headers
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'User ID', 'Full Name', 'Email', 'Birthday', 'Gender', 'Contact Number', 
        'Status', 'Total Piglets Owned', 'Total Quantity Ordered', 'Total Pigs Sold'
    ]);

    // Output rows
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['userID'],
            $row['lastName'] . ', ' . $row['firstName'] . ' ' . $row['middleName'],
            $row['emailAdd'],
            $row['bday'],
            $row['gender'],
            $row['contactNumber'],
            $row['status'],
            $row['latest_total_piglets'] ?? 'N/A',
            $row['total_quantity_ordered'] ?? 'N/A',
            $row['total_member_sold_pigs'] ?? 'N/A'
        ]);
    }

    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Monitoring</title>
    <link rel="stylesheet" href="style/admin.css">
    <link rel="stylesheet" href="style/member_monitoring.css">
    <script>
        // Redirect to user profile
        function goToProfile(userId) {
            window.location.href = 'user_profile.php?userid=' + userId;
        }
    </script>
</head>
<body>
<div class="wrapper">
    <?php include('admin.php'); ?>

    <div class="content">
        <h2>Member Monitoring</h2>

        <!-- Search and Export Form -->
        <div class="search-container">
            <form method="GET" action="" id="searchForm">
                <div class="search-input-group">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or email">
                    <button type="submit">Search</button>
                </div>
                <select name="sort" onchange="document.getElementById('searchForm').submit();">
                    <option value="">All</option>
                    <option value="Member" <?php echo $sort == 'Member' ? 'selected' : ''; ?>>Member</option>
                    <option value="Non-Member" <?php echo $sort == 'Non-Member' ? 'selected' : ''; ?>>Non-Member</option>
                </select>
                <button type="submit" name="export" value="1"> Export REPORT </button>
            </form>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Profile Photo</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Birthday</th>
                        <th>Gender</th>
                        <th>Contact Number</th>
                        <th>Status</th>
                        <th>Total Piglets Owned</th>
                        <th>Total Quantity(KG) Ordered</th>
                        <th>Total Pigs Sold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr onclick="goToProfile(<?php echo $row['userID']; ?>)">
                            <td><?php echo $row['userID']; ?></td>
                            <td>
                                <img src="<?php echo htmlspecialchars($row['profile_photo']); ?>" alt="Profile Photo" 
                                     style="width: 50px; height: 50px; border-radius: 50%;">
                            </td>
                            <td><?php echo $row['lastName'] . ', ' . $row['firstName'] . ' ' . $row['middleName']; ?></td>
                            <td><?php echo $row['emailAdd']; ?></td>
                            <td><?php echo $row['bday']; ?></td>
                            <td><?php echo $row['gender']; ?></td>
                            <td><?php echo $row['contactNumber']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                            <td><?php echo isset($row['latest_total_piglets']) ? $row['latest_total_piglets'] : 'N/A'; ?></td>
                            <td><?php echo isset($row['total_quantity_ordered']) ? $row['total_quantity_ordered'] : 'N/A'; ?></td>
                            <td><?php echo isset($row['total_member_sold_pigs']) ? $row['total_member_sold_pigs'] : 'N/A'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
mysqli_close($conn);
?>
</body>
</html>
