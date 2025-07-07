<?php
session_start();
include("config.php");
include("navbar.php"); // Include the navbar at the top

if (!isset($_GET['order_id'])) {
    echo "No order ID provided.";
    exit();
}

$order_id = intval($_GET['order_id']); // Sanitize input

// Fetch order details including the tracking number and receiver's address details
$sql_order = "SELECT o.order_id, o.order_date,
                     r.receiver_name, 
                     ra.purok_street, 
                     ra.barangay, 
                     ra.municipality, 
                     r.receiver_phone,
                     p.total_amount,
                     t.tracking_number
              FROM `order` o
              LEFT JOIN order_address oa ON o.order_id = oa.order_id
              LEFT JOIN receiver_address ra ON oa.receiverAddress_id = ra.receiverAddress_id
              LEFT JOIN receiver r ON ra.receiver_id = r.receiver_id
              LEFT JOIN payment p ON o.order_id = p.order_id
              LEFT JOIN tracking t ON o.order_id = t.order_id
              WHERE o.order_id = ?";
$stmt_order = $conn->prepare($sql_order);
$stmt_order->bind_param("i", $order_id);
$stmt_order->execute();
$result_order = $stmt_order->get_result();

if ($result_order->num_rows == 0) {
    echo "Order not found.";
    exit();
}

$order = $result_order->fetch_assoc();

// Fetch order items
$sql_items = "SELECT oi.productid, p.productname, oi.price AS item_price, oi.quantity AS item_quantity
              FROM order_items oi 
              JOIN products p ON oi.productid = p.productid 
              WHERE oi.order_id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Receipt</title>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .receipt-container {
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        .receipt-header {
            text-align: center;
        }
        .receipt-section {
            margin-bottom: 20px;
        }
        .order-items table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .order-items th, .order-items td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .total {
            font-weight: bold;
            font-size: 1.2em;
            text-align: right;
        }
        .receipt-actions {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 15px;
            margin: 0 5px;
            text-decoration: none;
            color: #fff;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background-color: #007bff;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>

<div class="navbar">
    <!-- Navbar is already included at the top via navbar.php -->
</div>

<div class="receipt-container">
    <div class="receipt-header">
        <h1>NESRAC</h1>
        <p>Nueva Ecija Swine Raiser Cooperative</p>
        <p>Campos Talavera Nueva Ecija</p>
    </div>

    <div class="receipt-section">
        <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order['order_id']); ?></p>
        <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order['order_date']); ?></p>
        <p><strong>Tracking Number:</strong> <?php echo htmlspecialchars($order['tracking_number'] ?? 'N/A'); ?></p>
    </div>

    <div class="receipt-section">
        <h3>Receiver Information</h3>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($order['receiver_name']); ?></p>
        <p><strong>Address:</strong> <?php echo htmlspecialchars($order['purok_street'] . ', ' . $order['barangay'] . ', ' . $order['municipality']); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['receiver_phone']); ?></p>
    </div>

    <div class="order-items">
        <h3>Order Items</h3>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
    <?php 
    $total_cost = 0.0;
    while ($item = $result_items->fetch_assoc()) {
        $item_price = floatval($item['item_price']);
        $item_quantity = floatval($item['item_quantity']);
        $subtotal = $item_price * $item_quantity;
        $total_cost += $subtotal;
    ?>
        <tr>
            <td><?php echo htmlspecialchars($item['productname']); ?></td>
            <td><?php echo number_format($item_quantity, 2, '.', ','); ?></td>
            <td>₱<?php echo number_format($item_price, 2, '.', ','); ?></td>
            <td>₱<?php echo number_format($subtotal, 2, '.', ','); ?></td>
        </tr>
    <?php } ?>
            </tbody>
        </table>
    </div>

    <p class="total"><strong>Total: ₱<?php echo number_format($total_cost, 2, '.', ','); ?></strong></p>
</div>

<div class="receipt-actions">
    <a href="homepage.php" class="btn btn-secondary">Back to Home</a>
    <a href="#" class="btn btn-primary" id="downloadReceipt">Download Receipt as Image</a>
</div>

<script>
document.getElementById('downloadReceipt').addEventListener('click', function() {
    html2canvas(document.querySelector('.receipt-container')).then(function(canvas) {
        const link = document.createElement('a');
        link.href = canvas.toDataURL('image/png');
        link.download = 'receipt.png';
        link.click();
    });
});
</script>

</body>
</html>

<?php
$conn->close();
?>
