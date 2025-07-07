<?php
session_start();
include("config.php");

// Check if the user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data from the database and store it in the session (if not already set)
$user_id = $_SESSION['userid'];

// Fetch user information and membership status
$query = "SELECT firstname, middlename, lastname, contactNumber, um.status AS membership_status
          FROM user u
          JOIN user_membership um ON u.userid = um.userID
          WHERE u.userid = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $_SESSION['firstname'] = $user['firstname'];
    $_SESSION['middlename'] = $user['middlename'];
    $_SESSION['lastname'] = $user['lastname'];
    $_SESSION['contactNumber'] = $user['contactNumber'];
    $_SESSION['membership_status'] = $user['membership_status'];  // Store membership status in session
} else {
    header("Location: login.php");
    exit();
}

$stmt->close();

// Restrict non-members from accessing this page
if ($_SESSION['membership_status'] != 'Member') {
    header("Location: non_member.php");  // Redirect to a non-member page
    exit();
}

// Initialize variables to store survey data
$total_piglets = $batch_count = $pregnant_females = $sows = $boars = $total_pigs = 0;
$batch_details = [];
$pregnancy_details = [];

// Fetch the most recent survey data
$query_survey = "SELECT id, total_piglets, batch_count, pregnant_females, sows, boars, total_pigs FROM pig_survey WHERE userid = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($query_survey);
$stmt->bind_param("i", $_SESSION['userid']);
$stmt->execute();
$stmt->bind_result($survey_id, $total_piglets, $batch_count, $pregnant_females, $sows, $boars, $total_pigs);
$stmt->fetch();
$stmt->close();

// Fetch batch details if survey exists
if ($survey_id) {
    $query_batch = "SELECT batch_number, piglet_count, birthdate FROM batch_details WHERE survey_id = ?";
    $stmt = $conn->prepare($query_batch);
    $stmt->bind_param("i", $survey_id);
    $stmt->execute();
    $result_batch = $stmt->get_result();

    while ($row = $result_batch->fetch_assoc()) {
        $batch_details[] = $row;
    }
    $stmt->close();

    // Fetch pregnancy details
    $query_pregnancy = "SELECT female_number, pregnancy_date FROM pregnancy_details WHERE survey_id = ?";
    $stmt = $conn->prepare($query_pregnancy);
    $stmt->bind_param("i", $survey_id);
    $stmt->execute();
    $result_pregnancy = $stmt->get_result();

    while ($row = $result_pregnancy->fetch_assoc()) {
        $pregnancy_details[] = $row;
    }
    $stmt->close();
}

