    <?php
    session_start();
    include("config.php");
    include("navbar.php");
    // Pagination settings

    // Pagination settings for "successfully sold pigs" table
    $rowsPerPage = 5; // Limit 5 records per page
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($currentPage - 1) * $rowsPerPage;

    // Fetch successfully sold pigs with pagination
    $query = "SELECT id, batch_number, piglet_count, weight, price, processed_at 
              FROM pig_sell_requests 
              WHERE status = 'accepted' 
              LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $rowsPerPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM pig_sell_requests WHERE status = 'accepted'";
    $countResult = $conn->query($countQuery);
    $totalRow = $countResult->fetch_assoc();
    $totalRows = $totalRow['total'];
    $totalPages = ceil($totalRows / $rowsPerPage);



    // Retrieve user data from the database
    $userid = $_SESSION['userid'];
    $query = "SELECT firstname, lastname, middlename, bday, gender, contactNumber FROM user WHERE userid = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $stmt->bind_result($firstname, $lastname, $middlename, $bday, $gender, $number);
    $stmt->fetch();
    $stmt->close();

    // Retrieve profile photo from the user_assets table
    $sql_photo = "SELECT profile_photo FROM user_assets WHERE userid = ?";
    $stmt_photo = $conn->prepare($sql_photo); // Use a new statement object
    $stmt_photo->bind_param("i", $userid);
    $stmt_photo->execute();
    $stmt_photo->bind_result($profile_photo);
    $stmt_photo->fetch();
    $stmt_photo->close();

    // Retrieve user's membership status
    $sql_membership = "SELECT status FROM user_membership WHERE userid = ?";
    $stmt_membership = $conn->prepare($sql_membership); // Use a new statement object
    $stmt_membership->bind_param("i", $userid);
    $stmt_membership->execute();
    $stmt_membership->bind_result($membership_status);
    $stmt_membership->fetch();
    $stmt_membership->close();

    // Check if the user is a member
    $is_member = ($membership_status === 'Member');

    // Only retrieve pig information if the user is a member
    if ($is_member) {
        // Query to retrieve pig information and survey_id from pig_survey table
        $sql_pig_info = "SELECT id as survey_id, SUM(total_piglets) AS total_piglets, SUM(batch_count) AS batch_count, 
                                SUM(pregnant_females) AS pregnant_females, SUM(sows) AS sows, 
                                SUM(boars) AS boars, SUM(total_pigs) AS total_pigs 
                         FROM pig_survey WHERE userid = ?";
        $stmt_pig_info = $conn->prepare($sql_pig_info); // Use a new statement object
        $stmt_pig_info->bind_param("i", $userid);
        $stmt_pig_info->execute();
        $stmt_pig_info->bind_result($survey_id, $total_piglets, $batch_count, $pregnant_females, $sows, $boars, $total_pigs);
        $stmt_pig_info->fetch();
        $stmt_pig_info->close();

        // Set default values if no pig survey exists
        $total_piglets = $total_piglets ?? 0;
        $batch_count = $batch_count ?? 0;
        $pregnant_females = $pregnant_females ?? 0;
        $sows = $sows ?? 0;
        $boars = $boars ?? 0;
        $total_pigs = $total_pigs ?? 0;

        // Query to retrieve batch details if a survey exists
        $batch_details = [];
        if ($survey_id) {
            $sql_batch_details = "SELECT batch_number, piglet_count, birthdate FROM batch_details WHERE survey_id = ? AND piglet_count > 0";
            $stmt_batch_details = $conn->prepare($sql_batch_details); // Use a new statement object
            $stmt_batch_details->bind_param("i", $survey_id);
            $stmt_batch_details->execute();
            $batch_details = $stmt_batch_details->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_batch_details->close();
        }

        // Query to retrieve pregnancy details if a survey exists
        $pregnancy_details = [];
        if ($survey_id) {
$test_survey_id = 8; // Replace with an ID that exists in pregnancy_details
$sql_pregnancy_details = "SELECT female_number, pregnancy_date FROM pregnancy_details WHERE survey_id = ?";
$stmt_pregnancy_details = $conn->prepare($sql_pregnancy_details);
$stmt_pregnancy_details->bind_param("i", $test_survey_id);
$stmt_pregnancy_details->execute();
$pregnancy_details = $stmt_pregnancy_details->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt_pregnancy_details->close();

        }
    }


    // Calculate total pages
    $totalPages = ceil($totalRows / $rowsPerPage);

    // Fetch order history for the table with pagination
    $sql_order_history = "SELECT o.order_id, o.order_date, p.total_amount, p.payment_status, o.order_status, t.tracking_number
                          FROM `order` o
                          JOIN payment p ON p.order_id = o.order_id
                          LEFT JOIN tracking t ON t.order_id = o.order_id
                          WHERE o.userid = ?
                          ORDER BY o.order_date DESC
                          LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql_order_history);
    $sql_order_chart = "SELECT DATE_FORMAT(o.order_date, '%Y-%m-%d') AS order_date, 
                               SUM(p.total_amount) AS total_amount
                        FROM `order` o
                        JOIN payment p ON o.order_id = p.order_id
                        WHERE o.userid = ?
                        GROUP BY order_date
                        ORDER BY order_date ASC";
    $stmt = $conn->prepare($sql_order_chart);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result_order_chart = $stmt->get_result();

    $order_dates = [];
    $order_totals = [];

    while ($row = $result_order_chart->fetch_assoc()) {
        $order_dates[] = $row['order_date'];
        $order_totals[] = $row['total_amount'];
    }

    $stmt->close();


    // Pagination settings
    $limit = 5; // Limit 5 records per page
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;


    // Calculate total pages
    $sql_count = "SELECT COUNT(*) AS total FROM `order` WHERE userid = ?";
    $stmt = $conn->prepare($sql_count);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $stmt->bind_result($total_orders);
    $stmt->fetch();
    $stmt->close();

    $total_pages = ceil($total_orders / $limit);



    // Set default date range (current month)
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');

    // Handle the date range form submission for "Order History & Charts"
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['start_date']) && isset($_POST['end_date'])) {
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
    }

    // Initialize totals to avoid undefined variable warnings
    $total_ordered = 0;
    $total_paid = 0;
    $total_unpaid = 0;
    $total_quantity = 0;

    // Retrieve total amount ordered, total paid, total unpaid amounts, and total quantity ordered within the selected date range
    $sql_totals = "SELECT 
                        SUM(p.total_amount) AS total_ordered,
                        SUM(CASE WHEN p.payment_status = 'Paid' THEN p.amount_paid ELSE 0 END) AS total_paid,
                        SUM(CASE WHEN p.payment_status = 'Unpaid' THEN p.total_amount ELSE 0 END) AS total_unpaid,
                        SUM(oi.quantity) AS total_quantity
                    FROM `order` o
                    JOIN order_items oi ON o.order_id = oi.order_id
                    JOIN payment p ON p.order_id = o.order_id
                    WHERE o.userid = ? AND o.order_date BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql_totals);
    $stmt->bind_param("iss", $userid, $start_date, $end_date);
    $stmt->execute();
    $stmt->bind_result($total_ordered, $total_paid, $total_unpaid, $total_quantity);
    $stmt->fetch();
    $stmt->close();

    // Fetch order history for the table
    $sql_order_history = "SELECT o.order_id, o.order_date, p.total_amount, p.payment_status, o.order_status, t.tracking_number
                          FROM `order` o
                          JOIN payment p ON p.order_id = o.order_id
                          LEFT JOIN tracking t ON t.order_id = o.order_id
                          WHERE o.userid = ?
                          ORDER BY o.order_date DESC
                          LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql_order_history);
    $stmt->bind_param("iii", $userid, $limit, $offset);
    $stmt->execute();
    $order_history_result = $stmt->get_result();
    $stmt->close();


    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <title>Profile Overview with Forms and Charts</title>
        <style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-image: url('img/News_Header.png');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
}
.container {
    display: flex;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}
