<?php
session_start();
include("config.php");
include("admin.php");

// Check if the user is logged in

// Fetch all pending sell requests (both members and non-members)
$query_pending = "SELECT psr.id, 
                         u.firstname AS firstname, 
                         u.lastname AS lastname, 
                         u.contactNumber AS number, 
                         psr.batch_number, 
                         psr.piglet_count, 
                          
                         psr.request_date 
                  FROM pig_sell_requests psr
                  LEFT JOIN pig_survey ps ON psr.survey_id = ps.id
                  LEFT JOIN user u ON ps.userid = u.userID
                  WHERE psr.status = 'pending'
                  ORDER BY psr.request_date DESC";
$result_pending = $conn->query($query_pending);

$query_total_pending_pigs = "SELECT SUM(piglet_count) AS total_pending_pigs 
                             FROM pig_sell_requests 
                             WHERE status = 'pending'";
$result_total_pending_pigs = $conn->query($query_total_pending_pigs);
$total_pending_pigs = $result_total_pending_pigs->fetch_assoc()['total_pending_pigs'] ?? 0;

// Fetch all accepted sell requests (both members and non-members)
$query_accepted = "SELECT psr.id, 
                           u.firstname AS firstname, 
                           u.lastname AS lastname, 
                           u.contactNumber AS number,  
                           psr.batch_number, 
                           psr.piglet_count, 
                           
                           psr.request_date, 
                           psr.weight, 
                           psr.price AS buy_price, 
                           psr.is_processed 
                    FROM pig_sell_requests psr
                    LEFT JOIN pig_survey ps ON psr.survey_id = ps.id
                    LEFT JOIN user u ON ps.userid = u.userID
                    WHERE psr.status = 'accepted'
                    ORDER BY psr.processed_at DESC";
$result_accepted = $conn->query($query_accepted);

// Fetch total kilos, total buy price, and total process price
$query_totals = "SELECT 
                    SUM(pm.quantity) AS total_kilos, 
                    SUM(pm.quantity * p.price) AS total_process_price, -- Processing price (quantity * product price)
                    SUM(psr.price) AS total_buy_price -- Total buying price from pig_sell_requests
                 FROM processed_meat pm
                 JOIN products p ON pm.product_id = p.productid
                 JOIN pig_sell_requests psr ON pm.sell_request_id = psr.id
                 WHERE psr.status = 'accepted'";
$result_totals = $conn->query($query_totals);
$totals = $result_totals->fetch_assoc();
$total_kilos = $totals['total_kilos'];
$total_buy_price = $totals['total_buy_price'];
$total_process_price = $totals['total_process_price'];

// Fetch the list of products to show in the SweetAlert form
$query_products = "SELECT productid, productname FROM products";
$result_products = $conn->query($query_products);

