<?php
session_start();

// Include configuration file and establish database connection
include("config.php");

// Check if the user is logged in
if (!isset($_SESSION['userid'])) {
    // Redirect to the login page or display an error message
    header("Location: login.php");
    exit();
}

// Check if cart_item_id is provided
if (isset($_POST['cart_item_id'])) {
    // Get the cart item ID to be removed
    $cartItemId = $_POST['cart_item_id'];

    // Prepare SQL statement to delete the item from the cart
    $sql = "DELETE FROM cart WHERE cart_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cartItemId);
    $stmt->execute();
    $stmt->close();

    // Redirect back to the cart page
    header("Location: basket.php");
    exit();
} else {
    // If cart_item_id is not provided, redirect back to the cart page or display an error message
    header("Location: prod.php");
    exit();
}

// Close the database connection
$conn->close();
?>
