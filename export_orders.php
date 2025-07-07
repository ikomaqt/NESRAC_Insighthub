<?php
include('config.php');

// Handle search query and date filters passed from the form
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';

// Construct the base SQL query without payment_method and tracking_number
$sql_order_history = "SELECT 
                        o.order_id,
                        o.order_date,
                        u.firstname,
                        u.lastname,
                        ua.emailAdd AS email,  -- Fetch email from user_account
                        p.total_amount,  -- Fetch total amount from payment
                        p.amount_paid,   -- Fetch amount paid from payment
                        p.payment_status, -- Fetch payment status from payment
                        o.order_status
                    FROM 
                        `order` o
                    JOIN 
                        user u ON o.userid = u.userID
                    JOIN 
                        user_account ua ON u.userID = ua.userID  -- Join user_account to get the email
                    JOIN 
                        payment p ON o.order_id = p.order_id  -- Join payment to get total_amount and amount_paid
                    WHERE 
                        (u.firstname LIKE ? 
                        OR u.lastname LIKE ? 
                        OR ua.emailAdd LIKE ?)";  // Search by email from user_account

// Add date filters if provided
if ($start_date) {
    $sql_order_history .= " AND o.order_date >= ?";
}
if ($end_date) {
    $sql_order_history .= " AND o.order_date <= ?";
}

// Add payment status filter if provided
if ($payment_status) {
    $sql_order_history .= " AND p.payment_status = ?";
}

// Append the ORDER BY clause to sort results
$sql_order_history .= " ORDER BY o.order_date DESC";

// Prepare the SQL statement
$stmt = $conn->prepare($sql_order_history);

// Setup the search query parameter
$search_param = '%' . $search_query . '%';

// Initialize parameter types and the array of parameters
$types = "sss"; // Three strings for the search query (firstname, lastname, email)
$params = [$search_param, $search_param, $search_param];

// Add date filter parameters if provided
if ($start_date) {
    $types .= 's';
    $params[] = $start_date;
}
if ($end_date) {
    $types .= 's';
    $params[] = $end_date;
}

// Add payment status parameter if provided
if ($payment_status) {
    $types .= 's';
    $params[] = $payment_status;
}

// Bind parameters dynamically based on provided filters
$stmt->bind_param($types, ...$params);

// Execute the query
$stmt->execute();
$result_order_history = $stmt->get_result();

// Prepare CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=order_history.csv');

// Open output stream to write CSV data
$output = fopen('php://output', 'w');

// Write CSV column headings (without tracking_number)
fputcsv($output, array('Order ID', 'Order Date', 'User', 'Email', 'Total Amount', 'Amount Paid', 'Payment Status', 'Order Status'));

// Fetch and write each row of the query result
while ($row = $result_order_history->fetch_assoc()) {
    fputcsv($output, array(
        $row['order_id'],
        $row['order_date'],
        $row['firstname'] . ' ' . $row['lastname'],
        $row['email'],  // Correctly fetch email from user_account
        number_format($row['total_amount'], 2),  // Fetch total amount from payment
        number_format($row['amount_paid'], 2),   // Fetch amount paid from payment
        $row['payment_status'],   // Fetch payment status from payment
        $row['order_status']
    ));
}

// Close the output stream
fclose($output);
?>
