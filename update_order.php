<?php
session_start();
include("config.php");

// Check if an order ID is provided
if (!isset($_GET['order_id'])) {
    echo "No order ID provided.";
    exit();
}

$order_id = (int)$_GET['order_id'];

// Fetch the order details, including payment ID, shipping address, and tracking number
$sql = "SELECT 
            o.order_id, 
            o.order_date, 
            o.order_status, 
            p.payment_status, 
            p.amount_paid, 
            p.total_amount, 
            p.payment_id,
            t.tracking_number, 
            t.delivery_date, 
            t.payment_id AS tracking_payment_id,
            u.firstname, 
            u.lastname, 
            CONCAT(ra.purok_street, ', ', ra.barangay, ', ', ra.municipality) AS shipping_address
        FROM 
            `order` o
        JOIN 
            `user` u ON o.userid = u.userID
        LEFT JOIN 
            `payment` p ON o.order_id = p.order_id
        LEFT JOIN 
            `tracking` t ON o.order_id = t.order_id
        LEFT JOIN 
            `receiver` r ON o.userid = r.userid
        LEFT JOIN 
            `receiver_address` ra ON r.receiver_id = ra.receiver_id
        WHERE 
            o.order_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Order not found.";
    exit();
}

$order = $result->fetch_assoc();

// Fetch order items and calculate total amount dynamically
$total_amount = 0; // Initialize total amount to zero
$sql_items = "SELECT oi.quantity, (oi.price * oi.quantity) AS subtotal, p.productname 
              FROM order_items oi 
              JOIN products p ON oi.productid = p.productid 
              WHERE oi.order_id = ?";

$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();

$order_items = []; // Array to store item details for display
while ($item = $result_items->fetch_assoc()) {
    $total_amount += $item['subtotal']; // Sum up subtotals for total amount
    $order_items[] = $item; // Save item details for later display
}

// Handle form submission to update the order
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $order_status = $_POST['order_status'];
    $amount_paid = isset($_POST['amount_paid']) ? (float)$_POST['amount_paid'] : $order['amount_paid'];
    $delivery_date = null;
    $tracking_payment_id = $order['payment_id']; // Use the payment ID for tracking

    // Automatically determine the payment status based on amount_paid and total_amount
    if ($amount_paid == 0) {
        $payment_status = 'Unpaid';
    } elseif ($amount_paid > 0 && $amount_paid < $total_amount) {
        $payment_status = 'Partial';
    } elseif ($amount_paid == $total_amount) {
        $payment_status = 'Paid';
    }

    // Set the delivery_date automatically if status is 'Delivered'
    if ($order_status === 'Delivered') {
        $delivery_date = date("Y-m-d"); // Current date in YYYY-MM-DD format
    }

    // Update payment table
    $update_sql = "UPDATE `payment` 
                   SET payment_status = ?, amount_paid = ? 
                   WHERE order_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sdi", $payment_status, $amount_paid, $order_id);

    // Update order table
    $update_order_sql = "UPDATE `order` 
                         SET order_status = ? 
                         WHERE order_id = ?";
    $update_order_stmt = $conn->prepare($update_order_sql);
    $update_order_stmt->bind_param("si", $order_status, $order_id);

    // Update tracking table with delivery date and payment ID
    $update_tracking_sql = "UPDATE `tracking` 
                            SET delivery_date = ?, payment_id = ? 
                            WHERE order_id = ?";
    $update_tracking_stmt = $conn->prepare($update_tracking_sql);
    $update_tracking_stmt->bind_param("sii", $delivery_date, $tracking_payment_id, $order_id);

    // Execute the updates
    if ($update_stmt->execute() && $update_order_stmt->execute() && $update_tracking_stmt->execute()) {
        header("Location: courier_dashboard.php");
        exit();
    } else {
        echo "<p>Error updating the order. Please try again later.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Update Order</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 100%;
            padding: 15px;
        }
        .order-card {
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 15px;
        }
        .grid-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-gap: 15px;
        }
        .grid-layout .left-side, .grid-layout .right-side {
            display: flex;
            flex-direction: column;
        }
        .grid-layout div {
            padding: 10px;
            font-size: 16px;
            font-weight: bold;
        }
        .grid-layout .label {
            font-size: 14px;
            font-weight: normal;
        }
        @media (max-width: 768px) {
            .grid-layout {
                grid-template-columns: 1fr; /* Stack them on top of each other on smaller screens */
            }
        }
        .form-group label {
            font-size: 14px;
            margin-bottom: 5px;
            display: block;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
        }
        .btn-submit {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        .btn-submit:hover {
            background-color: #0056b3;
        }
        .order-items {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .order-items h3 {
            margin-bottom: 20px;
        }
        .order-items ul {
            list-style: none;
            padding: 0;
        }
        .order-items ul li {
            background-color: #f1f1f1;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 5px;
        }
        .order-items ul li span {
            display: block;
            font-size: 14px;
        }
        @media (min-width: 768px) {
            .container {
                max-width: 600px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="order-card">
        <h2>Order Details</h2>
        <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order['order_id']); ?></p>
        <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order['order_date']); ?></p>
        <p><strong>User:</strong> <?php echo htmlspecialchars($order['firstname'] . ' ' . $order['lastname']); ?></p>
        <p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
        <p><strong>Total Amount:</strong> ₱<?php echo number_format($total_amount, 2); ?></p>
        <p><strong>Amount Paid:</strong> ₱<?php echo number_format($order['amount_paid'], 2); ?></p>
        <?php if ($order['payment_status'] === 'Partial') { ?>
            <p><strong>Balance:</strong> ₱<?php echo number_format($total_amount - $order['amount_paid'], 2); ?></p>
        <?php } ?>
        <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($order['payment_status']); ?></p>
    </div>

    <div class="order-items">
        <h3>Order Items</h3>
        <ul>
            <?php foreach ($order_items as $item) { ?>
                <li>
                    <strong>Product:</strong> <?php echo htmlspecialchars($item['productname']); ?><br>
                    <strong>Quantity:</strong> <?php echo htmlspecialchars($item['quantity']); ?><br>
                    <strong>Subtotal:</strong> ₱<?php echo number_format($item['subtotal'], 2); ?>
                </li>
            <?php } ?>
        </ul>
    </div>

    <form method="post" action="">
        <div class="order-card">
            <div class="form-group">
                <label for="order_status">Order Status:</label>
                <select name="order_status" id="order_status" required>
             
                    <option value="Delivered" <?php echo $order['order_status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="Cancelled" <?php echo $order['order_status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="form-group">
                <label for="amount_paid">Amount Paid:</label>
                <input type="number" name="amount_paid" id="amount_paid" step="0.01" 
                       value="<?php echo htmlspecialchars($order['amount_paid']); ?>" 
                       <?php echo $order['payment_status'] === 'Paid' ? 'readonly' : ''; ?>>
            </div>
            <button type="submit" class="btn-submit">Update Order</button>
        </div>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
