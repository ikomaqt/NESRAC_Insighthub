<?php
session_start();
include("config.php");
include("admin.php");

// Check if the user is logged in and is an admin
if (!isset($_SESSION['userid']) || $_SESSION['role'] == 'admin') {
    header("Location: login.php");
    exit();
}

// Handle search query and filters
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';

// Fetch user balances
$sql_balance_tracking = "SELECT 
                            u.userid,
                            u.firstname,
                            u.lastname,
                            u.email,
                            SUM(o.total_amount) AS total_amount,
                            SUM(o.amount_paid) AS total_paid,
                            (SUM(o.total_amount) - SUM(o.amount_paid)) AS balance
                        FROM 
                            users u
                        JOIN 
                            orders o ON u.userid = o.userid
                        WHERE 
                            u.role != 'admin'";

// Add search and payment status filters
if ($search_query) {
    $sql_balance_tracking .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.email LIKE ?)";
}

if ($payment_status) {
    if ($payment_status == 'paid') {
        $sql_balance_tracking .= " AND (SUM(o.total_amount) - SUM(o.amount_paid)) = 0";
    } elseif ($payment_status == 'unpaid') {
        $sql_balance_tracking .= " AND (SUM(o.amount_paid)) = 0";
    } elseif ($payment_status == 'balance') {
        $sql_balance_tracking .= " AND (SUM(o.total_amount) - SUM(o.amount_paid)) > 0";
    }
}

$sql_balance_tracking .= " GROUP BY u.userid ORDER BY u.lastname ASC";

$stmt = $conn->prepare($sql_balance_tracking);

if ($search_query) {
    $search_param = '%' . $search_query . '%';
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
}

$stmt->execute();
$result_balance_tracking = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/balance_tracking.css">
    <title>User Balance Tracking</title>
    <style>
        body {
            display: flex;
            margin: 0;
            padding: 0;
        }
        .navbar {
            width: 200px;
            background-color: #333;
            color: white;
            padding: 15px;
        }
        .content {
            flex-grow: 1;
            padding: 20px;
            margin-left: 220px;
        }
        .card {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin: 20px auto;
            background-color: #fff;
            box-shadow: 0px 6px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow-x: auto;
            max-width: 100%;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0px 12px 24px rgba(0,0,0,0.2);
        }
        .card h3 {
            margin-top: 0;
            font-size: 1.75em;
            color: #d32f2f;
            border-bottom: 2px solid #d32f2f;
            padding-bottom: 10px;
            white-space: nowrap;
        }
        .card p {
            margin: 10px 0;
            font-size: 1.1em;
            color: #444;
        }
        .card table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            min-width: 600px;
        }
        .card table th,
        .card table td {
            padding: 8px;
            border: 1px solid #e0e0e0;
            text-align: left;
            font-size: 0.9em;
        }
        .card table th {
            background-color: #f8f8f8;
            font-weight: bold;
            color: #d32f2f;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .card table td {
            color: #555;
        }
        .card table tr:nth-child(even) {
            background-color: #fefefe;
        }
        .filter-container {
            margin-bottom: 20px;
        }
        .filter-container input[type="text"],
        .filter-container select {
            padding: 8px;
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-right: 10px;
        }
        .filter-container button {
            padding: 8px 12px;
            border: none;
            background-color: #333;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }
        .filter-container button:hover {
            background-color: #555;
        }
    </style>
</head>
<body>
<div class="navbar">
    <h3>Navigation</h3>
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="orders.php">Orders</a></li>
        <li><a href="balance_tracking.php">Balance Tracking</a></li>
    </ul>
</div>
<div class="content">
    <div class="card">
        <h3>User Balance Tracking</h3>

        <!-- Filter Form -->
        <div class="filter-container">
            <form action="" method="get">
                <input type="text" name="search" placeholder="Search by user, email..." value="<?php echo htmlspecialchars($search_query); ?>">
                <select name="payment_status">
                    <option value="">All Payment Statuses</option>
                    <option value="paid" <?php echo $payment_status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="unpaid" <?php echo $payment_status === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                    <option value="balance" <?php echo $payment_status === 'balance' ? 'selected' : ''; ?>>Has Balance</option>
                </select>
                <button type="submit">Filter Users</button>
            </form>
        </div>

        <!-- User Balance Table -->
        <table>
            <thead>
            <tr>
                <th>User ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Total Amount</th>
                <th>Total Paid</th>
                <th>Balance</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($result_balance_tracking->num_rows > 0): ?>
                <?php while ($row = $result_balance_tracking->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['userid']); ?></td>
                        <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                        <td>₱<?php echo number_format($row['total_paid'], 2); ?></td>
                        <td>₱<?php echo number_format($row['balance'], 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No users found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
