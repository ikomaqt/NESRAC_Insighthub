<?php
include('config.php');
session_start();

$userid = $_SESSION['userid']; // Assume user ID is stored in session
$field = $_POST['field'];
$value = $_POST['value'];

// Map field names to database columns
$fieldMap = [
    'lastName' => 'lastName',
    'firstName' => 'firstName',
    'middleName' => 'middleName',
    'bday' => 'bday',
    'gender' => 'gender',
    'street' => 'street',
    'municipality' => 'municipality',
    'barangay' => 'barangay'
];

// Ensure the field is valid
if (array_key_exists($field, $fieldMap)) {
    // Prepare the SQL query
    $table = ($field == 'municipality' || $field == 'barangay' || $field == 'street') ? 'USER_Location' : 'USER';
    $column = $fieldMap[$field];

    // Update query
    $query = "UPDATE $table SET $column = ? WHERE userid = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('si', $value, $userid);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid field.']);
}
?>
