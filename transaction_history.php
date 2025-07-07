<?php
session_start();
include("config.php");
include("admin.php");

// Check if the user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

// Handle search query and date filters
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';

// Fetch transaction history with search filter and date range filter, only for delivered orders
$sql_transaction_history = "SELECT 
                                o.order_id,
                                o.order_date,
                                u.firstname,
                                u.lastname,
                                u.email,
                                o.total_amount,
                                o.amount_paid,
                                o.payment_status,
                                o.payment_method,
                                o.tracking_number
                            FROM 
                                orders o
                            JOIN 
                                user u ON o.userid = u.userid
                            WHERE 
                                (u.firstname LIKE ? 
                                OR u.lastname LIKE ? 
                                OR u.email LIKE ?)
                                AND o.order_status = 'Delivered'"; // Filter for Delivered orders

// Add date filters to the query
if ($start_date) {
    $sql_transaction_history .= " AND o.order_date >= ?";
}
if ($end_date) {
    $sql_transaction_history .= " AND o.order_date <= ?";
}

// Add payment status filter
if ($payment_status) {
    $sql_transaction_history .= " AND o.payment_status = ?";
}

$sql_transaction_history .= " GROUP BY o.order_id ORDER BY o.order_date DESC";

$stmt = $conn->prepare($sql_transaction_history);

$search_param = '%' . $search_query . '%';

$types = "sss";
$params = [$search_param, $search_param, $search_param];

if ($start_date) {
    $types .= 's';
    $params[] = $start_date;
}
if ($end_date) {
    $types .= 's';
    $params[] = $end_date;
}
if ($payment_status) {
    $types .= 's';
    $params[] = $payment_status;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result_transaction_history = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/transaction_history.css">
    <title>Transaction History</title>
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
        .card .total {
            margin-top: 20px;
            font-size: 1.3em;
            font-weight: bold;
            color: #d32f2f;
            text-align: right;
            border-top: 2px solid #d32f2f;
            padding-top: 10px;
        }
        /* Highlighted row styling */
        .card table tr.highlighted {
            background-color: #ffe0e0;
            transition: background-color 0.3s ease, color 0.3s ease;
            box-shadow: inset 0 0 10px rgba(211, 47, 47, 0.3);
        }
        /* Maintain text color contrast for highlighted row */
        .card table tr.highlighted td {
            color: #333;
        }
        /* Responsive adjustments */
        @media (max-width: 600px) {
            .card {
                padding: 15px;
            }

            .card table th, .card table td {
                padding: 6px;
            }

            .card .total {
                font-size: 1.2em;
            }
        }
    </style>
</head>
<body>
<div class="navbar">
    <h3>Navigation</h3>
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="orders.php">Orders</a></li>
        <li><a href="transaction_history.php">Transaction History</a></li>
    </ul>
</div>
<div class="content">
    <div class="card">
        <h2>Transaction History</h2>

        <!-- Search and Filter Form -->
        <div class="search-container">
            <form action="" method="get">
                <input type="text" name="search" placeholder="Search by user, email..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <!-- Date Range and Payment Status Filter -->
        <div class="filter-container">
            <form action="" method="get">
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                <select name="payment_status">
                    <option value="">All Payment Statuses</option>
                    <option value="Paid" <?php echo $payment_status === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="Unpaid" <?php echo $payment_status === 'Unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                    <option value="Partial" <?php echo $payment_status === 'Partial' ? 'selected' : ''; ?>>Partial</option>
                </select>
                <button type="submit">Filter Transactions</button>
            </form>
        </div>

        <!-- Export Button -->
        <div class="export-container">
            <form action="export_transactions.php" method="get">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                <input type="hidden" name="payment_status" value="<?php echo htmlspecialchars($payment_status); ?>">
                <button type="submit">Export to Excel</button>
            </form>
        </div>

        <!-- Transaction History Table -->
        <table>
            <thead>
            <tr>
                <th>Order ID</th>
                <th>Order Date</th>
                <th>User</th>
                <th>Email</th>
                <th>Total Amount</th>
                <th>Amount Paid</th>
                <th>Payment Status</th>
                <th>Payment Method</th>
                <th>Tracking Number</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($result_transaction_history->num_rows > 0): ?>
                <?php while ($row = $result_transaction_history->fetch_assoc()): ?>
                    <tr class="transaction-row" data-order-id="<?php echo htmlspecialchars($row['order_id']); ?>">
                        <td><?php echo htmlspecialchars($row['order_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['order_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                        <td>₱<?php echo number_format($row['amount_paid'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['payment_status']); ?></td>
                        <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                        <td><?php echo htmlspecialchars($row['tracking_number']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9">No transactions found.</td>
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
