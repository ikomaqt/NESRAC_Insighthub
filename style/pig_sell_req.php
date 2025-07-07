<?php
session_start();
include("config.php");

// Check if the user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userid = $_SESSION['userid'];
    $batch_number = $_POST['batch_number'];

    // Validate and ensure sell_count and birthdate are not empty
    $sell_count = isset($_POST['sell_count']) ? intval($_POST['sell_count']) : 0;
    $birthdate = isset($_POST['birthdate']) ? $_POST['birthdate'] : null;

    if ($sell_count > 0 && $birthdate) {
        // Get the survey ID based on the user's ID
        $surveyStmt = $conn->prepare("SELECT id FROM pig_survey WHERE userid = ? ORDER BY created_at DESC LIMIT 1");
        $surveyStmt->bind_param("i", $userid);
        $surveyStmt->execute();
        $surveyStmt->bind_result($survey_id);
        $surveyStmt->fetch();
        $surveyStmt->close();

        if ($survey_id) {
            // Insert the sell request into the database
            $stmt = $conn->prepare("INSERT INTO pig_sell_requests (survey_id, batch_number, piglet_count, birthdate, request_date) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("iiis", $survey_id, $batch_number, $sell_count, $birthdate);
            $stmt->execute();
            $stmt->close();

            header("Location: profile.php?message=Sell+request+submitted");
            exit();
        } else {
            echo "Error: Unable to find survey for this user.";
        }
    } else {
        echo "Invalid input: Please provide a valid piglet count and birthdate.";
    }
}

$conn->close();
?>
