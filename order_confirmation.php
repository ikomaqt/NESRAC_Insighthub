<?php
session_start();
include("config.php");

// Check if the user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$userid = $_SESSION['userid'];

// Fetch the latest order details
$sql = "SELECT * FROM orders WHERE userid = ? ORDER BY order_date DESC LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Prepare statement failed: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "No orders found.";
    exit();
}

$order = $result->fetch_assoc();

// Fetch order items
$sql_items = "SELECT oi.*, p.productname FROM order_items oi
              JOIN products p ON oi.product_id = p.productid
              WHERE oi.order_id = ?";
$stmt_items = $conn->prepare($sql_items);
if (!$stmt_items) {
    die('Prepare statement failed: ' . htmlspecialchars($conn->error));
}
$stmt_items->bind_param("i", $order['order_id']);
$stmt_items->execute();
$result_items = $stmt_items->get_result();

$order_items = [];
while ($item = $result_items->fetch_assoc()) {
    $order_items[] = $item;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/confirmation.css">
    <title>Order Confirmation</title>
</head>
<body>
    <header>
        <div class="header-container">
            <h1>Order Confirmation</h1>
        </div>
    </header>
    
    <main>
        <div class="confirmation-container">
            <p class="thank-you">Thank you for your order!</p>
            <p class="confirmation-message">Your order has been successfully placed and is being processed.</p>
            
            <section class="order-summary">
                <h2>Order Summary</h2>
                <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order['order_id']); ?></p>
                <p><strong>Total Amount:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?></p>
                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                <p><strong>Order Status:</strong> <?php echo htmlspecialchars($order['order_status']); ?></p>
                <p><strong>Order Date:</strong> <?php echo htmlspecialchars(date('F j, Y, g:i a', strtotime($order['order_date']))); ?></p>
            </section>
            
            <section class="order-items">
                <h2>Items Ordered</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['productname']); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                <td>₱<?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
            
            <div class="actions">
                <a href="index.php" class="button">Go to Home</a>
                <a href="order_history.php" class="button">View Order History</a>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-container">
            <p>&copy; <?php echo date('Y'); ?> NESRAC. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
