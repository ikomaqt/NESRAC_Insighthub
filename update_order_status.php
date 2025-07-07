<?php
session_start();
include("config.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id'], $_POST['order_status'])) {
    $order_id = intval($_POST['order_id']);
    $order_status = $_POST['order_status'];

    // Validate the input for order status
    $valid_statuses = ['Pending', 'Packed', 'Delivered', 'Cancelled'];
    if (!in_array($order_status, $valid_statuses)) {
        $_SESSION['error_message'] = "Invalid order status.";
        header("Location: order.php");
        exit;
    }

    // Fetch the current status of the order
    $current_status_query = "SELECT order_status FROM `order` WHERE order_id = ?";
    $stmt = $conn->prepare($current_status_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->bind_result($current_status);
    $stmt->fetch();
    $stmt->close();

    // If the order is already canceled, no further action is needed
    if ($current_status === 'Cancelled') {
        $_SESSION['error_message'] = "Order is already cancelled.";
        header("Location: order.php");
        exit;
    }

    // Update the order status in the database
    $update_query = "UPDATE `order` SET order_status = ? WHERE order_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $order_status, $order_id);

    if ($stmt->execute()) {
        if ($order_status === 'Cancelled') {
            // Fetch products and quantities from the order
            $order_items_query = "SELECT productid, quantity FROM order_items WHERE order_id = ?";
            $items_stmt = $conn->prepare($order_items_query);
            $items_stmt->bind_param("i", $order_id);
            $items_stmt->execute();
            $result = $items_stmt->get_result();

            // Restock each product
            while ($row = $result->fetch_assoc()) {
                $product_id = $row['productid'];
                $quantity = $row['quantity'];

                $restock_query = "UPDATE products SET quantity = quantity + ? WHERE productid = ?";
                $restock_stmt = $conn->prepare($restock_query);
                $restock_stmt->bind_param("ii", $quantity, $product_id);
                $restock_stmt->execute();
                $restock_stmt->close();
            }
            $items_stmt->close();
        }

        $_SESSION['success_message'] = "Order status updated successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to update order status. Please try again.";
    }

    $stmt->close();
    $conn->close();

    // Redirect back to the order history page
    header("Location: order.php");
    exit;
}
?>
