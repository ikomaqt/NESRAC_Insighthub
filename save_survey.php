<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['userid'])) {
    $userid = $_SESSION['userid'];

    // Establish a database connection
    $conn = new mysqli("localhost", "root", "", "nesrac");
    if ($conn->connect_error) {
        die('Connection Failed: ' . $conn->connect_error);
    }

    // Sanitize and validate input
    $total_count = intval($_POST['total_count']);
    $sow_count = intval($_POST['sow_count']);
    $pregnant_count = intval($_POST['pregnant_count']);
    $slaughter_count = intval($_POST['slaughter_count']);
    $slaughter_date = $_POST['slaughter_date'];

    // Insert survey data into the survey table
    $query = "INSERT INTO survey (userid, total_count, sow_count, pregnant_count, slaughter_count, slaughter_date, submitted_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiiiis", $userid, $total_count, $sow_count, $pregnant_count, $slaughter_count, $slaughter_date);
    $stmt->execute();
    $survey_id = $stmt->insert_id; // Get the inserted survey ID
    $stmt->close();

    // Handle dynamic pregnancy data
    for ($i = 1; $i <= $pregnant_count; $i++) {
        $expected_birth_date = $_POST['expected_birth_date_' . $i];
        $birth_date = $_POST['birth_date_' . $i];
        $male_piglets = intval($_POST['male_piglets_' . $i]);
        $female_piglets = intval($_POST['female_piglets_' . $i]);

        $pregnancy_query = "INSERT INTO pregnancy_data (survey_id, expected_birth_date, birth_date, male_piglets, female_piglets) VALUES (?, ?, ?, ?, ?)";
        $pregnancy_stmt = $conn->prepare($pregnancy_query);
        $pregnancy_stmt->bind_param("issii", $survey_id, $expected_birth_date, $birth_date, $male_piglets, $female_piglets);
        $pregnancy_stmt->execute();
        $pregnancy_stmt->close();
    }

    // Update the last_survey_date in the user table
    $update_query = "UPDATE user SET last_survey_date = NOW() WHERE userid = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $userid);
    $update_stmt->execute();
    $update_stmt->close();

    $conn->close();
    echo "Survey saved successfully!";
} else {
    echo "Unauthorized access!";
}
?>
