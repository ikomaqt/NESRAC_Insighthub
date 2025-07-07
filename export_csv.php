<?php
include("config.php");

// Fetch the export type and date range from the GET parameters
$type = $_GET['type'];
$dateRange = isset($_GET['dateRange']) ? explode(' to ', $_GET['dateRange']) : [date('Y-01-01'), date('Y-m-d')];

// Check if the date range has both start and end dates
if (count($dateRange) == 2) {
    $date_from = $dateRange[0];
    $date_to = $dateRange[1];
} else {
    // Default to current year if no date range is provided
    $date_from = date('Y-01-01');
    $date_to = date('Y-m-d');
}

// Set the headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $type . '_data_' . date('Y-m-d') . '.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Determine the data type and fetch the corresponding data
if ($type == 'orders') {
    // Write column headers for orders, including the user's name
    fputcsv($output, ['Order ID', 'User ID', 'Full Name', 'Total Amount', 'Payment Method', 'Order Status', 'Order Date', 'Tracking Number', 'Tracking Status', 'Delivery Date', 'Payment Status', 'Amount Paid']);

    // Fetch orders data with user's full name
    $sql = "SELECT o.order_id, o.userid, CONCAT(u.firstname, ' ', u.lastname) AS full_name, o.total_amount, o.payment_method, o.order_status, o.order_date, o.tracking_number, o.tracking_status, o.delivery_date, o.payment_status, o.amount_paid
            FROM orders o
            JOIN user u ON o.userid = u.userid
            WHERE o.order_date BETWEEN '$date_from' AND '$date_to'";
    $result = $conn->query($sql);

    // Write each row of data to the CSV
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    } else {
        echo "No records found for the given date range.";
    }

} elseif ($type == 'pig_sell_request') {
    // Write column headers for pig sell request, including the user's full name
    fputcsv($output, ['ID', 'User ID', 'Full Name', 'Piglet Count', 'Request Date', 'Status', 'Weight', 'Price', 'Processed At', 'Non-Member ID']);

    // Fetch pig sell request data with user's full name
    $sql = "SELECT psr.id, psr.userid, CONCAT(u.firstname, ' ', u.lastname) AS full_name, psr.piglet_count, psr.request_date, psr.status, psr.weight, psr.price, psr.processed_at, psr.non_member_id
            FROM pig_sell_requests psr
            JOIN user u ON psr.userid = u.userid
            WHERE psr.processed_at BETWEEN '$date_from' AND '$date_to'";
    
    $result = $conn->query($sql);

    // Write each row of data to the CSV
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    } else {
        echo "No records found for the given date range.";
    }
}

// Close the output stream
fclose($output);
exit;
?>
