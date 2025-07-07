<?php
session_start();
include("config.php");
include("admin.php");

// Handle search query and date filters
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';

// Fetch payment status breakdown for all users
$sql_payment_status = "SELECT 
                        SUM(CASE WHEN payment_status = 'Paid' THEN 1 ELSE 0 END) AS paid,
                        SUM(CASE WHEN payment_status = 'Unpaid' THEN 1 ELSE 0 END) AS unpaid,
                        SUM(CASE WHEN payment_status = 'Partial' THEN 1 ELSE 0 END) AS partial
                       FROM `payment`";
$result_payment_status = $conn->query($sql_payment_status);
$payment_status_data = $result_payment_status->fetch_assoc();

// Fetch order history with search filter and date range filter
$sql_order_history = "SELECT 
                        o.order_id,
                        o.order_date,
                        u.firstName,
                        u.lastName,
                        CONCAT(u.street, ', ', u.barangay, ', ', u.municipality) AS address,
                        p.total_amount,
                        p.amount_paid,
                        p.payment_status,
                        o.order_status,
                        t.tracking_number
                    FROM 
                        `order` o
                    JOIN 
                        `user` u ON o.userid = u.userID
                    JOIN
                        `user_account` a ON u.userID = a.userID
                    LEFT JOIN
                        `payment` p ON o.order_id = p.order_id
                    LEFT JOIN
                        `tracking` t ON o.order_id = t.order_id
                    WHERE 
                        (u.firstName LIKE ? 
                        OR u.lastName LIKE ?
                        OR a.emailAdd LIKE ?)";

// Add date filters and payment status filters as needed
if ($start_date) {
    $sql_order_history .= " AND o.order_date >= ?";
}
if ($end_date) {
    $sql_order_history .= " AND o.order_date <= ?";
}
if ($payment_status) {
    $sql_order_history .= " AND p.payment_status = ?";
}

$sql_order_history .= " ORDER BY o.order_date DESC";


$stmt = $conn->prepare($sql_order_history);

$search_param = '%' . $search_query . '%';
$types = "sss";
$params = [$search_param, $search_param, $search_param];

if ($start_date) {
    $types .= 's';
    $params[] = $start_date;
}
if ($end_date) {
    $types .= 's';
    $params[] = $end_date;
}
if ($payment_status) {
    $types .= 's';
    $params[] = $payment_status;
}

$stmt->bind_param($types, ...$params);

// Debugging query execution
if (!$stmt->execute()) {
    echo "Query Error: " . $stmt->error;
}

