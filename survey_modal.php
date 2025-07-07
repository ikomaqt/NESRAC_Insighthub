<?php
session_start();
include("config.php");

$userid = $_SESSION['userid'];

// Initialize variables
$total_count = $sow_count = $pregnant_count = $slaughter_count = $slaughter_date = "";
$pregnancies = [];

// Fetch the last survey data if available
$stmt = $conn->prepare("SELECT total_count, sow_count, pregnant_count, slaughter_count, slaughter_date FROM survey WHERE userid = ? ORDER BY submitted_at DESC LIMIT 1");
$stmt->bind_param("i", $userid);
$stmt->execute();
$stmt->bind_result($total_count, $sow_count, $pregnant_count, $slaughter_count, $slaughter_date);
$stmt->fetch();
$stmt->close();

// Fetch the pregnancy data if there is a last survey
if ($pregnant_count > 0) {
    $stmt = $conn->prepare("SELECT expected_birth_date, birth_date, male_piglets, female_piglets FROM pregnancy_data WHERE survey_id = (SELECT id FROM survey WHERE userid = ? ORDER BY submitted_at DESC LIMIT 1)");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $pregnancies[] = $row;
    }
    $stmt->close();
}

// Check if the survey should be shown
$showSurvey = isset($_SESSION['show_survey']) && $_SESSION['show_survey'];

// Close the connection after fetching data
$conn->close();
?>

<?php if ($showSurvey): ?>
<div id="survey-modal" style="display: none;">
    <div class="modal-content">
        <h2>Survey</h2>
        <form id="survey-form" action="save_survey.php" method="post" onsubmit="submitSurvey(event)">
            <div class="input-group">
                <label for="total_count">Total na Baboy na Alaga:</label>
                <input type="number" id="total_count" name="total_count" value="<?php echo htmlspecialchars($total_count); ?>" required>
            </div>

            <div class="input-group">
                <label for="sow_count">SOW Count (Inahing Baboy):</label>
                <input type="number" id="sow_count" name="sow_count" value="<?php echo htmlspecialchars($sow_count); ?>" required>
            </div>

            <div class="input-group">
                <label for="pregnant_count">How Many are Pregnant:</label>
                <input type="number" id="pregnant_count" name="pregnant_count" min="0" value="<?php echo htmlspecialchars($pregnant_count); ?>" required oninput="generatePregnancyFields(this.value)">
            </div>

            <!-- Container for dynamic pregnancy fields -->
            <div id="pregnancy-fields"></div>

            <div class="input-group">
                <label for="slaughter_count">Ilan ang Pang Katay (Count):</label>
                <input type="number" id="slaughter_count" name="slaughter_count" value="<?php echo htmlspecialchars($slaughter_count); ?>" required>
            </div>

            <div class="input-group">
                <label for="slaughter_date">Expected Date ng Katay:</label>
                <input type="date" id="slaughter_date" name="slaughter_date" value="<?php echo htmlspecialchars($slaughter_date); ?>" required>
            </div>

            <button type="submit">Submit</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (<?php echo json_encode($showSurvey); ?>) {
            openModal(); // Function to open the survey modal
            generatePregnancyFields(<?php echo json_encode($pregnant_count); ?>); // Pre-generate fields with existing data
        }
    });

    function openModal() {
        document.getElementById('survey-modal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('survey-modal').style.display = 'none';
    }

    function submitSurvey(event) {
        event.preventDefault(); // Prevent the form from submitting normally

        var form = document.getElementById('survey-form');
        var formData = new FormData(form);

        var xhr = new XMLHttpRequest();
        xhr.open("POST", form.action, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                closeModal(); // Close the modal after successful submission
                alert('Survey submitted successfully!');
            } else {
                alert('Error saving data: ' + xhr.statusText);
            }
        };
        xhr.send(formData);
    }

    function generatePregnancyFields(count) {
        const container = document.getElementById('pregnancy-fields');
        container.innerHTML = ''; // Clear existing fields

        const pregnancies = <?php echo json_encode($pregnancies); ?>;

        for (let i = 1; i <= count; i++) {
            const pregnancyData = pregnancies[i - 1] || {expected_birth_date: '', birth_date: '', male_piglets: '', female_piglets: ''};

            const fieldset = document.createElement('fieldset');
            fieldset.innerHTML = `
                <legend>Pregnant Sow ${i}</legend>
                <div class="input-group">
                    <label for="expected_birth_date_${i}">Expected Date of Giving Birth:</label>
                    <input type="date" id="expected_birth_date_${i}" name="expected_birth_date_${i}" value="${pregnancyData.expected_birth_date}" required>
                </div>
                <div class="input-group">
                    <label for="birth_date_${i}">Nakapanganak na (Date):</label>
                    <input type="date" id="birth_date_${i}" name="birth_date_${i}" value="${pregnancyData.birth_date}">
                </div>
                <div class="input-group">
                    <label for="male_piglets_${i}">How Many Male Piglets:</label>
                    <input type="number" id="male_piglets_${i}" name="male_piglets_${i}" min="0" value="${pregnancyData.male_piglets}" required>
                </div>
                <div class="input-group">
                    <label for="female_piglets_${i}">How Many Female Piglets:</label>
                    <input type="number" id="female_piglets_${i}" name="female_piglets_${i}" min="0" value="${pregnancyData.female_piglets}" required>
                </div>
            `;
            container.appendChild(fieldset);
        }
    }
</script>
<?php endif; ?>
