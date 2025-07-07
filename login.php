<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Connect to the database
    $conn = new mysqli("localhost", "root", "", "nesrac");
    if ($conn->connect_error) {
        die('Connection Failed: ' . $conn->connect_error);
    } else {
        // Query to get the user account based on email from USER_Account table
        $stmt = $conn->prepare("SELECT ua.userID, ua.password, um.status, ua.emailAdd, u.lastName, u.firstName, u.middleName
                                FROM USER_Account ua 
                                JOIN USER_Membership um ON ua.userID = um.userID
                                JOIN USER u ON ua.userID = u.userID
                                WHERE ua.emailAdd = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt_result = $stmt->get_result();

        if ($stmt_result->num_rows > 0) {
            $data = $stmt_result->fetch_assoc();
            
            // Verify the password
            if (password_verify($password, $data['password'])) {
                // Set user session
                $_SESSION['userid'] = $data['userID'];
                $_SESSION['status'] = $data['status'];  // Store user status (e.g., 'Member', 'Non-Member')
                setcookie('loggedIn', 'true', time() + 3600, '/'); // Set cookie for 1 hour

                // Check if the user is a member
                if ($data['status'] === 'Member') {
                    // Query to check last_survey from USER_Assets table
                    $stmt_survey = $conn->prepare("SELECT last_survey FROM USER_Assets WHERE userID = ?");
                    $stmt_survey->bind_param("i", $data['userID']);
                    $stmt_survey->execute();
                    $stmt_survey->bind_result($last_survey_date);
                    $stmt_survey->fetch();
                    $stmt_survey->close();

                    $current_date = new DateTime(); // Current date
                    $survey_due = false;  // Flag to track if the survey should appear

                    // If no last_survey date, show survey immediately
                    if (is_null($last_survey_date)) {
                        $survey_due = true;
                    } else {
                        // Calculate the days since the last survey
                        $last_survey_date = new DateTime($last_survey_date);
                        $interval = $last_survey_date->diff($current_date);
                        if ($interval->days >= 7) {
                            $survey_due = true;
                        }
                    }

                    if ($survey_due) {
                        // Set flag to show survey in session
                        $_SESSION['show_survey'] = true;

                        // Update the last_survey date in the database for members only
                        $update_survey = $conn->prepare("UPDATE USER_Assets SET last_survey = NOW() WHERE userID = ?");
                        $update_survey->bind_param("i", $data['userID']);
                        $update_survey->execute();
                        $update_survey->close();
                    } else {
                        // Do not show the survey if not due
                        $_SESSION['show_survey'] = false;
                    }
                } else {
                    // If user is not a member, do not show or update the survey date
                    $_SESSION['show_survey'] = false;
                }

                // Return success response for the front-end (if using AJAX)
                $response = array("status" => "success", "message" => "Login Successful", "userid" => $data['userID']);
                echo json_encode($response);
            } else {
                // Invalid password
                $response = array("status" => "error", "message" => "Invalid Email or Password");
                echo json_encode($response);
            }
        } else {
            // Invalid email
            $response = array("status" => "error", "message" => "Invalid Email or Password");
            echo json_encode($response);
        }

        // Close database connections
        $stmt->close();
        $conn->close();
    }
}
?>