$product_options = [];
while ($product = $result_products->fetch_assoc()) {
    $product_options[] = $product;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell Requests</title>
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
            max-width: 1300px;
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
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        .accept-btn, .decline-btn, .process-btn {
            border-radius: 5px;
            color: white;
            cursor: pointer;
            border: none;
            padding: 10px 20px;
        }
        .accept-btn {
            background-color: #28a745;
        }
        .decline-btn {
            background-color: red;
        }
        .process-btn {
            background-color: #007bff;
        }
        .processed-label {
            color: #28a745;
            font-weight: bold;
        }
        .toggle-buttons {
            text-align: center;
            margin-bottom: 20px;
        }
        .toggle-buttons button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            background-color: #007bff;
            color: white;
            font-size: 16px;
        }
        .toggle-buttons button.active {
            background-color: #0056b3;
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
        .swal-wide {
    width: 30% !important; /* Adjust as needed for pop-up width */
}
.product-grid-container {
    display: grid;
    grid-template-columns: auto 1fr; /* Two columns: labels on the left, fields on the right */
    gap: 15px 10px; /* Horizontal and vertical gap */
    margin-top: 10px;
    align-items: center;
}

.product-grid-item {
    display: contents; /* To allow each child (label and input) to take its column */
}

.product-grid-item label {
    font-weight: bold;
    text-align: right;
    margin-right: 10px; /* Space between label and input */
}

.product-grid-item input {
    width: 60%; /* Full width for inputs */
}



    </style>
</head>
<body>
    <div class="container">
        <h2>Sell Requests</h2>

        <!-- Toggle between Pending and Accepted Requests -->
        <div class="toggle-buttons">
            <button id="pending-toggle" class="active" onclick="showTable('pending')">Pending Requests</button>
            <button id="accepted-toggle" onclick="showTable('accepted')">Accepted Requests</button>
        </div>

        <!-- Pending Requests Table -->
        <div id="pending-table" style="display: block;">
            <?php if ($result_pending->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>User Name</th>
                            <th>Contact Number</th>
                            <th>Batch Number</th>
                            <th>Pig Count</th>
                            <th>Request Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
<?php while ($row = $result_pending->fetch_assoc()): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['id']); ?></td>
        <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
        <td><?php echo htmlspecialchars($row['number']); ?></td>
        <td><?php echo htmlspecialchars($row['batch_number']); ?></td>
        <td><?php echo htmlspecialchars($row['piglet_count']); ?></td>
        <td><?php echo htmlspecialchars($row['request_date']); ?></td>
        <td class="action-buttons">
            <!-- Pass piglet_count as a parameter to acceptRequest -->
            <button class="accept-btn" onclick="acceptRequest(<?php echo $row['id']; ?>, <?php echo $row['piglet_count']; ?>)">Accept</button>
            <button class="decline-btn" onclick="declineRequest(<?php echo $row['id']; ?>)">Decline</button>
        </td>
    </tr>
<?php endwhile; ?>

                    </tbody>
                </table>
            <?php else: ?>
                <div>No pending sell requests found.</div>
            <?php endif; ?>
        </div>

        <!-- Accepted Requests Table -->
        <div id="accepted-table" style="display: none;">
            <?php if ($result_accepted->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>User Name</th>
                            <th>Contact Number</th>
                            <th>Batch Number</th>
                            <th>Pig Count</th>
                            <th>Request Date</th>
                            <th>Weight (kg)</th>
                            <th>Buying Price (₱)</th>
                            <th>Actions</th>
                        </tr>
                   </thead>
                    <tbody>
                        <?php while ($row = $result_accepted->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                                <td><?php echo htmlspecialchars($row['number']); ?></td>
                                <td><?php echo htmlspecialchars($row['batch_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['piglet_count']); ?></td>
                                <td><?php echo htmlspecialchars($row['request_date']); ?></td> 
                                <td><?php echo htmlspecialchars($row['weight']); ?></td>
                                <td><?php echo htmlspecialchars($row['buy_price']); ?></td>
                                <td class="action-buttons">
                                    <?php if ($row['is_processed']): ?>
                                        <span class="processed-label">Processed</span>
                                    <?php else: ?>
                                       <button class="process-btn" onclick="processMeat(<?php echo $row['id']; ?>, <?php echo $row['weight']; ?>)">Process Meat</button>

                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div>No accepted sell requests found.</div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const products = <?php echo json_encode($product_options); ?>;

        function showTable(table) {
            const pendingTable = document.getElementById('pending-table');
            const acceptedTable = document.getElementById('accepted-table');
            const pendingToggle = document.getElementById('pending-toggle');
            const acceptedToggle = document.getElementById('accepted-toggle');

            if (table === 'pending') {
                pendingTable.style.display = 'block';
                acceptedTable.style.display = 'none';
                pendingToggle.classList.add('active');
                acceptedToggle.classList.remove('active');
            } else {
                pendingTable.style.display = 'none';
                acceptedTable.style.display = 'block';
                pendingToggle.classList.remove('active');
                acceptedToggle.classList.add('active');
            }
        }

function processMeat(requestId, liveWeight) {
    let productFields = '';
    products.forEach(product => {
        productFields += `
            <div class="product-grid-item">
                <label>${product.productname}</label>
                <input type="number" id="swal-input-product-${product.productid}" class="swal2-input" step="0.01" placeholder="Quantity (kg)" required>
            </div>
        `;
    });

    Swal.fire({
        title: 'Process Meat',
        html: `
            <div class="product-grid-container">
                ${productFields}
            </div>
            <div style="margin-top: 20px;">
                <label style="font-weight: bold;">Total Live Weight: ${liveWeight} kg</label>
                
            </div>
        `,
        customClass: {
            popup: 'swal-wide'
        },
        focusConfirm: false,
        confirmButtonText: 'Process',
        confirmButtonColor: '#007bff',
        showCancelButton: true,
        cancelButtonText: 'Cancel',
        cancelButtonColor: '#d33',
        preConfirm: () => {
            let totalWeight = 0;
            let processedProducts = [];

            products.forEach(product => {
                const quantity = parseFloat(document.getElementById(`swal-input-product-${product.productid}`).value) || 0;

                if (quantity < 0) {
                    Swal.showValidationMessage('Quantities cannot be negative.');
                    return;
                }

                totalWeight += quantity;

                processedProducts.push({ productId: product.productid, quantity: quantity });
            });

            if (totalWeight > liveWeight) {
                Swal.showValidationMessage(`Total weight exceeds the live weight (${liveWeight} kg).`);
                return;
            }

            if (processedProducts.length === 0) {
                Swal.showValidationMessage('Please enter the quantities for at least one product.');
                return;
            }

            return processedProducts;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'process_meat.php';

            const requestIdInput = document.createElement('input');
            requestIdInput.type = 'hidden';
            requestIdInput.name = 'request_id';
            requestIdInput.value = requestId;
            form.appendChild(requestIdInput);

            result.value.forEach(product => {
                const productIdInput = document.createElement('input');
                productIdInput.type = 'hidden';
                productIdInput.name = 'product_ids[]';
                productIdInput.value = product.productId;
                form.appendChild(productIdInput);

                const quantityInput = document.createElement('input');
                quantityInput.type = 'hidden';
                quantityInput.name = 'quantities[]';
                quantityInput.value = product.quantity;
                form.appendChild(quantityInput);
            });

            document.body.appendChild(form);
            form.submit();
        }
    });

    // Update remaining weight dynamically
    products.forEach(product => {
        const inputField = document.getElementById(`swal-input-product-${product.productid}`);
        inputField.addEventListener('input', () => {
            let totalWeight = 0;

            products.forEach(product => {
                const quantity = parseFloat(document.getElementById(`swal-input-product-${product.productid}`).value) || 0;
                totalWeight += quantity;
            });

            const remainingWeight = liveWeight - totalWeight;
            const remainingWeightElement = document.getElementById('remaining-weight');
            remainingWeightElement.textContent = `Remaining Weight: ${remainingWeight.toFixed(2)} kg`;

            if (remainingWeight < 0) {
                remainingWeightElement.style.color = 'red';
            } else {
                remainingWeightElement.style.color = 'green';
            }
        });
    });
}



function acceptRequest(requestId, pigletCount) {
    Swal.fire({
        title: 'Accept Sell Request',
        html: `
            <div>
                <label style="font-weight: bold;">Number of Pigs to Accept (Max: ${pigletCount}):</label>
                <input id="swal-input-pigs" type="number" min="1" max="${pigletCount}" class="swal2-input" placeholder="Enter number of pigs" required>
            </div>
            <div id="weight-container" style="margin-top: 15px;"></div>
            <div>
                <label style="font-weight: bold;">Price per Kilo (₱):</label>
                <input id="swal-input-price-per-kilo" type="number" step="0.01" min="0.01" class="swal2-input" placeholder="Enter price per kilo" required>
            </div>
            <div>
                <label style="font-weight: bold;">Total Price (₱):</label>
                <input id="swal-input-total-price" type="text" class="swal2-input" readonly>
            </div>
        `,
        focusConfirm: false,
        confirmButtonText: 'Accept',
        confirmButtonColor: '#28a745',
        showCancelButton: true,
        cancelButtonText: 'Cancel',
        cancelButtonColor: '#d33',
        preConfirm: () => {
            const acceptedPigs = parseInt(document.getElementById('swal-input-pigs').value);
            const pricePerKilo = parseFloat(document.getElementById('swal-input-price-per-kilo').value);
            const weights = [];
            let totalWeight = 0;

            // Validate weights
            for (let i = 1; i <= acceptedPigs; i++) {
                const weight = parseFloat(document.getElementById(`swal-input-pig-${i}-weight`).value);
                if (!weight || weight < 0.01) {
                    Swal.showValidationMessage(`Weight for Pig ${i} must be greater than 0.`);
                    return;
                }
                weights.push(weight);
                totalWeight += weight;
            }

            // Validate price per kilo
            if (!pricePerKilo || pricePerKilo < 0.01) {
                Swal.showValidationMessage('Price per kilo must be greater than 0.');
                return;
            }

            const totalPrice = totalWeight * pricePerKilo;
            if (totalPrice <= 0) {
                Swal.showValidationMessage('Total price must be greater than zero.');
                return;
            }

            return { acceptedPigs, weights, pricePerKilo, totalWeight, totalPrice };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'accept_sell_request.php';

            const requestIdInput = document.createElement('input');
            requestIdInput.type = 'hidden';
            requestIdInput.name = 'request_id';
            requestIdInput.value = requestId;
            form.appendChild(requestIdInput);

            const acceptedPigsInput = document.createElement('input');
            acceptedPigsInput.type = 'hidden';
            acceptedPigsInput.name = 'accepted_pigs';
            acceptedPigsInput.value = result.value.acceptedPigs;
            form.appendChild(acceptedPigsInput);

            const pricePerKiloInput = document.createElement('input');
            pricePerKiloInput.type = 'hidden';
            pricePerKiloInput.name = 'price_per_kilo';
            pricePerKiloInput.value = result.value.pricePerKilo;
            form.appendChild(pricePerKiloInput);

            const totalWeightInput = document.createElement('input');
            totalWeightInput.type = 'hidden';
            totalWeightInput.name = 'total_weight';
            totalWeightInput.value = result.value.totalWeight;
            form.appendChild(totalWeightInput);

            const totalPriceInput = document.createElement('input');
            totalPriceInput.type = 'hidden';
            totalPriceInput.name = 'total_price';
            totalPriceInput.value = result.value.totalPrice;
            form.appendChild(totalPriceInput);

            result.value.weights.forEach((weight, index) => {
                const weightInput = document.createElement('input');
                weightInput.type = 'hidden';
                weightInput.name = `weights[${index}]`;
                weightInput.value = weight;
                form.appendChild(weightInput);
            });

            document.body.appendChild(form);
            form.submit();
        }
    });

    // Dynamically add weight fields
    document.getElementById('swal-input-pigs').addEventListener('input', function () {
        const weightContainer = document.getElementById('weight-container');
        const numPigs = parseInt(this.value) || 0;

        weightContainer.innerHTML = '';
        for (let i = 1; i <= numPigs; i++) {
            const weightField = document.createElement('div');
            weightField.style.marginBottom = '10px';
            weightField.innerHTML = `
                <label style="font-weight: bold;">Pig ${i} Weight (kg):</label>
                <input type="number" id="swal-input-pig-${i}-weight" step="0.01" min="0.01" class="swal2-input" placeholder="Enter weight for Pig ${i}" required>
            `;
            weightContainer.appendChild(weightField);
        }

        updateTotalPrice();
    });

    document.getElementById('swal-input-price-per-kilo').addEventListener('input', updateTotalPrice);

    function updateTotalPrice() {
        const pricePerKilo = parseFloat(document.getElementById('swal-input-price-per-kilo').value) || 0;
        let totalWeight = 0;

        const numPigs = parseInt(document.getElementById('swal-input-pigs').value) || 0;
        for (let i = 1; i <= numPigs; i++) {
            const weight = parseFloat(document.getElementById(`swal-input-pig-${i}-weight`)?.value) || 0;
            totalWeight += weight;
        }

        const totalPrice = totalWeight * pricePerKilo;
        document.getElementById('swal-input-total-price').value = `₱${totalPrice.toFixed(2)}`;
    }
}
function declineRequest(requestId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'Do you want to decline this request and restore the pig count?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, decline it!',
        confirmButtonColor: '#d33',
        cancelButtonText: 'Cancel',
        cancelButtonColor: '#3085d6',
    }).then((result) => {
        if (result.isConfirmed) {
            // Send AJAX request to decline the sell request
            fetch('process_decline_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `request_id=${requestId}`,
            })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    Swal.fire('Declined!', data.success_message || 'Sell request has been declined.', 'success')
                        .then(() => {
                            // Reload the page or update the table dynamically
                            location.reload();
                        });
                } else {
                    Swal.fire('Error!', data.error || 'Failed to decline the request.', 'error');
                }
            })
            .catch((error) => {
                console.error('Error:', error);
                Swal.fire('Error!', 'An unexpected error occurred.', 'error');
            });
        }
    });
}






    </script>
</body>
</html>

<?php
$conn->close();
?>
