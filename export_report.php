<?php
include("config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';

    // Validate and sanitize input
    $date_from = filter_var($date_from, FILTER_SANITIZE_STRING);
    $date_to = filter_var($date_to, FILTER_SANITIZE_STRING);

    if (!DateTime::createFromFormat('Y-m-d', $date_from) || !DateTime::createFromFormat('Y-m-d', $date_to)) {
        die("Invalid date format. Please use 'Y-m-d'.");
    }

    // Use timestamps for queries
    $date_from .= ' 00:00:00';
    $date_to .= ' 23:59:59';

    // Proceed with data export logic
    // Fetch data based on sanitized and validated $date_from and $date_to





    // Sanitize inputs
    $date_from = filter_var($date_from, FILTER_SANITIZE_STRING);
    $date_to = filter_var($date_to, FILTER_SANITIZE_STRING);

    // Validate dates
    if (!DateTime::createFromFormat('Y-m-d H:i:s', $date_from)) {
        die("Invalid date_from format: $date_from");
    }
    if (!DateTime::createFromFormat('Y-m-d H:i:s', $date_to)) {
        die("Invalid date_to format: $date_to");
    }

    // Initialize totals
    $total_sales = 0;
    $total_investment = 0;
    $total_bought_pigs = 0;
    $bought_pigs = [];
    $order_data = [];

    // Fetch "Paid" or "Partial" orders
    $sql_orders = "SELECT o.order_id, o.order_date, p.amount_paid, p.payment_status, 
                          u.firstname, u.lastname 
                   FROM `order` o
                   JOIN user u ON o.userid = u.userID
                   JOIN payment p ON o.order_id = p.order_id
                   WHERE p.payment_status IN ('Paid', 'Partial') 
                   AND o.order_date BETWEEN ? AND ?";
    $stmt_orders = $conn->prepare($sql_orders);
    $stmt_orders->bind_param('ss', $date_from, $date_to);
    $stmt_orders->execute();
    $result_orders = $stmt_orders->get_result();

    while ($row = $result_orders->fetch_assoc()) {
        $total_sales += $row['amount_paid'];
        $order_data[] = $row;
    }

    // Fetch investment and bought pigs data
    $sql_investment = "SELECT u.firstname, u.lastname, psr.batch_number, psr.price, psr.processed_at, psr.piglet_count 
                       FROM pig_sell_requests psr
                       JOIN pig_survey ps ON psr.survey_id = ps.id
                       JOIN user u ON ps.userid = u.userID
                       WHERE psr.status = 'accepted' 
                       AND psr.processed_at BETWEEN ? AND ?";
    $stmt_investment = $conn->prepare($sql_investment);
    $stmt_investment->bind_param('ss', $date_from, $date_to);
    $stmt_investment->execute();
    $result_investment = $stmt_investment->get_result();

    while ($row = $result_investment->fetch_assoc()) {
        $total_investment += $row['price'];
        $total_bought_pigs += $row['piglet_count'];
        $bought_pigs[] = $row;
    }

    // Calculate net profit
    $net_profit = $total_sales - $total_investment - (500 * $total_bought_pigs);
    $net_profit = max($net_profit, 0);

    if (!empty($order_data) || !empty($bought_pigs)) {
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="report_' . date('Y-m-d', strtotime($date_from)) . '_to_' . date('Y-m-d', strtotime($date_to)) . '.csv"');

        // Add BOM for UTF-8 compatibility
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // Orders Section
        fputcsv($output, ['Order Report']);
        fputcsv($output, ['Order ID', 'Order Date', 'User', 'Amount Paid', 'Payment Status']);
        foreach ($order_data as $order) {
            fputcsv($output, [
                $order['order_id'],
                $order['order_date'],
                $order['firstname'] . ' ' . $order['lastname'],
                '₱' . number_format($order['amount_paid'], 2),
                $order['payment_status']
            ]);
        }

        // Bought Pigs Section
        if (!empty($bought_pigs)) {
            fputcsv($output, []); // Blank row
            fputcsv($output, ['Bought Pigs Report']);
            fputcsv($output, ['Seller Name', 'Batch Number', 'Piglet Count', 'Price', 'Processed Date']);
            foreach ($bought_pigs as $pig) {
                fputcsv($output, [
                    $pig['firstname'] . ' ' . $pig['lastname'],
                    $pig['batch_number'],
                    $pig['piglet_count'],
                    '₱' . number_format($pig['price'], 2),
                    $pig['processed_at']
                ]);
            }
        }

        // Summary Section
        fputcsv($output, []); // Blank row
        fputcsv($output, ['Summary Report']);
        fputcsv($output, ['Total Sales', '₱' . number_format($total_sales, 2)]);
        fputcsv($output, ['Total Investment', '₱' . number_format($total_investment, 2)]);
        fputcsv($output, ['Total Bought Pigs', $total_bought_pigs]);
        fputcsv($output, ['Net Profit', '₱' . number_format($net_profit, 2)]);

        fclose($output);
        exit;
    } else {
        echo "No data available for the selected date range.";
    }
}
?>
