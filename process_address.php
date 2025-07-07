<?php
session_start();
include("config.php");

if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$userid = $_SESSION['userid'];
$receiver_name = trim($_POST['receiver_name']);
$municipality = trim($_POST['municipality']);
$barangay = trim($_POST['barangay']);
$landmark = isset($_POST['landmark']) ? trim($_POST['landmark']) : null; // Optional
$purok_street = trim($_POST['purok_street']);
$receiver_phone = trim($_POST['receiver_phone']);

// Validate input
if (empty($receiver_name) || empty($municipality) || empty($barangay) || empty($purok_street) || empty($receiver_phone)) {
    echo "<script>alert('Please fill in all required address details.'); window.history.back();</script>";
    exit();
}

// Start a transaction to ensure both inserts are successful
$conn->begin_transaction();

try {
    // Check if the receiver already exists for the user
    $sql_check_receiver = "SELECT receiver_id FROM receiver WHERE userid = ? AND receiver_name = ? AND receiver_phone = ? LIMIT 1";
    $stmt_check = $conn->prepare($sql_check_receiver);
    $stmt_check->bind_param("iss", $userid, $receiver_name, $receiver_phone);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // Receiver exists, reuse the existing receiver_id
        $row = $result_check->fetch_assoc();
        $receiver_id = $row['receiver_id'];
    } else {
        // Insert into RECEIVER table
        $sql_receiver = "INSERT INTO receiver (userid, receiver_name, receiver_phone) VALUES (?, ?, ?)";
        $stmt_receiver = $conn->prepare($sql_receiver);
        $stmt_receiver->bind_param("iss", $userid, $receiver_name, $receiver_phone);
        $stmt_receiver->execute();
        $receiver_id = $conn->insert_id;
    }

    // Check if the exact address already exists for this receiver
    $sql_check_address = "SELECT receiverAddress_id FROM receiver_address WHERE receiver_id = ? AND purok_street = ? AND barangay = ? AND municipality = ? LIMIT 1";
    $stmt_check_address = $conn->prepare($sql_check_address);
    $stmt_check_address->bind_param("isss", $receiver_id, $purok_street, $barangay, $municipality);
    $stmt_check_address->execute();
    $result_check_address = $stmt_check_address->get_result();

    if ($result_check_address->num_rows > 0) {
        // Address already exists, no need to insert again
        $row_address = $result_check_address->fetch_assoc();
        $receiverAddress_id = $row_address['receiverAddress_id'];
        $conn->commit();
        echo "<script>alert('This address already exists.'); window.location.href = 'checkout.php?saved_address=" . $receiverAddress_id . "';</script>";
        exit();
    }

    // Insert into RECEIVER_ADDRESS table
    $sql_address = "INSERT INTO receiver_address (landmark, purok_street, barangay, municipality, receiver_id) VALUES (?, ?, ?, ?, ?)";
    $stmt_address = $conn->prepare($sql_address);
    $stmt_address->bind_param("ssssi", $landmark, $purok_street, $barangay, $municipality, $receiver_id);
    $stmt_address->execute();
    $receiverAddress_id = $conn->insert_id;

    // Commit the transaction if both inserts were successful
    $conn->commit();

    // Redirect back to checkout page with the new address selected
    header("Location: checkout.php?saved_address=" . $receiverAddress_id);
    exit();

} catch (Exception $e) {
    // Rollback transaction if any insert fails
    $conn->rollback();
    echo "<script>alert('Failed to save address. Please try again.'); window.history.back();</script>";
}

$conn->close();
?>
