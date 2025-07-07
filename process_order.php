<?php
session_start();
ob_start();
include("config.php");

// Check if the user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$userid = $_SESSION['userid'];
$total_amount = isset($_POST['total_cost']) ? floatval($_POST['total_cost']) : 0;
$selected_address = isset($_POST['selected_address']) ? intval($_POST['selected_address']) : null;

// Validate input
if ($total_amount <= 0 || empty($selected_address)) {
    echo "Invalid order details.";
    exit();
}

// Begin transaction
$conn->begin_transaction();

try {
    // Insert the order into the database
    $sql_order = "INSERT INTO `order` (userid, order_status, order_date) VALUES (?, 'Pending', NOW())";
    $stmt_order = $conn->prepare($sql_order);
    $stmt_order->bind_param("i", $userid);
    if (!$stmt_order->execute()) {
        throw new Exception("Error inserting order: " . $stmt_order->error);
    }
    $orderId = $stmt_order->insert_id;

    // Insert payment details
    $sql_payment = "INSERT INTO `payment` (total_amount, payment_status, amount_paid, order_id) VALUES (?, 'Unpaid', 0.00, ?)";
    $stmt_payment = $conn->prepare($sql_payment);
    $stmt_payment->bind_param("di", $total_amount, $orderId);
    if (!$stmt_payment->execute()) {
        throw new Exception("Error inserting payment: " . $stmt_payment->error);
    }

    // Generate a unique tracking number
    $tracking_number = strtoupper(uniqid('TRK'));

    // Insert the tracking number into the tracking table
    $sql_tracking = "INSERT INTO `tracking` (tracking_number, order_id) VALUES (?, ?)";
    $stmt_tracking = $conn->prepare($sql_tracking);
    $stmt_tracking->bind_param("si", $tracking_number, $orderId);
    if (!$stmt_tracking->execute()) {
        throw new Exception("Error inserting tracking information: " . $stmt_tracking->error);
    }

    // Link the selected receiver address to the order
    $sql_link_address = "INSERT INTO `order_address` (order_id, receiverAddress_id) VALUES (?, ?)";
    $stmt_link_address = $conn->prepare($sql_link_address);
    $stmt_link_address->bind_param("ii", $orderId, $selected_address);
    if (!$stmt_link_address->execute()) {
        throw new Exception("Error linking address to order: " . $stmt_link_address->error);
    }

    // Fetch cart items with current product prices
    $sql_cart = "SELECT c.cart_productid, c.cart_quantity, p.price AS product_price
                 FROM cart c
                 JOIN products p ON c.cart_productid = p.productid
                 WHERE c.cart_userid = ?";
    $stmt_cart = $conn->prepare($sql_cart);
    $stmt_cart->bind_param("i", $userid);
    $stmt_cart->execute();
    $result_cart = $stmt_cart->get_result();

    while ($row = $result_cart->fetch_assoc()) {
        // Insert order items with the correct product price
        $sql_order_item = "INSERT INTO `order_items` (order_id, productid, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt_order_item = $conn->prepare($sql_order_item);
        $stmt_order_item->bind_param("iidd", $orderId, $row['cart_productid'], $row['cart_quantity'], $row['product_price']);
        if (!$stmt_order_item->execute()) {
            throw new Exception("Error inserting order item for product ID " . $row['cart_productid'] . ": " . $stmt_order_item->error);
        }

        // Update product stock
        $sql_update_stock = "UPDATE `products` SET quantity = quantity - ? WHERE productid = ? AND quantity >= ?";
        $stmt_update_stock = $conn->prepare($sql_update_stock);
        $stmt_update_stock->bind_param("ddi", $row['cart_quantity'], $row['cart_productid'], $row['cart_quantity']);
        if (!$stmt_update_stock->execute() || $stmt_update_stock->affected_rows == 0) {
            throw new Exception("Stock update failed for product ID: " . $row['cart_productid']);
        }
    }

    // Clear cart
    $sql_clear_cart = "DELETE FROM `cart` WHERE cart_userid = ?";
    $stmt_clear_cart = $conn->prepare($sql_clear_cart);
    $stmt_clear_cart->bind_param("i", $userid);
    if (!$stmt_clear_cart->execute()) {
        throw new Exception("Error clearing cart: " . $stmt_clear_cart->error);
    }

    // Commit the transaction
    $conn->commit();

    // Redirect to success page with total_amount and tracking number
    header("Location: order_success.php?order_id=" . urlencode($orderId) . "&total_amount=" . urlencode($total_amount) . "&tracking_number=" . urlencode($tracking_number));
    exit();

} catch (Exception $e) {
    $conn->rollback();
    error_log("Failed to place order: " . $e->getMessage());
    echo "Failed to place order: " . $e->getMessage();
}

$conn->close();
ob_end_flush();
