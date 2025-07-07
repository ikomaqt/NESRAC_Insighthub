<?php
session_start();
include("config.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    $userid = $_SESSION['userid'];

    // Check if the order belongs to the user and get its status
    $check_order_query = "SELECT order_id, order_status FROM `order` WHERE order_id = ? AND userid = ?";
    $stmt = $conn->prepare($check_order_query);
    $stmt->bind_param("ii", $order_id, $userid);
    $stmt->execute();
    $stmt->bind_result($existing_order_id, $order_status);
    $stmt->fetch();
    $stmt->close();

    if ($existing_order_id) {
        if ($order_status !== 'Cancelled' && $order_status !== 'Packed' && $order_status !== 'Delivered') {
            // Update the order status to 'Cancelled'
            $conn->begin_transaction();
            try {
                $cancel_order_query = "UPDATE `order` SET order_status = 'Cancelled' WHERE order_id = ? AND userid = ?";
                $stmt = $conn->prepare($cancel_order_query);
                $stmt->bind_param("ii", $order_id, $userid);
                $stmt->execute();
                $stmt->close();

                // Retrieve the products and quantities associated with the order
                $get_order_items_query = "SELECT productid, quantity FROM order_items WHERE order_id = ?";
                $stmt = $conn->prepare($get_order_items_query);
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                $result = $stmt->get_result();

                // Update each product's available quantity
                while ($row = $result->fetch_assoc()) {
                    $product_id = $row['productid'];
                    $quantity = $row['quantity'];

                    $update_product_query = "UPDATE products SET quantity = quantity + ? WHERE productid = ?";
                    $stmt_update = $conn->prepare($update_product_query);
                    $stmt_update->bind_param("di", $quantity, $product_id);
                    $stmt_update->execute();
                    $stmt_update->close();
                }
                $stmt->close();

                // Commit transaction
                $conn->commit();
                $_SESSION['success_message'] = "Order has been successfully cancelled, and inventory updated.";
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error_message'] = "Failed to cancel the order and update inventory. Please try again.";
            }
        } else {
            // Display a message if the order is not eligible for cancellation
            $_SESSION['error_message'] = "Orders labeled as 'Packed' or 'Delivered' cannot be cancelled.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid order or the order does not belong to you.";
    }

    // Redirect back to the profile page or order history
    header("Location: profile.php");
    exit;
}
?>
