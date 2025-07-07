<?php
session_start();
include("config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $accepted_pigs = $_POST['accepted_pigs'];
    $weights = $_POST['weights'];
    $price_per_kilo = $_POST['price_per_kilo'];
    $total_weight = $_POST['total_weight'];
    $total_price = $_POST['total_price'];

    // Step 1: Validate input
    if ($price_per_kilo <= 0 || $total_price <= 0) {
        $_SESSION['error_message'] = "Price per kilo and total price must be greater than zero.";
        header("Location: sell_requests.php");
        exit();
    }

    if (count($weights) !== (int)$accepted_pigs) {
        $_SESSION['error_message'] = "Number of weights provided does not match the accepted pigs.";
        header("Location: sell_requests.php");
        exit();
    }

    $calculated_total_weight = array_sum($weights);
    if (abs($calculated_total_weight - $total_weight) > 0.01) { // Allow small floating-point differences
        $_SESSION['error_message'] = "Total weight does not match the sum of individual weights.";
        header("Location: sell_requests.php");
        exit();
    }

    $calculated_total_price = $calculated_total_weight * $price_per_kilo;
    if (abs($calculated_total_price - $total_price) > 0.01) {
        $_SESSION['error_message'] = "Total price is not consistent with total weight and price per kilo.";
        header("Location: sell_requests.php");
        exit();
    }

    // Step 2: Lock the record to prevent race conditions
    $lock_query = "SELECT status FROM pig_sell_requests WHERE id = ? FOR UPDATE";
    $stmt = $conn->prepare($lock_query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $_SESSION['error_message'] = "Sell request not found.";
        header("Location: sell_requests.php");
        exit();
    }
    

    $sell_request = $result->fetch_assoc();
    if ($sell_request['status'] !== 'pending') {
        $_SESSION['error_message'] = "Sell request is no longer pending.";
        header("Location: sell_requests.php");
        exit();
    }

    // Step 3: Update the sell request
    $update_query = "UPDATE pig_sell_requests 
                     SET status = 'accepted', 
                         weight = ?, 
                         price = ?, 
                         price_per_kilo = ?, 
                         processed_at = NOW() 
                     WHERE id = ?";
    $stmt_update = $conn->prepare($update_query);
    $stmt_update->bind_param("dddi", $total_weight, $total_price, $price_per_kilo, $request_id);
    if ($stmt_update->execute()) {
        $_SESSION['success_message'] = "Sell request accepted successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to accept the sell request.";
    }

    header("Location: sell_requests.php");
    exit();
}
?>
