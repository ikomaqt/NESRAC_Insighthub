<?php
session_start();
include("config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : null;
    $amount_paid = isset($_POST['amount_paid']) ? floatval($_POST['amount_paid']) : 0.00;
    $payment_status = isset($_POST['payment_status']) ? $_POST['payment_status'] : null;

    if ($order_id && $payment_status) {
        $sql = "UPDATE `payment`
                SET `amount_paid` = ?, 
                    `payment_status` = ?, 
                    `updated_at` = NOW()
                WHERE `order_id` = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dsi", $amount_paid, $payment_status, $order_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Payment updated successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to update payment.";
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Invalid input data.";
    }
    $conn->close();

    // Redirect back to order.php
    header("Location: order.php");
    exit;
}
?>
