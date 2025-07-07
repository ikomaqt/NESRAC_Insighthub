<?php
session_start();
include("config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = intval($_POST['request_id']); // Get the request ID from the form data

    // Get the piglet count and batch number from the request
    $query = "SELECT piglet_count, batch_number, survey_id FROM pig_sell_requests WHERE id = ? AND status = 'pending'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $stmt->bind_result($piglet_count, $batch_number, $survey_id);
    
    if ($stmt->fetch()) {
        $stmt->close();

        // Update the pig count in the batch
        $updateBatchQuery = "UPDATE batch_details SET piglet_count = piglet_count + ? WHERE batch_number = ? AND survey_id = ?";
        $updateStmt = $conn->prepare($updateBatchQuery);
        $updateStmt->bind_param("iii", $piglet_count, $batch_number, $survey_id);
        $updateStmt->execute();
        $updateStmt->close();

        // Mark the sell request as declined
        $declineQuery = "UPDATE pig_sell_requests SET status = 'declined' WHERE id = ?";
        $declineStmt = $conn->prepare($declineQuery);
        $declineStmt->bind_param("i", $request_id);
        if ($declineStmt->execute()) {
            $_SESSION['success_message'] = "Sell request declined and pigs restored to the batch.";
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to decline the request.']);
        }
        $declineStmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Sell request not found or already processed.']);
        $stmt->close();
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}

$conn->close();
?>
