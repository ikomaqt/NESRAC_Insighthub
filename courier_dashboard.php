    <?php
    session_start();
    include("config.php");




    // Handle search query and filters
    $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $payment_status_filter = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

    // SQL query to fetch only Packed and Delivered orders
    $sql_courier_dashboard = "SELECT 
                                o.order_id,
                                o.order_date,
                                p.total_amount, 
                                p.payment_status,
                                p.amount_paid,
                                o.order_status,
                                t.tracking_number,
                                u.firstname,
                                u.lastname,
                                CONCAT(ra.purok_street, ', ', ra.barangay, ', ', ra.municipality) AS shipping_address
                            FROM 
                                `order` o
                            JOIN 
                                `user` u ON o.userid = u.userID
                            LEFT JOIN 
                                `payment` p ON o.order_id = p.order_id
                            LEFT JOIN 
                                `tracking` t ON o.order_id = t.order_id
                            LEFT JOIN  
                                `receiver` r ON o.userid = r.userid
                            LEFT JOIN 
                                `receiver_address` ra ON r.receiver_id = ra.receiver_id
                            WHERE 
                                (o.order_status = 'Packed' OR o.order_status = 'Delivered') AND 
                                (u.firstname LIKE ? 
                                OR u.lastname LIKE ? 
                                OR o.order_id LIKE ?)";

    // Adding filters to the SQL query
    if ($status_filter !== '') {
        $sql_courier_dashboard .= " AND o.order_status = ?";
    }
    if ($payment_status_filter !== '') {
        $sql_courier_dashboard .= " AND p.payment_status = ?";
    }
    if ($start_date !== '') {
        $sql_courier_dashboard .= " AND o.order_date >= ?";
    }
    if ($end_date !== '') {
        $sql_courier_dashboard .= " AND o.order_date <= ?";
    }

    $sql_courier_dashboard .= " GROUP BY o.order_id ORDER BY o.order_date DESC";

    $stmt = $conn->prepare($sql_courier_dashboard);

    // Bind parameters dynamically based on filters
    $params = [];
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';

    if ($status_filter !== '') {
        $params[] = $status_filter;
    }
    if ($payment_status_filter !== '') {
        $params[] = $payment_status_filter;
    }
    if ($start_date !== '') {
        $params[] = $start_date;
    }
    if ($end_date !== '') {
        $params[] = $end_date;
    }

    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result_courier_dashboard = $stmt->get_result();
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Courier Dashboard</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
        <style>
            /* General Styling */
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background-color: #f8f9fa;
                margin: 0;
                padding: 0;
            }
            .header {
                background-color: #343a40;
                padding: 15px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                color: white;
            }
            .header h1 {
                font-size: 24px;
                margin: 0;
            }
            .content {
                padding: 15px;
            }

            /* Toggle Buttons */
            .view-toggle {
                display: flex;
                justify-content: flex-end;
                margin-bottom: 20px;
            }
            .view-toggle button {
                margin-left: 5px;
                padding: 5px 10px;
                border: none;
                border-radius: 4px;
                background-color: #007bff;
                color: white;
                cursor: pointer;
            }
            .view-toggle button:hover {
                background-color: #0056b3;
            }

            /* Filter Form Styling */
            .filter-form {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 20px;
                background: #fff;
                padding: 15px;
                border-radius: 8px;
                box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.05);
            }
            .filter-form .form-control,
            .filter-form .form-select {
                width: 100%;
                max-width: 180px;
            }
            .filter-form .btn {
                max-width: 150px;
                align-self: end;
            }

            /* Grid View */
            .grid-view {
                display: block;
            }
            .grid-view .order-card {
                border: 1px solid #e0e0e0;
                border-radius: 12px;
                padding: 20px;
                background-color: white;
                box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.05);
                margin-bottom: 15px;
                width: 100%;
            }
            .order-card h5 {
                font-size: 18px;
                font-weight: 600;
                color: #343a40;
            }
            .order-card p {
                font-size: 14px;
                color: #555;
            }
            .badge {
                padding: 5px 10px;
                font-size: 12px;
                border-radius: 20px;
            }
            .actions {
                text-align: right;
                margin-top: 15px;
            }

            /* Table View */
            .table-view-container {
                display: none;
            }
            .table-view-container table {
                width: 100%;
                border-collapse: collapse;
            }
            .table-view-container th, .table-view-container td {
                padding: 10px;
                border: 1px solid #ddd;
                text-align: left;
            }
            .table-view-container thead {
                background-color: #343a40;
                color: white;
            }
        </style>
    </head>
    <body>

        <!-- Header -->

        <div class="header">
    <h1>Courier Dashboard</h1>
    <a href="admin_logout.php" class="btn btn-sm btn-danger">Logout</a>
