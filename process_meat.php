<?php
session_start();
include("config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $product_ids = $_POST['product_ids'];
    $quantities = $_POST['quantities'];

    // Fetch the live weight for this request
    $query_live_weight = "SELECT weight FROM pig_sell_requests WHERE id = ?";
    $stmt = $conn->prepare($query_live_weight);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        $_SESSION['error_message'] = "Sell request not found.";
        header("Location: sell_requests.php");
        exit();
    }

    $live_weight = $row['weight'];

    // Calculate the total processed weight
    $total_processed_weight = 0;
    for ($i = 0; $i < count($product_ids); $i++) {
        $total_processed_weight += $quantities[$i];
    }

    // Validate the processed weight does not exceed the live weight
    if ($total_processed_weight > $live_weight) {
        $_SESSION['error_message'] = "Total processed weight (${total_processed_weight} kg) exceeds the live weight (${live_weight} kg).";
        header("Location: sell_requests.php");
        exit();
    }

    // Update the sell request to mark it as processed without changing the status
    $update_query = "UPDATE pig_sell_requests SET is_processed = 1, processed_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $request_id);
    if ($stmt->execute()) {
        // Insert each product's quantity into the processed_meat table and update the product stock
        for ($i = 0; $i < count($product_ids); $i++) {
            $product_id = $product_ids[$i];
            $quantity = $quantities[$i];

            // Fetch the product price from the products table
            $price_query = "SELECT price FROM products WHERE productid = ?";
            $stmt_price = $conn->prepare($price_query);
            $stmt_price->bind_param("i", $product_id);
            $stmt_price->execute();
            $result_price = $stmt_price->get_result();
            $product = $result_price->fetch_assoc();
            $price = $product['price'];

            // Calculate the total price
            $total_price = $quantity * $price;

            // Insert into the processed_meat table
            $insert_query = "INSERT INTO processed_meat (sell_request_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($insert_query);
            $stmt_insert->bind_param("iiid", $request_id, $product_id, $quantity, $total_price);
            $stmt_insert->execute();

            // Update the product quantity in the products table by adding the processed quantity
            $update_quantity_query = "UPDATE products SET quantity = quantity + ? WHERE productid = ?";
            $stmt_update_quantity = $conn->prepare($update_quantity_query);
            $stmt_update_quantity->bind_param("ii", $quantity, $product_id);
            $stmt_update_quantity->execute();
        }

        $_SESSION['success_message'] = "Meat processed and quantity updated successfully.";
        header("Location: sell_requests.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Failed to process meat. Please try again.";
        header("Location: sell_requests.php");
        exit();
    }
}

$conn->close();
?>
