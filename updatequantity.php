<?php
session_start();
include("config.php");

if (isset($_SESSION['userid'])) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['cart_item_id']) && isset($_POST['quantity'])) {
            $cartItemId = intval($_POST['cart_item_id']);
            $quantity = floatval($_POST['quantity']); // Ensure it handles decimal values

            // Update the quantity in the cart
            $sql_update = "UPDATE cart SET cart_quantity = ? WHERE cart_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("di", $quantity, $cartItemId); // Use "d" for decimals
            $stmt_update->execute();

            // Calculate the new total cost
            $userId = $_SESSION['userid'];
            $sql_total = "SELECT SUM(cart_price * cart_quantity) AS total_cost FROM cart WHERE cart_userid = ?";
            $stmt_total = $conn->prepare($sql_total);
            $stmt_total->bind_param("i", $userId);
            $stmt_total->execute();
            $result_total = $stmt_total->get_result();
            $row_total = $result_total->fetch_assoc();

            // Return the new total cost formatted to two decimal places
            echo number_format($row_total['total_cost'], 2);

            $stmt_update->close();
            $stmt_total->close();
        }
    }
} else {
    // If not logged in, return an error (you could handle this with an appropriate message)
    echo "Error: User not logged in";
}

$conn->close();
?>