// Handle form submission
$success_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userid = $_SESSION['userid'];
    $firstname = $_SESSION['firstname'];
    $middlename = $_SESSION['middlename'];
    $lastname = $_SESSION['lastname'];
    $contactNumber = $_SESSION['contactNumber'];
    $total_piglets = isset($_POST['piglets_total']) ? intval($_POST['piglets_total']) : 0;
    $batch_count = isset($_POST['batch_count']) ? intval($_POST['batch_count']) : 0;
    $pregnant_females = isset($_POST['pregnant_count']) ? intval($_POST['pregnant_count']) : 0;
    $sows = isset($_POST['sows']) ? intval($_POST['sows']) : 0;
    $boars = isset($_POST['boars']) ? intval($_POST['boars']) : 0;
    $total_pigs = isset($_POST['total_pigs']) ? intval($_POST['total_pigs']) : 0;

    // Insert into pig_survey table
    $stmt = $conn->prepare("INSERT INTO pig_survey (userid, total_piglets, batch_count, pregnant_females, sows, boars, total_pigs) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiiiii", $userid, $total_piglets, $batch_count, $pregnant_females, $sows, $boars, $total_pigs);
    $stmt->execute();

    // Get the last inserted survey ID
    $survey_id = $stmt->insert_id;
    $stmt->close();

    // Insert batch details into batch_details table
    for ($i = 1; $i <= $batch_count; $i++) {
        $batch_number = $i;
        $piglet_count = isset($_POST["piglets_batch_$i"]) ? intval($_POST["piglets_batch_$i"]) : null;
        $birthdate = isset($_POST["piglet_birthdate_$i"]) ? $_POST["piglet_birthdate_$i"] : null;

        if ($piglet_count !== null && $birthdate !== null) {
            $stmt = $conn->prepare("INSERT INTO batch_details (survey_id, batch_number, piglet_count, birthdate) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $survey_id, $batch_number, $piglet_count, $birthdate);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Insert pregnancy details into pregnancy_details table
    for ($i = 1; $i <= $pregnant_females; $i++) {
        $female_number = $i;
        $pregnancy_date = isset($_POST["pregnancy_date_$i"]) ? $_POST["pregnancy_date_$i"] : null;

        if ($pregnancy_date !== null) {
            $stmt = $conn->prepare("INSERT INTO pregnancy_details (survey_id, female_number, pregnancy_date) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $survey_id, $female_number, $pregnancy_date);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Success message to trigger SweetAlert
    $success_message = 'Survey submitted successfully!';
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pig Survey Form</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* General Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        form {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            animation: fadeIn 0.5s ease-in-out;
        }

        button {
            margin: 20px;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        h2 {
            font-size: 1.8em;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }

        label {
            font-weight: bold;
            margin-bottom: 8px;
            display: block;
            color: #555;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            transition: border-color 0.3s ease-in-out;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="date"]:focus {
            border-color: #007bff;
            outline: none;
        }

        input[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1em;
            width: 100%;
            transition: background-color 0.3s ease-in-out;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .success-message {
            font-size: 1.2em;
            color: green;
            margin-top: 20px;
        }

        .member-info-card {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #ddd;
        }

        .member-info-card input {
            background-color: #e9ecef;
            border: none;
            color: #495057;
            font-weight: bold;
        }

        .member-info-card input[readonly] {
            cursor: not-allowed;
        }

        .member-info-header {
            grid-column: span 2;
            text-align: center;
            font-size: 1.6em;
            color: #007bff;
            margin-bottom: 20px;
        }

        fieldset {
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        legend {
            padding: 0 15px;
            font-weight: bold;
            color: #333;
        }

        @media (max-width: 768px) {
            form {
                padding: 20px;
                max-width: 100%;
            }

            h2 {
                font-size: 1.5em;
            }

            input[type="submit"] {
                font-size: 1em;
            }

            .member-info-card {
                grid-template-columns: 1fr;
            }

            .member-info-header {
                font-size: 1.4em;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <script>
        function goHome() {
            window.location.href = 'homepage.php';  // Replace 'home.php' with your actual home page URL
        }

        function updateBatchFields() {
            const batchCount = document.getElementById("batch_count").value;
            const batchFieldsContainer = document.getElementById("batch_fields_container");
            batchFieldsContainer.innerHTML = "";  // Clear existing fields

            for (let i = 1; i <= batchCount; i++) {
                const batchFieldSet = document.createElement("fieldset");
                const legend = document.createElement("legend");
                legend.innerText = `Batch ${i}`;
                batchFieldSet.appendChild(legend);

                const numberLabel = document.createElement("label");
                numberLabel.innerText = `Number of Piglets in Batch ${i}: `;
                const numberInput = document.createElement("input");
                numberInput.type = "number";
                numberInput.name = `piglets_batch_${i}`;
                numberInput.required = true;
                numberInput.oninput = updateTotalPiglets;
                batchFieldSet.appendChild(numberLabel);
                batchFieldSet.appendChild(numberInput);
                batchFieldSet.appendChild(document.createElement("br"));

                const birthdateLabel = document.createElement("label");
                birthdateLabel.innerText = `Birthdate of Batch ${i}: `;
                const birthdateInput = document.createElement("input");
                birthdateInput.type = "date";
                birthdateInput.name = `piglet_birthdate_${i}`;
                birthdateInput.required = true;
                batchFieldSet.appendChild(birthdateLabel);
                batchFieldSet.appendChild(birthdateInput);
                batchFieldSet.appendChild(document.createElement("br"));

                batchFieldsContainer.appendChild(batchFieldSet);
            }

            updateTotalPiglets();  // Update total piglets after adding batches
        }

        function updateTotalPiglets() {
            const batchCount = document.getElementById("batch_count").value;
            let totalPiglets = 0;

            for (let i = 1; i <= batchCount; i++) {
                const pigletsInBatch = document.getElementsByName(`piglets_batch_${i}`)[0].value;
                totalPiglets += parseInt(pigletsInBatch) || 0;  // Add the number of piglets, default to 0 if empty
            }

            document.getElementById("piglets_total").value = totalPiglets;
            updateTotalPigs();  // Update the total pigs whenever the piglet count changes
        }

        function updatePregnancyFields() {
            const pregnantCount = document.getElementById("pregnant_count").value;
            const pregnancyFieldsContainer = document.getElementById("pregnancy_fields_container");
            pregnancyFieldsContainer.innerHTML = "";  // Clear existing fields

            for (let i = 1; i <= pregnantCount; i++) {
                const pregnancyFieldSet = document.createElement("fieldset");
                const legend = document.createElement("legend");
                legend.innerText = `Pregnant Pig ${i}`;
                pregnancyFieldSet.appendChild(legend);

                const pregnancyDateLabel = document.createElement("label");
                pregnancyDateLabel.innerText = `Pregnancy Date for Female ${i}: `;
                const pregnancyDateInput = document.createElement("input");
                pregnancyDateInput.type = "date";
                pregnancyDateInput.name = `pregnancy_date_${i}`;
                pregnancyDateInput.required = false;
                pregnancyFieldSet.appendChild(pregnancyDateLabel);
                pregnancyFieldSet.appendChild(pregnancyDateInput);
                pregnancyFieldSet.appendChild(document.createElement("br"));

                pregnancyFieldsContainer.appendChild(pregnancyFieldSet);
            }
        }

        function updateTotalPigs() {
            const sows = parseInt(document.getElementsByName('sows')[0].value) || 0;
            const boars = parseInt(document.getElementsByName('boars')[0].value) || 0;
            const piglets = parseInt(document.getElementById('piglets_total').value) || 0;

            const totalPigs = sows + boars + piglets;
            document.getElementsByName('total_pigs')[0].value = totalPigs;
        }

        document.addEventListener('DOMContentLoaded', function () {
            <?php if ($success_message): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?php echo $success_message; ?>',
            });
            <?php endif; ?>

            updatePregnancyFields();
        });
    </script>
</head>
<body>
    <!-- Back to Home Button -->
  

    <form method="post" action="">
        <!-- Back to Home Button -->
<a href="homepage.php" class="btn" style="display: inline-block; margin: 20px; padding: 10px; background-color: #007bff; color: white; border-radius: 5px; text-decoration: none;">← Back to Home</a>


        <h2>Member Information</h2>
        <div class="member-info-card">
            <div>
                <label>First Name:</label>
                <input type="text" name="firstname" value="<?php echo htmlspecialchars($_SESSION['firstname']); ?>" readonly>
            </div>
            <div>
                <label>Last Name:</label>
                <input type="text" name="lastname" value="<?php echo htmlspecialchars($_SESSION['lastname']); ?>" readonly>
            </div>
            <div>
                <label>Contact Number:</label>
                <input type="text" name="contactNumber" value="<?php echo htmlspecialchars($_SESSION['contactNumber']); ?>" readonly>
            </div>
        </div>

        <h2>Piglets (0-8 weeks)</h2>
        How many batches of piglets do you have?
        <input type="number" id="batch_count" name="batch_count" min="1" max="10" required onchange="updateBatchFields()" value="<?php echo htmlspecialchars($batch_count); ?>">
        <br><br>

        <div id="batch_fields_container">
            <!-- Batch fields will be dynamically generated here -->
        </div>

        Total Number of Piglets: <input type="number" id="piglets_total" name="piglets_total" readonly required value="<?php echo htmlspecialchars($total_piglets); ?>"><br><br>
<h2>Pregnant  Pigs</h2>
How many pregnant pigs do you have?
<input type="number" id="pregnant_count" name="pregnant_count" min="0" max="10" onchange="updatePregnancyFields()" value="<?php echo htmlspecialchars($pregnant_females); ?>">
<br><br>

<div id="pregnancy_fields_container">
    <!-- Pregnancy fields will be dynamically generated here -->
</div>

<h2>Current Inventory</h2>
Sows (Adult Female Pigs): <input type="number" name="sows" oninput="updateTotalPigs()" value="<?php echo htmlspecialchars($sows); ?>"><br><br>
Boars (Adult Male Pigs): <input type="number" name="boars" oninput="updateTotalPigs()" value="<?php echo htmlspecialchars($boars); ?>"><br><br>
Total Number of Pigs: <input type="number" name="total_pigs" readonly value="<?php echo htmlspecialchars($total_pigs); ?>"><br><br>


        <input type="submit" value="Submit">
    </form>

</body>

</html>
