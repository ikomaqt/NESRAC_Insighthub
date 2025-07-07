<?php
session_start();
include("config.php");

// Check if the user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

// Fetch all accepted pigs from the accepted_pigs table
$query_accepted_pigs = "SELECT ap.id, 
                               psr.batch_number, 
                               psr.piglet_count, 
                               psr.birthdate, 
                               ap.weight, 
                               ap.price, 
                               ap.meat_produced, 
                               ap.created_at 
                        FROM accepted_pigs ap
                        LEFT JOIN pig_sell_requests psr ON ap.pig_sell_request_id = psr.id
                        ORDER BY ap.created_at DESC";
$result_accepted_pigs = $conn->query($query_accepted_pigs);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accepted Pigs</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: #fff;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .view-meat-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background-color: #17a2b8;
            color: white;
        }
        .view-meat-btn:hover {
            background-color: #138496;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Accepted Pigs</h2>
        <?php if ($result_accepted_pigs->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Pig ID</th>
                        <th>Batch Number</th>
                        <th>Piglet Count</th>
                        <th>Birthdate</th>
                        <th>Weight (kg)</th>
                        <th>Price (₱)</th>
                        <th>Meat Produced</th>
                        <th>Accepted Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result_accepted_pigs->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['batch_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['piglet_count']); ?></td>
                            <td><?php echo htmlspecialchars($row['birthdate']); ?></td>
                            <td><?php echo htmlspecialchars($row['weight']); ?></td>
                            <td><?php echo htmlspecialchars($row['price']); ?></td>
                            <td><?php echo htmlspecialchars($row['meat_produced']); ?></td>
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                            <td class="action-buttons">
                                <button class="view-meat-btn" onclick="viewMeatProduced(<?php echo $row['id']; ?>)">View Meat</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div>No accepted pigs found.</div>
        <?php endif; ?>
    </div>

    <script>
        function viewMeatProduced(pigId) {
            // AJAX call to fetch the meat produced for the pig
            $.post('view_meat_produced.php', { pig_id: pigId }, function(response) {
                let data = JSON.parse(response);
                Swal.fire({
                    title: 'Meat Produced',
                    html: data.meat_produced,
                    icon: 'info'
                });
            });
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>
