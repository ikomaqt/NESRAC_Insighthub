<?php  
session_start();
include("config.php");
include("navbar.php");

// Check if the user is logged in
if (isset($_SESSION['userid'])) {
    $userId = $_SESSION['userid'];

    // Fetch cart items with the current price from the products table
    $sql_cart = "SELECT 
                    cart.cart_id, 
                    cart.cart_productid, 
                    cart.cart_quantity, 
                    products.productname, 
                    products.quantity AS remaining_stock, 
                    products.price AS product_price, 
                    products.image AS product_image 
                 FROM cart
                 JOIN products ON cart.cart_productid = products.productid
                 WHERE cart_userid = ?";
    $stmt_cart = $conn->prepare($sql_cart);
    $stmt_cart->bind_param("i", $userId);
    $stmt_cart->execute();
    $result_cart = $stmt_cart->get_result();
} else {
    header("Location: login.php");
    exit();
}

$total_cost = 0.0; // Ensure total_cost is a float
$disableCheckout = false; // Flag to disable checkout if there are out-of-stock items

if ($result_cart->num_rows > 0) {
    while ($row = $result_cart->fetch_assoc()) {
        if ($row['remaining_stock'] == 0 || $row['cart_quantity'] > $row['remaining_stock']) {
            $disableCheckout = true; // Disable checkout if any item is out of stock
        }
        // Calculate the subtotal using the current product price
        $subtotal = $row['product_price'] * floatval($row['cart_quantity']);
        $total_cost += $subtotal;
    }
    $result_cart->data_seek(0); // Reset pointer to the start for further use
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/basket.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Products and Cart</title>
</head>
<body>
    <style>
        body {
            background-image: url('img/News_Header.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f4f4f4;
        }
        .check-out {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            text-align: center;
            border-radius: 5px;
        }
        .check-out:hover {
            background-color: #218838;
        }
        .out-of-stock {
            color: red;
            font-weight: bold;
        }
    </style>
    <div class="container basket">
        <div class="cart basket">
            <a href="homepage.php#shop" class="next-page-button"><i class="fa-solid fa-circle-arrow-left"></i></a>
            <h2>Your Shopping Cart</h2>
            <?php if ($result_cart->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Product Image</th>
                            <th>Product Name</th>
                            <th>Stocks</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                   <tbody>
    <?php while ($row = $result_cart->fetch_assoc()): ?>
        <?php 
            $subtotal = $row['product_price'] * floatval($row['cart_quantity']);
            $imagePath = !empty($row['product_image']) ? 'productimg/' . htmlspecialchars($row['product_image']) : 'img/placeholder.png';
            $outOfStock = $row['remaining_stock'] == 0 || $row['cart_quantity'] > $row['remaining_stock'];
        ?>
        <tr class="cart-item" data-id="<?php echo $row['cart_id']; ?>">
            <td><img src="<?php echo $imagePath; ?>" alt="Product Image" style="width: 100px; height: auto;"></td>
            <td><?php echo htmlspecialchars($row['productname']); ?></td>
            <td><?php echo htmlspecialchars($row['remaining_stock']); ?></td>
            <td>₱<?php echo number_format($row['product_price'], 2); ?></td>
            
<td>
    <div class="quantity-input" style="display: flex; align-items: center;">
        <button class="quantity-decrement" data-id="<?php echo $row['cart_id']; ?>" style="width: 30px; height: 30px; background-color: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer;">-</button>
        <input type="number" name="quantity" id="quantity_<?php echo $row['cart_id']; ?>" value="<?php echo htmlspecialchars($row['cart_quantity']); ?>" step="0.1" min="0.1" max="<?php echo htmlspecialchars($row['remaining_stock']); ?>" data-max-stock="<?php echo htmlspecialchars($row['remaining_stock']); ?>" data-price="<?php echo htmlspecialchars($row['product_price']); ?>" style="width: 60px; text-align: center; margin: 0 5px;">
        <button class="quantity-increment" data-id="<?php echo $row['cart_id']; ?>" style="width: 30px; height: 30px; background-color: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer;">+</button>
    </div>
</td>

            
            <td>₱<span id="subtotal_<?php echo $row['cart_id']; ?>"><?php echo number_format($subtotal, 2); ?></span></td>
            <td>
                <?php if ($outOfStock): ?>
                    <span class="out-of-stock">Out of stock or insufficient stock</span>
                <?php endif; ?>
                <form method="post" action="removecart.php" style="margin-top: 10px;">
                    <input type="hidden" name="cart_item_id" value="<?php echo htmlspecialchars($row['cart_id']); ?>">
                    <input type="submit" value="Remove" style="background-color: #dc3545; color: white; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer;">
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
</tbody>

                    <tfoot>
                        <tr>
                            <td colspan="7" style="text-align:right;">
                                <strong>Total: ₱<span id="total_cost"><?php echo number_format($total_cost, 2); ?></span></strong>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                <?php if (!$disableCheckout): ?>
                    <a href="checkout.php" class="check-out">Proceed to Checkout</a>
                <?php else: ?>
                    <span style="color: red; font-weight: bold;">Please update your cart before proceeding to checkout.</span>
                <?php endif; ?>
            <?php else: ?>
                <p>Your cart is empty.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        <?php if ($disableCheckout): ?>
            Swal.fire({
                icon: 'error',
                title: 'Checkout Disabled',
                text: 'Some items in your cart are out of stock or have insufficient stock. Please update your cart.',
                confirmButtonColor: '#d33'
            });
        <?php endif; ?>

 document.addEventListener("DOMContentLoaded", () => {
    const updateSubtotal = (cartId, newQuantity, price) => {
        const subtotalElement = document.getElementById(`subtotal_${cartId}`);
        const newSubtotal = newQuantity * price;
        subtotalElement.textContent = newSubtotal.toFixed(2);

        // Update total cost
        let totalCost = 0;
        document.querySelectorAll('span[id^="subtotal_"]').forEach(sub => {
            totalCost += parseFloat(sub.textContent);
        });
        document.getElementById('total_cost').textContent = totalCost.toFixed(2);
    };

    const updateQuantityInDatabase = (cartId, quantity) => {
        fetch("updatequantity.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `cart_item_id=${cartId}&quantity=${quantity}`
        })
        .then(response => response.text())
        .then(data => {
            if (data.trim() === "Error") {
                alert("Failed to update quantity. Please try again.");
            }
        })
        .catch(error => console.error("Error updating cart quantity:", error));
    };

    document.querySelectorAll('.quantity-decrement').forEach(button => {
        button.addEventListener('click', () => {
            const cartId = button.getAttribute('data-id');
            const quantityInput = document.getElementById(`quantity_${cartId}`);
            const price = parseFloat(quantityInput.getAttribute('data-price'));
            let quantity = parseFloat(quantityInput.value);

            if (quantity > parseFloat(quantityInput.min)) {
                quantity -= parseFloat(quantityInput.step);
                quantityInput.value = quantity.toFixed(1);
                updateSubtotal(cartId, quantity, price);
                updateQuantityInDatabase(cartId, quantity);
            }
        });
    });

    document.querySelectorAll('.quantity-increment').forEach(button => {
        button.addEventListener('click', () => {
            const cartId = button.getAttribute('data-id');
            const quantityInput = document.getElementById(`quantity_${cartId}`);
            const price = parseFloat(quantityInput.getAttribute('data-price'));
            let quantity = parseFloat(quantityInput.value);

            if (quantity < parseFloat(quantityInput.max)) {
                quantity += parseFloat(quantityInput.step);
                quantityInput.value = quantity.toFixed(1);
                updateSubtotal(cartId, quantity, price);
                updateQuantityInDatabase(cartId, quantity);
            }
        });
    });

    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('change', () => {
            const cartId = input.id.split('_')[1];
            const price = parseFloat(input.getAttribute('data-price'));
            let quantity = parseFloat(input.value);

            if (quantity > parseFloat(input.max)) {
                quantity = parseFloat(input.max);
                input.value = quantity.toFixed(1);
            } else if (quantity < parseFloat(input.min)) {
                quantity = parseFloat(input.min);
                input.value = quantity.toFixed(1);
            }
            updateSubtotal(cartId, quantity, price);
            updateQuantityInDatabase(cartId, quantity);
        });
    });
});


    </script>
</body>
</html>