$result_order_history = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Order History</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .content {
            margin-left: 220px;
            padding: 20px;
        }
        .card {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .highlighted { background-color: #e0f7fa !important; }
        .export { margin-left: 85%; position: absolute; }
    </style>
</head>
<body>
<div class="content">
    <div class="card mt-4">
        <h1>Order History</h1>
        <div class="mb-4">
            <form class="export" action="export_orders.php" method="get">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                <input type="hidden" name="payment_status" value="<?php echo htmlspecialchars($payment_status); ?>">
                <button type="submit" class="btn btn-success">Export</button>
            </form>
        </div>

        <div class="mb-4">
            <form class="form-inline" action="" method="get">
                <input type="text" class="form-control mr-2" name="search" placeholder="Search by user, address..." value="<?php echo htmlspecialchars($search_query); ?>">
                <input type="date" class="form-control mr-2" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                <input type="date" class="form-control mr-2" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                <select name="payment_status" class="form-control mr-2">
                    <option value="">All Payment Statuses</option>
                    <option value="Paid" <?php echo $payment_status === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="Unpaid" <?php echo $payment_status === 'Unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                    <option value="Partial" <?php echo $payment_status === 'Partial' ? 'selected' : ''; ?>>Partial</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter Orders</button>
            </form>
        </div>

        <table class="table table-striped table-bordered">
            <thead class="thead-dark">
            <tr>
                <th>Order ID</th>
                <th>Order Date</th>
                <th>User</th>
                <!-- <th>Address</th> -->
                <th>Total Amount</th>
                <th>Amount Paid</th>
                <th>Payment Status</th>
                <th>Status</th>
                <th>Tracking Number</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($result_order_history->num_rows > 0): ?>
                <?php while ($row = $result_order_history->fetch_assoc()): ?>
                    <tr class="order-row" data-order-id="<?php echo htmlspecialchars($row['order_id']); ?>" data-total-amount="<?php echo $row['total_amount']; ?>" data-amount-paid="<?php echo $row['amount_paid']; ?>" data-payment-status="<?php echo $row['payment_status']; ?>" data-order-status="<?php echo $row['order_status']; ?>">
                        <td><?php echo htmlspecialchars($row['order_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['order_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['firstName'] . ' ' . $row['lastName']); ?></td>
                        <!-- <td><?php echo htmlspecialchars($row['address']); ?></td> -->
                        <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                        <td>₱<?php echo number_format($row['amount_paid'], 2); ?></td>
                        <td>
                            <?php if ($row['payment_status'] == 'Paid'): ?>
                                <span class="badge badge-success">Paid</span>
                            <?php elseif ($row['payment_status'] == 'Unpaid'): ?>
                                <span class="badge badge-danger">Unpaid</span>
                            <?php elseif ($row['payment_status'] == 'Partial'): ?>
                                <span class="badge badge-warning">Partial</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['order_status'] == 'Pending'): ?>
                                <span class="badge badge-secondary">Pending</span>
                            <?php elseif ($row['order_status'] == 'Packed'): ?>
                                <span class="badge badge-info">Packed</span>
                            <?php elseif ($row['order_status'] == 'Delivered'): ?>
                                <span class="badge badge-success">Delivered</span>
                            <?php elseif ($row['order_status'] == 'Cancelled'): ?>
                                <span class="badge badge-danger">Cancelled</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['tracking_number']); ?></td>
                        <td>
                            <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#orderDetailsModal" data-order-id="<?php echo htmlspecialchars($row['order_id']); ?>">View Details</button>
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#updateStatusModal" data-order-id="<?php echo htmlspecialchars($row['order_id']); ?>" data-current-status="<?php echo htmlspecialchars($row['order_status']); ?>">Update Status</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" class="text-center">No orders found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for Viewing Order Details -->
<div id="orderDetailsModal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Order Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="orderDetails">
        <!-- Order details will be loaded here -->
      </div>
      <div class="modal-footer">
        <!-- Update Payment Form -->
        <form id="updatePaymentForm" action="update_payment.php" method="post">
            <input type="hidden" name="order_id" id="orderIdForPayment">
            <label for="amount_paid">Amount Paid:</label>
            <input type="number" name="amount_paid" id="amountPaid" class="form-control" value="0.00" min="0" step="0.01">
            <label for="payment_status">Payment Status:</label>
            <select name="payment_status" id="paymentStatus" class="form-control">
                <option value="Paid">Paid</option>
                <option value="Unpaid">Unpaid</option>
                <option value="Partial">Partial</option>
            </select>
            <button type="submit" class="btn btn-primary mt-3">Update Payment</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal for Updating Order Status -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">Update Order Status</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="updateStatusForm" action="update_order_status.php" method="post">
                    <input type="hidden" name="order_id" id="modalOrderId">
                    <label for="modalOrderStatus">Order Status:</label>
                    <select name="order_status" id="modalOrderStatus" class="form-control">
                        <option value="Pending">Pending</option>
                        <option value="Packed">Packed</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-primary mt-3">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.11/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        $('#orderDetailsModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var orderId = button.data('order-id'); // Extract order ID
            $('#orderIdForPayment').val(orderId);

            // Fetch order details via AJAX
            $.ajax({
                url: 'fetch_order_details.php',
                type: 'GET',
                data: { order_id: orderId },
                success: function(data) {
                    $('#orderDetails').html(data);
                },
                error: function() {
                    $('#orderDetails').html('<p>An error occurred while fetching the order details.</p>');
                }
            });
        });

        $('#updateStatusModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var orderId = button.data('order-id');
            var currentStatus = button.data('current-status');

            var modal = $(this);
            modal.find('#modalOrderId').val(orderId);
            modal.find('#modalOrderStatus').val(currentStatus);
        });

        // Display SweetAlert2 messages
        <?php if (isset($_SESSION['success_message'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '<?php echo addslashes($_SESSION['success_message']); ?>',
                confirmButtonColor: '#3085d6'
            });
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?php echo addslashes($_SESSION['error_message']); ?>',
                confirmButtonColor: '#d33'
            });
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    });
</script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