.profile-sidebar {
    width: 25%;
    height: 500px; /* Set a fixed height */
    background-color: white; /* Semi-transparent */
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    overflow: auto; /* Allows scrolling if content overflows */
}
.profile-sidebar img {
    width: 100px;
    border-radius: 50%;
    display: block;
    margin: 0 auto 20px;
}
.profile-sidebar h2 {
    text-align: center;
    color: black; /* Updated color */
}
.profile-sidebar p {
    text-align: center;
    margin: 5px 0;
    color: #6c757d;
}
.tabs-content {
    width: 80%;
    margin-left: 20px;
    background-color: white; /* Semi-transparent */
    padding: 20px;
    border-radius: 10px;
}
.tabs {
    display: flex;
    cursor: pointer;
    justify-content: space-around;
    margin-bottom: 20px;
    background-color: #AC1D1D; /* Updated color */
    padding: 10px 0;
}
.tabs div {
    padding: 15px 20px;
    background-color: #AC1D1D; /* Updated color */
    color: white;
    border-radius: 5px;
}
.tabs .active {
    background-color: #8A1717; /* Darker shade for active tab */
}
.tab-content {
    display: none;
}
.tab-content.active {
    display: block;
}
.chart-container {
    width: 48%;
    margin-right: 2%;
    float: left;
}
.order-history-table, .general-pig-table, .pregnancy-data-table, .pig-sell-table {
    clear: both;
    margin-top: 20px;
    width: 100%;
    border-collapse: collapse;
}
.order-history-table th, .general-pig-table th, .pregnancy-data-table th, .pig-sell-table th,
.order-history-table td, .general-pig-table td, .pregnancy-data-table td, .pig-sell-table td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}
.order-history-table th, .general-pig-table th, .pregnancy-data-table th, .pig-sell-table th {
    background-color: #AC1D1D; /* Updated color */
    color: white;
}
.pagination {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

.pagination a {
    color: #AC1D1D; /* Updated color */
    padding: 8px 16px;
    text-decoration: none;
    border: 1px solid #ddd;
    margin: 0 4px;
    border-radius: 4px;
}

.pagination a.active {
    background-color: #AC1D1D; /* Updated color */
    color: white;
}

.pagination a:hover:not(.active) {
    background-color: #ddd;
}

        </style>
    </head>
    <body>
        <div class="container">
            <!-- Profile Sidebar -->
            <div class="profile-sidebar">
                <img src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Profile Photo" class="profile-photo mb-3">
                <h2><?php echo htmlspecialchars("$firstname $middlename $lastname"); ?></h2>
                <p>Phone: <?php echo htmlspecialchars($number); ?></p>
                <p>Gender: <?php echo htmlspecialchars($gender); ?></p>
                <p>Birthday: <?php echo htmlspecialchars($bday); ?></p>

                <!-- Edit Profile Button -->
                <a href="edit_profile.php" class="btn" style="display: block; text-align: center; margin-top: 20px; background-color: #28a745; padding: 10px; color: white; text-decoration: none; border-radius: 5px;">Edit Profile</a>

                <!-- Log Out Button -->
                <a href="logout.php" class="btn" style="display: block; text-align: center; margin-top: 20px; background-color: #007bff; padding: 10px; color: white; text-decoration: none; border-radius: 5px;">Log Out</a>
            </div>

            <!-- Tabs and Content -->
            <div class="tabs-content">
                <!-- Tab Navigation -->
                <div class="tabs">
                    <div class="active" data-tab="tab1">Order History</div>
                    <div data-tab="tab2">General Pig Information</div>
                    <div data-tab="tab3">Pregnancy Data</div>
                    <div data-tab="tab4">Pig Sell</div>
                </div>

                <!-- Tab Content: Order History & Charts -->
                <div id="tab1" class="tab-content active">
                    <!-- Charts Section -->
                    <div class="chart-container">
                        <h4>Order Summary Chart</h4>
                        <canvas id="orderSummaryChart"></canvas>
                    </div>
                    <div class="chart-container">
                        <h4>Order History Chart</h4>
                        <canvas id="orderHistoryChart"></canvas>
                    </div>

    <!-- Order History Table -->
    <h3>Order History</h3>
    <table class="order-history-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Order Date</th>
                <th>Total Amount</th>
                <th>Payment Status</th>
                <th>Order Status</th>
                <th>Tracking Number</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $order_history_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['order_id']); ?></td>
                <td><?php echo htmlspecialchars($row['order_date']); ?></td>
                <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                <td><?php echo htmlspecialchars($row['payment_status']); ?></td>
                <td><?php echo htmlspecialchars($row['order_status']); ?></td>
                <td><?php echo htmlspecialchars($row['tracking_number']); ?></td>
    <td>
        <?php if ($row['order_status'] !== 'Cancelled' && $row['order_status'] !== 'Packed' && $row['order_status'] !== 'Delivered'): ?>
    <form id="cancelForm<?php echo htmlspecialchars($row['order_id']); ?>" action="cancel_order.php" method="POST" onsubmit="return confirmCancellation(event, <?php echo htmlspecialchars($row['order_id']); ?>);">
        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($row['order_id']); ?>">
        <button type="submit" style="background-color: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">Cancel</button>
    </form>

        <?php else: ?>
            <span><?php echo $row['order_status'] === 'Cancelled' ? 'Cancelled' : 'Not Cancellable'; ?></span>
        <?php endif; ?>
    </td>



            </tr>
            <?php endwhile; ?>
        </tbody>
        <?php if (isset($_SESSION['success_message'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '<?php echo addslashes($_SESSION['success_message']); ?>',
                confirmButtonColor: '#3085d6'
            });
        </script>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?php echo addslashes($_SESSION['error_message']); ?>',
                confirmButtonColor: '#d33'
            });
        </script>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    </table>

                    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>">&laquo; Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>

                </div>

                <!-- Tab Content: General Pig Information -->
                <div id="tab2" class="tab-content">
                    <table class="general-pig-table">
                        <thead>
                            <tr>
                                <th>Total Grower/s</th>
                                <th>Total Batches</th>
                                <th>Pregnant Females</th>
                                <th>Sows</th>
                                <th>Boars</th>
                                <th>Total Pigs</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo htmlspecialchars($total_piglets); ?></td>
                                <td><?php echo htmlspecialchars($batch_count); ?></td>
                                <td><?php echo htmlspecialchars($pregnant_females); ?></td>
                                <td><?php echo htmlspecialchars($sows); ?></td>
                                <td><?php echo htmlspecialchars($boars); ?></td>
                                <td><?php echo htmlspecialchars($total_pigs); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tab Content: Pregnancy Data -->
                <div id="tab3" class="tab-content">
                    <table class="pregnancy-data-table">
                        <thead>
                            <tr>
                                <th>Pregnant Female Number</th>
                                <th>Pregnancy Start Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pregnancy_details)): ?>
                                <?php foreach ($pregnancy_details as $pregnancy): 
                                    $pregnancyDate = new DateTime($pregnancy['pregnancy_date']);
                                    $gestationAgeInDays = $pregnancyDate->diff(new DateTime())->days;
                                    $alert = $gestationAgeInDays >= 110 ? "⚠️ Soon to give birth" : "Gestating";
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pregnancy['female_number']); ?></td>
                                    <td><?php echo htmlspecialchars($pregnancy['pregnancy_date']); ?></td>
                                    <td><?php echo $alert; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="3">No pregnancy data available.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

    <!-- Tab Content: Pig Sell -->
    <div id="tab4" class="tab-content">
        <table class="pig-sell-table">
            <thead>
                <tr>
                    <th>Batch Number</th>
                    <th>Grower Count</th>
                    <th>Birth Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($batch_details)): ?>
                    <?php foreach ($batch_details as $batch): 
                        $birthdate = new DateTime($batch['birthdate']);
                        $currentDate = new DateTime();
                        $ageInDays = $birthdate->diff($currentDate)->days;
                        $status = $ageInDays >= 0 ? "Ready to Sell" : "Not Ready";
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($batch['batch_number']); ?></td>
                        <td><?php echo htmlspecialchars($batch['piglet_count']); ?></td>
                        <td><?php echo htmlspecialchars($batch['birthdate']); ?></td>
                        <td><?php echo $status; ?></td>
                        <td>
                            <?php if ($status === "Ready to Sell"): ?>
                                <form action="pig_sell_req.php" method="POST">
                                    <input type="hidden" name="batch_number" value="<?php echo htmlspecialchars($batch['batch_number']); ?>">
                                    <label for="sell_count">Pigs to be sold</label>
                                    <input type="number" name="sell_count" id="sell_count" min="1" max="<?php echo htmlspecialchars($batch['piglet_count']); ?>" required>
                                    <input type="hidden" name="birthdate" value="<?php echo htmlspecialchars($batch['birthdate']); ?>">
                                    <button type="submit" class="btn-sell" style="background-color: #28a745; color: white; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer;">Sell Request</button>
                                </form>
                            <?php else: ?>
                                <span>Not ready</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="5">No pigs available for sale.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Table for Successfully Sold Pigs -->
        <h3>List of Successfully Sold Pigs</h3>
        <table class="pig-sell-table">

            <thead>
                <tr>
                    <th>Sold ID</th>
                    <th>Batch Number</th>
                    <th>Piglet Count</th>
                    <th>Weight (kg)</th>
                    <th>Price (₱)</th>
                    <th>Processed Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['batch_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['piglet_count']); ?></td>
                            <td><?php echo htmlspecialchars($row['weight']); ?> kg</td>
                            <td>₱<?php echo number_format($row['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['processed_at']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No sold pigs available.</td>

                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination Controls -->
        <div class="pagination">

            <?php if ($currentPage > 1): ?>
                <a href="?page=<?php echo $currentPage - 1; ?>">Previous</a>
            <?php endif; ?>

            <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                <a href="?page=<?php echo $page; ?>" class="<?php echo $page == $currentPage ? 'active' : ''; ?>">
                    <?php echo $page; ?>
                </a>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="?page=<?php echo $currentPage + 1; ?>">Next</a>
            <?php endif; ?>
        </div>
    </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const tabs = document.querySelectorAll('.tabs div');
            const contents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    contents.forEach(c => c.classList.remove('active'));

                    tab.classList.add('active');
                    document.getElementById(tab.getAttribute('data-tab')).classList.add('active');
                });
            });

            // Order Summary Chart
            const ctxSummary = document.getElementById('orderSummaryChart').getContext('2d');
            const orderSummaryChart = new Chart(ctxSummary, {
                type: 'bar',
                data: {
                    labels: ['Total Ordered', 'Total Amount Paid', 'Unsettled'],
                    datasets: [{
                        label: '₱',
                        data: [<?php echo $total_ordered; ?>, <?php echo $total_paid; ?>, <?php echo $total_unpaid; ?>],
                        backgroundColor: ['rgba(54, 162, 235, 0.7)', 'rgba(75, 192, 192, 0.7)', 'rgba(255, 99, 132, 0.7)'],
                        borderColor: ['rgba(54, 162, 235, 1)', 'rgba(75, 192, 192, 1)', 'rgba(255, 99, 132, 1)'],
                        borderWidth: 2
                    }]
                },
                options: {
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });

            // Order History Chart

        const orderDates = <?php echo json_encode($order_dates); ?>;
        const orderTotals = <?php echo json_encode($order_totals); ?>;

        const ctxHistory = document.getElementById('orderHistoryChart').getContext('2d');
        const orderHistoryChart = new Chart(ctxHistory, {
            type: 'line',
            data: {
                labels: orderDates,
                datasets: [{
                    label: 'Total Amount (₱)',
                    data: orderTotals,
                    backgroundColor: 'rgba(0, 123, 255, 0.2)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 3
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });


    function confirmCancellation(event, orderId) {
        event.preventDefault(); // Prevent the form from submitting immediately

        Swal.fire({
            title: 'Are you sure?',
            text: "Do you really want to cancel this order?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, cancel it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit the form programmatically
                document.getElementById('cancelForm' + orderId).submit();
            }
        });

        return false; // Ensure the form does not submit automatically
    }

        </script>
    </body>
    </html>