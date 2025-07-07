<?php
session_start();
include("config.php");

// Assuming the user is logged in and we have their user ID in the session
if (isset($_SESSION['userid'])) {
    $userid = $_SESSION['userid'];

    // Update the last_survey_date to today's date in the user table
    $sql = "UPDATE user SET last_survey_date = NOW() WHERE userid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userid);
    $stmt->execute();

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>
