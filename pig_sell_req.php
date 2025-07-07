<?php
session_start();
include("config.php");

// Ensure the user is logged in
if (!isset($_SESSION['userid'])) {
    $_SESSION['error_message'] = 'User not logged in.';
    header("Location: profile.php");
    exit();
}

// Handle the sell request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userid = $_SESSION['userid'];
    $batch_number = intval($_POST['batch_number']);
    $sell_count = intval($_POST['sell_count']);

    if ($sell_count > 0 && $batch_number > 0) {
        // Fetch the batch details
        $query = "SELECT piglet_count FROM batch_details WHERE batch_number = ? AND survey_id = (SELECT id FROM pig_survey WHERE userid = ? ORDER BY id DESC LIMIT 1)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $batch_number, $userid);
        $stmt->execute();
        $stmt->bind_result($current_count);
        $stmt->fetch();
        $stmt->close();

        // Check if there are enough pigs in the batch
        if ($current_count >= $sell_count) {
            // Decrement the pig count or delete the batch if the count becomes 0
            $new_count = $current_count - $sell_count;

            if ($new_count == 0) {
                // Delete the batch
                $deleteQuery = "DELETE FROM batch_details WHERE batch_number = ? AND survey_id = (SELECT id FROM pig_survey WHERE userid = ? ORDER BY id DESC LIMIT 1)";
                $deleteStmt = $conn->prepare($deleteQuery);
                $deleteStmt->bind_param("ii", $batch_number, $userid);
                $deleteStmt->execute();
                $deleteStmt->close();
            } else {
                // Update the pig count
                $updateQuery = "UPDATE batch_details SET piglet_count = ? WHERE batch_number = ? AND survey_id = (SELECT id FROM pig_survey WHERE userid = ? ORDER BY id DESC LIMIT 1)";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("iii", $new_count, $batch_number, $userid);
                $updateStmt->execute();
                $updateStmt->close();
            }

            // Insert the sell request
            $insertQuery = "INSERT INTO pig_sell_requests (batch_number, piglet_count, survey_id, status, request_date) VALUES (?, ?, (SELECT id FROM pig_survey WHERE userid = ? ORDER BY id DESC LIMIT 1), 'pending', NOW())";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("iii", $batch_number, $sell_count, $userid);
            if ($insertStmt->execute()) {
                $_SESSION['success_message'] = "Sell request submitted successfully! Remaining pigs in batch: $new_count.";
            } else {
                $_SESSION['error_message'] = "Failed to submit the sell request.";
            }
            $insertStmt->close();
        } else {
            $_SESSION['error_message'] = "Not enough pigs in the batch.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid input.";
    }
} else {
    $_SESSION['error_message'] = "Invalid request method.";
}

// Redirect back to the profile page with success or error message
header("Location: profile.php");
exit();
?>
