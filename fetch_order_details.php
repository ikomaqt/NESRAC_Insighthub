<?php
include("config.php");

if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];

    // Fetch the main order details, including receiver info and address
    $sql_order = "SELECT o.order_id, o.order_date, 
                     r.receiver_name, 
                     ra.purok_street, ra.barangay, ra.municipality, 
                     r.receiver_phone,
                     SUM(oi.quantity * oi.price) AS calculated_total_amount
                  FROM `order` o
                  LEFT JOIN order_address oa ON o.order_id = oa.order_id
                  LEFT JOIN receiver_address ra ON oa.receiverAddress_id = ra.receiverAddress_id
                  LEFT JOIN receiver r ON ra.receiver_id = r.receiver_id
                  LEFT JOIN order_items oi ON o.order_id = oi.order_id
                  WHERE o.order_id = ?";

    $stmt_order = $conn->prepare($sql_order);
    $stmt_order->bind_param("i", $order_id);
    $stmt_order->execute();
    $result_order = $stmt_order->get_result();

    if ($result_order->num_rows == 0) {
        echo "<p>Order not found.</p>";
        exit();
    }

    $order = $result_order->fetch_assoc();

    // Fetch order items
    $sql_items = "SELECT oi.quantity, oi.price, p.productname
                  FROM order_items oi 
                  JOIN products p ON oi.productid = p.productid 
                  WHERE oi.order_id = ?";
    $stmt_items = $conn->prepare($sql_items);
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    ?>

    <div class="modal-body">
        <div class="receipt-container">
            <!-- Order Summary -->
            <div class="receipt-header">
                <h3></h3>
                <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order['order_id']); ?></p>
                <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order['order_date']); ?></p>
            </div>

            <!-- Receiver Information -->
            <div class="receipt-section">
                <h4>Receiver Information</h4>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($order['receiver_name']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($order['purok_street'] . ', ' . $order['barangay'] . ', ' . $order['municipality']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['receiver_phone']); ?></p>
            </div>

            <!-- Order Items -->
            <div class="order-items">
                <h4>Items in this Order</h4>
                <table class="table table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $result_items->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['productname']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                <td>₱<?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Total Price -->
            <div class="total">
                <h4>Total Amount: ₱<?php echo number_format($order['calculated_total_amount'], 2); ?></h4>
            </div>
        </div>
    </div>

    <?php
    $stmt_order->close();
    $stmt_items->close();
} else {
    echo "<p>Invalid order ID.</p>";
}

$conn->close();
?>
