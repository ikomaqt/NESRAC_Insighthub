<?php
session_start();
include("config.php");
include("admin.php");

// Define date range (last 7 days by default or GET params if set)
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] . ' 00:00:00' : date('Y-m-d', strtotime('-7 days')) . ' 00:00:00';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] . ' 23:59:59' : date('Y-m-d') . ' 23:59:59';

// Validate and sanitize dates
if (!DateTime::createFromFormat('Y-m-d H:i:s', $date_from) || !DateTime::createFromFormat('Y-m-d H:i:s', $date_to)) {
    die("Invalid date format provided.");
}

// Generate last 7 days array (date only, no time)
$last7Days = [];
$period = new DatePeriod(new DateTime(substr($date_from, 0, 10)), new DateInterval('P1D'), (new DateTime(substr($date_to, 0, 10)))->modify('+1 day'));
foreach ($period as $date) {
    $last7Days[] = $date->format('Y-m-d');
}

// Database query function with error handling
function fetch_data($conn, $sql, $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }
    if (!empty($params)) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}


// Fetch total sales (sum of paid and partially paid amounts)
$sql_total_sales = "SELECT SUM(p.amount_paid) AS total_sales 
                    FROM `order` o 
                    JOIN payment p ON o.order_id = p.order_id 
                    WHERE p.payment_status IN ('Paid', 'Partial') 
                    AND o.order_date BETWEEN '$date_from' AND '$date_to'";
$result_total_sales = fetch_data($conn, $sql_total_sales);
$total_sales = $result_total_sales->fetch_assoc()['total_sales'] ?? 0;

// Fetch total number of orders
$sql_total_order = "SELECT COUNT(order_id) AS total_order 
                    FROM `order` 
                    WHERE order_date BETWEEN '$date_from' AND '$date_to'";
$result_total_order = fetch_data($conn, $sql_total_order);
$total_order = $result_total_order->fetch_assoc()['total_order'] ?? 0;

// Fetch total buy price (sum of pig purchases)
$sql_total_buy_price = "SELECT SUM(price) AS total_buy_price 
                        FROM pig_sell_requests 
                        WHERE status = 'accepted' 
                        AND processed_at BETWEEN '$date_from' AND '$date_to'";
$result_total_buy_price = fetch_data($conn, $sql_total_buy_price);
$total_buy_price = $result_total_buy_price->fetch_assoc()['total_buy_price'] ?? 0;

// Fetch total bought pigs (sum of piglet counts)
$sql_total_bought_pigs = "SELECT SUM(piglet_count) AS total_bought_pigs
                          FROM pig_sell_requests
                          WHERE status = 'accepted'
                          AND processed_at BETWEEN '$date_from' AND '$date_to'";
$result_total_bought_pigs = fetch_data($conn, $sql_total_bought_pigs);
$total_bought_pigs = $result_total_bought_pigs->fetch_assoc()['total_bought_pigs'] ?? 0;

// Calculate net profit with ₱500 deduction per pig bought
$net_profit = $total_sales - $total_buy_price - (500 * $total_bought_pigs);
$net_profit = max($net_profit, 0);
// Fetch top pig sellers
$sql_top_pig_seller = "SELECT u.firstname, u.lastname, SUM(psr.piglet_count) AS total_pigs_sold
                       FROM pig_sell_requests psr
                       JOIN pig_survey ps ON psr.survey_id = ps.id
                       JOIN user u ON ps.userid = u.userID
                       WHERE psr.status = 'accepted'
                       AND psr.processed_at BETWEEN '$date_from' AND '$date_to'
                       GROUP BY ps.userid
                       ORDER BY total_pigs_sold DESC
                       LIMIT 5";

$result_top_pig_seller = fetch_data($conn, $sql_top_pig_seller);
$pig_sellers = [];
$pigs_sold = [];
while ($row = $result_top_pig_seller->fetch_assoc()) {
    $pig_sellers[] = $row['firstname'] . ' ' . $row['lastname'];
    $pigs_sold[] = $row['total_pigs_sold'];
}

// Fetch payment status breakdown
$sql_payment_status = "SELECT 
                        SUM(CASE WHEN p.payment_status = 'Paid' THEN 1 ELSE 0 END) AS paid,
                        SUM(CASE WHEN p.payment_status = 'Unpaid' THEN 1 ELSE 0 END) AS unpaid,
                        SUM(CASE WHEN p.payment_status = 'Partial' THEN 1 ELSE 0 END) AS partial
                       FROM `order` o
                       JOIN payment p ON o.order_id = p.order_id
                       WHERE o.order_date BETWEEN '$date_from' AND '$date_to'";
