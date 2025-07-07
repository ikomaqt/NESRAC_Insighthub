<?php
session_start();
include("config.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $number = $_POST['number'];
    $batch_number = $_POST['batch_number'];
    $piglet_count = $_POST['piglet_count'];
    $birthdate = $_POST['birthdate'];

    // Insert non-member information into non_members table
    $query_non_member = "INSERT INTO non_members (firstname, lastname, number) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query_non_member);
    $stmt->bind_param("sss", $firstname, $lastname, $number);
    $stmt->execute();
    $non_member_id = $stmt->insert_id; // Get the inserted non-member's ID
    $stmt->close();

    // Insert pig sell request into pig_sell_requests table, setting userid to NULL and non_member_id
    $query_sell_request = "INSERT INTO pig_sell_requests (batch_number, piglet_count, birthdate, request_date, status, userid, non_member_id) 
                           VALUES (?, ?, ?, NOW(), 'pending', NULL, ?)";
    $stmt = $conn->prepare($query_sell_request);
    $stmt->bind_param("iisi", $batch_number, $piglet_count, $birthdate, $non_member_id);
    $stmt->execute();
    $stmt->close();

    // Redirect back to the sell requests page
    header("Location: sell_requests.php");
    exit();
}
?>
