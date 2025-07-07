<?php
include("config.php");

if (isset($_POST['pig_id'])) {
    $pig_id = $_POST['pig_id'];

    // Fetch the meat produced details from the accepted_pigs table
    $query = "SELECT meat_produced FROM accepted_pigs WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $pig_id);
    $stmt->execute();
    $stmt->bind_result($meat_produced);
    $stmt->fetch();

    $response = array(
        'meat_produced' => $meat_produced
    );

    echo json_encode($response);
    $stmt->close();
}
?>