$result_payment_status = fetch_data($conn, $sql_payment_status);
$payment_status = $result_payment_status->fetch_assoc() ?? ['paid' => 0, 'unpaid' => 0, 'partial' => 0];

$sql_sales = "SELECT DATE(o.order_date) AS sale_date, SUM(p.amount_paid) AS total_sales
              FROM `order` o
              JOIN payment p ON o.order_id = p.order_id
              WHERE p.payment_status IN ('Paid', 'Partial')
              AND o.order_date BETWEEN '$date_from' AND '$date_to'
              GROUP BY sale_date";
$result_sales = fetch_data($conn, $sql_sales);
$salesData = array_fill_keys($last7Days, 0);
while ($row = $result_sales->fetch_assoc()) {
    $salesData[$row['sale_date']] = $row['total_sales'];
}

// Fetch buy price data and fill missing dates
$sql_buy_price = "SELECT DATE(psr.processed_at) AS process_date, SUM(psr.price) AS total_buy_price
                  FROM pig_sell_requests psr
                  WHERE psr.status = 'accepted'
                  AND psr.processed_at BETWEEN '$date_from' AND '$date_to'
                  GROUP BY process_date";
$result_buy_price = fetch_data($conn, $sql_buy_price);
$buyPriceData = array_fill_keys($last7Days, 0);
while ($row = $result_buy_price->fetch_assoc()) {
    $buyPriceData[$row['process_date']] = $row['total_buy_price'];
}

// Convert data to JSON for JavaScript
$salesDataJson = json_encode(array_values($salesData));
$buyPriceDataJson = json_encode(array_values($buyPriceData));
$last7DaysJson = json_encode($last7Days);

// Map sales data to last 7 days, filling missing dates with 0
$salesData = array_fill_keys($last7Days, 0);
while ($row = $result_sales->fetch_assoc()) {
    $salesData[$row['sale_date']] = $row['total_sales'];
}


// Fetch order volume data with proper date grouping
$sql_order_volume = "SELECT DATE_FORMAT(order_date, '%Y-%m-%d') AS order_day, 
                            COUNT(order_id) AS total_order
                     FROM `order`
                     WHERE order_date BETWEEN '$date_from' AND '$date_to' 
                     GROUP BY order_day";
$result_order_volume = fetch_data($conn, $sql_order_volume);
$order_volume_data = [];
while ($row = $result_order_volume->fetch_assoc()) {
    $order_volume_data[$row['order_day']] = $row['total_order'];
}

// Fetch user growth data with valid date format
$sql_user_growth = "SELECT DATE_FORMAT(um.created_at, '%Y-%m-%d') AS registration_day, 
                           COUNT(um.userID) AS new_users 
                    FROM user_membership um
                    WHERE um.created_at BETWEEN '$date_from' AND '$date_to' 
                    GROUP BY registration_day";
$result_user_growth = fetch_data($conn, $sql_user_growth);
$user_growth_data = [];
while ($row = $result_user_growth->fetch_assoc()) {
    $user_growth_data[$row['registration_day']] = $row['new_users'];
}
// Fetch top pig sell requests processed, ranked by piglet count
$sql_top_pig_sell_requests = "SELECT u.firstname, u.lastname, SUM(psr.piglet_count) AS total_pigs_sold
                              FROM pig_sell_requests psr
                              JOIN pig_survey ps ON psr.survey_id = ps.id
                              JOIN user u ON ps.userid = u.userID
                              WHERE psr.status = 'accepted' AND psr.is_processed = 1
                              GROUP BY ps.userid
                              ORDER BY total_pigs_sold DESC
                              LIMIT 5";

$result_top_pig_sell_requests = fetch_data($conn, $sql_top_pig_sell_requests);


// Fetch total bought price (sum of price for accepted and processed requests)
$sql_total_bought_price = "
    SELECT SUM(price) AS total_bought_price 
    FROM pig_sell_requests 
    WHERE status = 'accepted' 
    AND processed_at BETWEEN '$date_from' AND '$date_to'
";
$result_total_bought_price = fetch_data($conn, $sql_total_bought_price);
$total_bought_price = $result_total_bought_price->fetch_assoc()['total_bought_price'] ?? 0;





// Fetch top products
$sql_product_chart = "SELECT p.productname, 
                             SUM(oi.quantity) AS total_quantity 
                      FROM order_items oi
                      JOIN products p ON oi.productid = p.productid
                      GROUP BY oi.productid 
                      ORDER BY total_quantity DESC 
                      LIMIT 5";