</div>


        <!-- View Toggle Buttons -->
        <div class="view-toggle">
            <button onclick="setView('grid')"><i class="fas fa-th"></i> Grid View</button>
            <button onclick="setView('table')"><i class="fas fa-table"></i> Table View</button>
        </div>

        <!-- Main Content -->
        <div class="content">
            <!-- Filter Form -->
            <form method="get" class="filter-form">
                <input type="text" name="search" class="form-control" placeholder="Search by User, Order ID..." value="<?php echo htmlspecialchars($search_query); ?>">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Packed" <?php echo $status_filter === 'Packed' ? 'selected' : ''; ?>>Packed</option>
                    <option value="Delivered" <?php echo $status_filter === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                </select>
                <select name="payment_status" class="form-select">
                    <option value="">All Payment Statuses</option>
                    <option value="Unpaid" <?php echo $payment_status_filter === 'Unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                    <option value="Partial" <?php echo $payment_status_filter === 'Partial' ? 'selected' : ''; ?>>Partial</option>
                    <option value="Paid" <?php echo $payment_status_filter === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                </select>
                <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>" placeholder="Start Date">
                <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>" placeholder="End Date">
                <button type="submit" class="btn btn-primary">Filter Orders</button>
            </form>

            <!-- Grid View -->
            <div id="order-container" class="grid-view">
                <?php if ($result_courier_dashboard->num_rows > 0) {
                    while ($row = $result_courier_dashboard->fetch_assoc()) { ?>
                        <div class="order-card">
                            <h5>Order ID: <?php echo htmlspecialchars($row['order_id']); ?></h5>
                            <p><strong>Order Date:</strong> <?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($row['order_date']))); ?></p>
                            <p><strong>User:</strong> <?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></p>
                            <p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($row['shipping_address']); ?></p>
                            <p><strong>Total Amount:</strong> ₱<?php echo number_format($row['total_amount'], 2); ?></p>
                            <p><strong>Amount Paid:</strong> ₱<?php echo number_format($row['amount_paid'], 2); ?></p>
                            <p><strong>Payment Status:</strong> 
                                <?php echo getBadge($row['payment_status']); ?>
                            </p>
                            <p><strong>Order Status:</strong> 
                                <?php echo getBadge($row['order_status'], true); ?>
                            </p>
                            <p><strong>Tracking Number:</strong> <?php echo htmlspecialchars($row['tracking_number']); ?></p>
                            <div class="actions">
                                <a href="update_order.php?order_id=<?php echo $row['order_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit me-1"></i> Update
                                </a>
                            </div>
                        </div>
                    <?php } 
                } else { ?>
                    <div class="alert alert-info">No orders found.</div>
                <?php } ?>
            </div>

            <!-- Table View Container -->
            <div id="table-view-container" class="table-view-container">
                <table class="table-view">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Order Date</th>
                            <th>User</th>
                            <th>Shipping Address</th>
                            <th>Total Amount</th>
                            <th>Amount Paid</th>
                            <th>Payment Status</th>
                            <th>Order Status</th>
                            <th>Tracking Number</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_courier_dashboard->num_rows > 0) {
                            $result_courier_dashboard->data_seek(0);
                            while ($row = $result_courier_dashboard->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['order_id']); ?></td>
                                    <td><?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($row['order_date']))); ?></td>
                                    <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                                    <td><?php echo htmlspecialchars($row['shipping_address']); ?></td>
                                    <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                                    <td>₱<?php echo number_format($row['amount_paid'], 2); ?></td>
                                    <td><?php echo getBadge($row['payment_status']); ?></td>
                                    <td><?php echo getBadge($row['order_status'], true); ?></td>
                                    <td><?php echo htmlspecialchars($row['tracking_number']); ?></td>
                                    <td><a href="update_order.php?order_id=<?php echo $row['order_id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Update</a></td>
                                </tr>
                            <?php }
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
            function setView(view) {
                const orderContainer = document.getElementById('order-container');
                const tableViewContainer = document.getElementById('table-view-container');
                if (view === 'table') {
                    orderContainer.style.display = 'none';
                    tableViewContainer.style.display = 'block';
                } else {
                    orderContainer.style.display = 'block';
                    tableViewContainer.style.display = 'none';
                }
            }
        </script>

    </body>
    </html>

    <?php
    function getBadge($status, $isOrder = false) {
        if ($isOrder) {
            return match ($status) {
                'Packed' => '<span class="badge bg-info">Packed</span>',
                'Delivered' => '<span class="badge bg-success">Delivered</span>',
                default => $status,
            };
        } else {
            return match ($status) {
                'Unpaid' => '<span class="badge bg-danger">Unpaid</span>',
                'Partial' => '<span class="badge bg-warning text-dark">Partial</span>',
                'Paid' => '<span class="badge bg-success">Paid</span>',
                default => $status,
            };
        }
    }
    $stmt->close();
    $conn->close();
    ?>
