<?php
session_start();
include("config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = intval($_POST['request_id']);
    $weight = floatval($_POST['weight']);
    $price = floatval($_POST['price']); // Ensure this matches your form and database column name

    // Update the pig_sell_requests table to mark the request as accepted with additional details
    $stmt = $conn->prepare("UPDATE pig_sell_requests SET weight = ?, price = ?, status = 'accepted', processed_at = NOW() WHERE id = ?");
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }
    
    $bind = $stmt->bind_param("ddi", $weight, $price, $request_id);
    if ($bind === false) {
        die('Bind failed: ' . htmlspecialchars($stmt->error));
    }

    $exec = $stmt->execute();
    if ($exec === false) {
        die('Execute failed: ' . htmlspecialchars($stmt->error));
    }

    $stmt->close();

    header("Location: sell_requests.php?message=Request+accepted");
    exit();
}

$conn->close();
?>