$result_product_chart = fetch_data($conn, $sql_product_chart);
$product_names = [];
$product_quantities = [];
while ($row = $result_product_chart->fetch_assoc()) {
    $product_names[] = $row['productname'];
    $product_quantities[] = $row['total_quantity'];
}

// Fetch top users of the month
$sql_top_users = "SELECT u.firstname, u.lastname, 
                         COUNT(o.order_id) AS total_order
                  FROM `order` o
                  JOIN user u ON o.userid = u.userID
                  WHERE o.order_date BETWEEN '$date_from' AND '$date_to'
                  GROUP BY u.userID 
                  ORDER BY total_order DESC 
                  LIMIT 5";
$result_top_users = fetch_data($conn, $sql_top_users);
$top_users = [];
$total_orders = [];
while ($row = $result_top_users->fetch_assoc()) {
    $top_users[] = $row['firstname'] . ' ' . $row['lastname'];
    $total_orders[] = $row['total_order'];
}
$sql_top_buyers = "
    SELECT u.firstname, u.lastname, SUM(oi.quantity) AS total_quantity
    FROM `order` o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN user u ON o.userid = u.userID
    WHERE o.order_date BETWEEN '$date_from' AND '$date_to'
    GROUP BY u.userID
    ORDER BY total_quantity DESC
    LIMIT 5";
$result_top_buyers = fetch_data($conn, $sql_top_buyers);

// Fetch latest orders
$sql_latest_order = "SELECT o.order_id, o.order_date, p.total_amount, p.payment_status, 
                             u.firstname, u.lastname 
                      FROM `order` o
                      JOIN user u ON o.userid = u.userID
                      JOIN payment p ON o.order_id = p.order_id
                      WHERE o.order_date BETWEEN '$date_from' AND '$date_to'
                      ORDER BY o.order_date DESC 
                      LIMIT 5";
$result_latest_order = fetch_data($conn, $sql_latest_order);



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <title>Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>





    <style>

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Responsive columns with min 250px */
    gap: 20px;
    width: 100%; /* Full width to align with charts */
    margin: 0 auto 20px;
    padding: 0 20px;
}

.card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    padding: 30px 20px;
    text-align: center;
    height: 150px; /* Consistent height */
    display: flex;
    flex-direction: column;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease-in-out;
}

.card:hover {
    transform: translateY(-10px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
}

.card h3 {
    font-size: 1.2rem;
    color: #007bff;
    margin-bottom: 10px;
}

.card p {
    font-size: 2rem;
    font-weight: bold;
    color: #000;
    margin: 0;
}

.chart-container {
    display: grid;
    grid-template-columns: repeat(2, 1fr); /* 2 charts per row for medium to large screens */
    gap: 20px;
    width: 100%;
    margin-top: 20px;
}

.chart.large {
    grid-column: span 2;
    height: 450px;
}

.chart.medium {
    height: 350px;
}

.chart.small {
    height: 250px;
}

.chart.extra-large {
    grid-column: span 2;
    height: 600px;
}

canvas {
    max-width: 100%;
}

.table-container {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

table th, table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
    white-space: nowrap;
}
.date-picker {
    padding: 10px;
    font-size: 16px;
    width: 180px;
    border: 1px solid #ccc;
    border-radius: 5px;
    margin-right: 10px;
    transition: border-color 0.3s;
}

.date-picker:focus {
    border-color: #007bff;
    outline: none;
}

/* Modal styling */
.modal {
    display: none;
    position: fixed;
    z-index: 1;
    padding-top: 60px;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.4);
}

.modal-content {
    background-color: #fefefe;
    margin: auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 700px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: #000;
    text-decoration: none;
    cursor: pointer;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: repeat(2, 1fr); /* 2 cards per row on smaller screens */
    }
    
    .chart-container {
        grid-template-columns: 1fr; /* 1 chart per row on smaller screens */
    }

    .table-container {
        overflow-x: auto;
    }
}
.date-picker {
    padding: 10px;
    font-size: 1rem;
    border: 1px solid #ccc;
    border-radius: 5px;
    width: 150px;
    transition: border-color 0.3s ease;
}

.date-picker:focus {
    outline: none;
    border-color: #007bff;
}
form button {
    padding: 10px 20px;
    font-size: 1rem;
    font-weight: bold;
    color: #fff;
    background-color: #007bff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}



    </style>
</head>
<body>
  

    <div class="content">
        <h1>Dashboard</h1>
        <div class="content">
    <?php
    // Format the dates for display
    $formatted_date_from = date('F j, Y', strtotime($date_from));
    $formatted_date_to = date('F j, Y', strtotime($date_to));
    ?>

</div>

<form action="export_report.php" method="POST" style="margin-bottom: 10px; ">
    <input type="hidden" name="date_from" value="<?php echo htmlspecialchars(substr($date_from, 0, 10)); ?>">
    <input type="hidden" name="date_to" value="<?php echo htmlspecialchars(substr($date_to, 0, 10)); ?>">
    <button type="submit">Export Report</button>
</form>


<form method="GET" action="" style="margin-bottom: 20px;">
  <div>  <label for="date_from">Date From:</label>
    <input type="text" id="date_from" name="date_from" class="date-picker" 
        placeholder="Select start date">
    
    <label for="date_to">Date To:</label>
    <input type="text" id="date_to" name="date_to" class="date-picker" 
        placeholder="Select end date">

    <button type="submit">Filter</button>
    <button onclick="setDateRange('last7Days')">Last 7 Days</button>
<button onclick="setDateRange('thisMonth')">This Month</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
       <a style="font-size: 1rem; margin: 0;">
        Showing data from <strong><?php echo $formatted_date_from; ?></strong> to <strong><?php echo $formatted_date_to; ?></strong>.
   </div>
   <br>

</form>




        <div class="dashboard-grid">


    <!-- Total Sales Card -->
    <div class="card" onclick="openModal('totalSalesModal')">
        <h3>Total Sales</h3>
        <p>₱<?php echo number_format($total_sales, 2); ?></p>
    </div>

    <!-- Net Profit Card -->
    <div class="card" onclick="openModal('netProfitModal')">
        <h3>Net Sales</h3>
        <p>₱<?php echo number_format($net_profit, 2); ?></p>
    </div>

    <!-- Total Orders Card -->
    <div class="card" onclick="openModal('totalOrdersModal')">
        <h3>Total Orders</h3>
        <p><?php echo $total_order; ?></p>
    </div>

    <!-- Total Bought Price Card -->
    <div class="card" onclick="openModal('totalBoughtPriceModal')">
        <h3>Total Investment</h3>
        <p>₱<?php echo number_format($total_bought_price, 2); ?></p>
    </div>


<!-- Modals for Detailed Table Data -->

<!-- Total Sales Modal -->
<div id="totalSalesModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('totalSalesModal')">&times;</span>
        <h2>Total Sales Details</h2>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Amount Paid</th>
                    <th>Payment Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql_sales_details = "SELECT o.order_id, p.amount_paid, p.payment_status, o.order_date 
                                      FROM `order` o
                                      JOIN payment p ON o.order_id = p.order_id
                                      WHERE p.payment_status IN ('Paid', 'Partial') 
                                      AND o.order_date BETWEEN '$date_from' AND '$date_to'";
                $result_sales_details = fetch_data($conn, $sql_sales_details);
                while ($row = $result_sales_details->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['order_id']; ?></td>
                        <td>₱<?php echo number_format($row['amount_paid'], 2); ?></td>
                        <td><?php echo $row['payment_status']; ?></td>
                        <td><?php echo $row['order_date']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Net Profit Modal -->
<div id="netProfitModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('netProfitModal')">&times;</span>
        <h2>Net Sales Details</h2>
        <p>Total Sales: ₱<?php echo number_format($total_sales, 2); ?></p>
        <p>Total Buy Price: ₱<?php echo number_format($total_buy_price, 2); ?></p>
        <p>Total Buchers Fee: ₱<?php echo number_format(1000 * $total_bought_pigs, 2); ?></p>
        <p><strong>Net Profit: ₱<?php echo number_format($net_profit, 2); ?></strong></p>
    </div>
</div>


<div id="totalOrdersModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('totalOrdersModal')">&times;</span>
        <h2>Total Orders Details</h2>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Payment Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql_orders_details = "SELECT o.order_id, o.order_date, p.total_amount, p.payment_status 
                                       FROM `order` o
                                       JOIN payment p ON o.order_id = p.order_id
                                       WHERE o.order_date BETWEEN '$date_from' AND '$date_to'";
                $result_orders_details = fetch_data($conn, $sql_orders_details);
                while ($row = $result_orders_details->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['order_id']; ?></td>
                        <td><?php echo $row['order_date']; ?></td>
                        <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                        <td><?php echo $row['payment_status']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>


<div id="totalBoughtPriceModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('totalBoughtPriceModal')">&times;</span>
        <h2>Total Investment Details</h2>
        <table>
            <thead>
                <tr>
                    <th>Seller Name</th>
                    <th>Batch Number</th>
                    <th>Price</th>
                    <th>Processed Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
$sql_bought_price_details = "
    SELECT u.firstname, u.lastname, psr.batch_number, psr.price, psr.processed_at 
    FROM pig_sell_requests psr
    JOIN pig_survey ps ON psr.survey_id = ps.id
    JOIN user u ON ps.userid = u.userID
    WHERE psr.status = 'accepted'
    AND psr.processed_at BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'
";
$result_bought_price_details = fetch_data($conn, $sql_bought_price_details);

                while ($row = $result_bought_price_details->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['firstname'] . ' ' . $row['lastname']; ?></td>
                        <td><?php echo $row['batch_number']; ?></td>
                        <td>₱<?php echo number_format($row['price'], 2); ?></td>
                        <td><?php echo $row['processed_at']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>







        </div>

        <div class="chart-container">
            <div class="chart large">
                <h3>Sales and Investment</h3>
                <canvas id="combinedSalesBuyPriceChart"></canvas>
            </div>

            <div class="chart medium">
                <h3>Payment Status Distribution</h3>
                <canvas id="paymentStatusChart"></canvas>
            </div>

            <div class="chart medium">
                <h3>Order and User Growth</h3>
                <canvas id="combinedChart"></canvas>
            </div>
        

<!-- Top Products Table -->
<div class="chart medium">
    <h3>Top Products</h3>
    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Product Name</th>
                <th>Units Sold</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $rank = 1;
            $result_product_chart->data_seek(0); // Reset result set pointer
            while ($row = $result_product_chart->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $rank++; ?></td>
                    <td><?php echo htmlspecialchars($row['productname']); ?></td>
                    <td><?php echo htmlspecialchars($row['total_quantity']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Top Pig Sellers Table -->
<div class="chart medium">
    <h3>Top Pig Sellers</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Seller Name</th>
                    <th>Total Pigs Sold</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rank = 1;
                while ($row = $result_top_pig_sell_requests->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $rank++; ?></td>
                        <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                        <td><?php echo htmlspecialchars($row['total_pigs_sold']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Top Users of the Month Table -->

<div class="chart medium">
    <h3>Top Buyers of the Month</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>User Name</th>
                    <th>Total Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rank = 1;
                $result_top_buyers->data_seek(0); // Reset result set pointer
                while ($row = $result_top_buyers->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $rank++; ?></td>
                        <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                        <td><?php echo htmlspecialchars($row['total_quantity']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- Latest Orders Table -->
<div class="chart medium">
    <h3>Latest Orders</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Order Date</th>
                    <th>User</th>
                    <th>Total Amount</th>
                    <th>Payment Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result_latest_order->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['order_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['order_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                        <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['payment_status']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

    <script>


// Combined Sales and Capital Chart
// Combined Sales and Investment Chart
// Fetch data for the chart from PHP
const last7Days = <?php echo $last7DaysJson; ?>;
const totalSalesData = <?php echo $salesDataJson; ?>;
const totalBuyPriceData = <?php echo $buyPriceDataJson; ?>;

// Combined Sales and Investment Chart
const combinedChartCtx = document.getElementById('combinedSalesBuyPriceChart').getContext('2d');
const combinedSalesBuyPriceChart = new Chart(combinedChartCtx, {
    type: 'line',
    data: {
        labels: last7Days, // X-axis labels representing the last 7 days
        datasets: [
            {
                label: 'Total Sales (₱)', // Label for the sales dataset
                data: totalSalesData, // Sales data with 0s for missing days
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                fill: true,
                tension: 0.4
            },
            {
                label: 'Total Investment (₱)', // Label for the investment dataset
                data: totalBuyPriceData, // Buy price data with 0s for missing days
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                fill: true,
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                title: {
                    display: true,
                    text: 'Date' // Label for the x-axis
                }
            },
            y: {
                title: {
                    display: true,
                    text: 'Amount (₱)' // Label for the y-axis
                },
                beginAtZero: true
            }
        }
    }
});





        // Payment Status bar
        const paymentCtx = document.getElementById('paymentStatusChart').getContext('2d');
        const paymentStatusChart = new Chart(paymentCtx, {
            type: 'bar',
            data: {
                labels: ['Paid', 'Unpaid', 'Partial'],
                datasets: [{
                    data: [
                        <?php echo $payment_status['paid']; ?>,
                        <?php echo $payment_status['unpaid']; ?>,
                        <?php echo $payment_status['partial']; ?>
                    ],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(255, 206, 86, 0.7)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });

        // Initialize the order volume and user growth chart
        const userGrowthCtx = document.getElementById('combinedChart').getContext('2d');
        const combinedChart = new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($order_volume_data)); ?>,
                datasets: [
                    {
                        label: 'Order Volume',
                        data: <?php echo json_encode(array_values($order_volume_data)); ?>,
                        borderColor: 'rgba(255, 159, 64, 1)',
                        backgroundColor: 'rgba(255, 159, 64, 0.2)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'User Growth',
                        data: <?php echo json_encode(array_values($user_growth_data)); ?>,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Time Period'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Data'
                        },
                        beginAtZero: true
                    }
                }
            }
        });

        // Top Products Chart
        const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
        const topProductsChart = new Chart(topProductsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($product_names); ?>,
                datasets: [{
                    label: 'Units Sold',
                    data: <?php echo json_encode($product_quantities); ?>,
                    backgroundColor: 'rgba(153, 102, 255, 0.6)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Top Pig Sellers Chart
        const topPigSellersCtx = document.getElementById('topPigSellersChart').getContext('2d');
        const topPigSellersChart = new Chart(topPigSellersCtx, {
            type: 'bar',
            data: {
                labels: <?php 
                    $pig_sellers = [];
                    while ($row = $result_top_pig_seller->fetch_assoc()) {
                        $pig_sellers[] = $row['firstname'] . ' ' . $row['lastname'];
                    }
                    echo json_encode($pig_sellers);
                ?>,
                datasets: [{
                    label: 'Total Pigs Sold',
                    data: <?php 
                        $pigs_sold = [];
                        while ($row = $result_top_pig_seller->fetch_assoc()) {
                            $pigs_sold[] = $row['total_pigs_sold'];
                        }
                        echo json_encode($pigs_sold);
                    ?>,
                    backgroundColor: 'rgba(255, 206, 86, 0.6)',
                    borderColor: 'rgba(255, 206, 86, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });


        // Top Users of the Month Chart
        const topUsersCtx = document.getElementById('topUsersChart').getContext('2d');
        const topUsersChart = new Chart(topUsersCtx, {
            type: 'bar',
            data: {
                labels: <?php 
                    $users = [];
                    while ($row = $result_top_users->fetch_assoc()) {
                        $users[] = $row['firstname'] . ' ' . $row['lastname'];
                    }
                    echo json_encode($users);
                ?>,
                datasets: [{
                    label: 'Total Orders',
                    data: <?php 
                        $orders = [];
                        while ($row = $result_top_users->fetch_assoc()) {
                            $orders[] = $row['total_order'];
                        }
                        echo json_encode($orders);
                    ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

document.addEventListener('DOMContentLoaded', function () {
    flatpickr("#date_from", {
        dateFormat: "Y-m-d",
        allowInput: true,
        defaultDate: "<?php echo htmlspecialchars(substr($date_from, 0, 10)); ?>"
    });

    flatpickr("#date_to", {
        dateFormat: "Y-m-d",
        allowInput: true,
        defaultDate: "<?php echo htmlspecialchars(substr($date_to, 0, 10)); ?>"
    });
});

// Accessible globally
function setDateRange(range) {
    const today = new Date();
    const start = new Date();
    if (range === 'last7Days') start.setDate(today.getDate() - 7);
    if (range === 'thisMonth') start.setDate(1);

    document.getElementById('date_from')._flatpickr.setDate(start);
    document.getElementById('date_to')._flatpickr.setDate(today);
}




    // JavaScript functions to open and close modals
    function openModal(modalId) {
        document.getElementById(modalId).style.display = "block";
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = "none";
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        let modals = document.getElementsByClassName("modal");
        for (let i = 0; i < modals.length; i++) {
            if (event.target == modals[i]) {
                modals[i].style.display = "none";
            }
        }
    };




    function setDateRange(range) {
        const today = new Date();
        const start = new Date();
        if (range === 'last7Days') start.setDate(today.getDate() - 7);
        if (range === 'thisMonth') start.setDate(1);
        document.getElementById('date_from').value = start.toISOString().split('T')[0];
        document.getElementById('date_to').value = today.toISOString().split('T')[0];
    }


    </script>
</body>
</html>

<?php
$conn->close();
?>
