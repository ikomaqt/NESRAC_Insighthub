<?php
session_start();
include("config.php");
include("navbar.php");

// Check if the product ID is provided and user is logged in
if (isset($_POST['productid']) && isset($_SESSION['userid'])) {
    $productid = $_POST['productid'];
    $userid = $_SESSION['userid'];
    $quantity = floatval($_POST['quantity']); // Ensure decimal value

    // Get the product price, availability, and available quantity from the database
    $sql = "SELECT price, quantity, availability FROM products WHERE productid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $productid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $pricePerKilo = $product['price'];
        $availableQuantity = $product['quantity'];
        $availability = $product['availability'];

        // Check if the product is available and has enough stock
        if ($availability === 'Available' && $availableQuantity >= $quantity) {
            // Insert product into the cart with the correct price per kilo
            $sql_insert = "INSERT INTO cart (cart_userid, cart_productid, cart_price, cart_quantity) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("iidd", $userid, $productid, $pricePerKilo, $quantity);

            if ($stmt_insert->execute()) {
                // Update the available quantity in the products table
                $sql_update_quantity = "UPDATE products SET quantity = quantity - ? WHERE productid = ?";
                $stmt_update_quantity = $conn->prepare($sql_update_quantity);
                $stmt_update_quantity->bind_param("di", $quantity, $productid);

                if ($stmt_update_quantity->execute()) {
                    header("Location: basket.php");
                    exit();
                } else {
                    echo "Error updating product quantity.";
                }
            } else {
                echo "Error adding to cart.";
            }
        } elseif ($availability !== 'Available') {
            echo "This product is currently unavailable.";
        } else {
            echo "Insufficient stock for this product.";
        }
    } else {
        echo "Product not found.";
    }

    // Close statements
    $stmt->close();
    $stmt_insert->close();
    $stmt_update_quantity->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="style/shop_product.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.min.css">
</head>
<body>

<div class="container">
    <div class="title-wrapper">
        <h2 class="h2 section-title">Products</h2>
        <div class="row mt-4">
<?php
// Fetch products from the database where availability is 'Available'
$sql = "SELECT * FROM products WHERE availability = 'Available'"; // Fetch only available products
$result_products = $conn->query($sql);

if ($result_products->num_rows > 0) {
    while ($row = $result_products->fetch_assoc()) {
        $imagePath = !empty($row['image']) ? 'productimg/' . htmlspecialchars($row['image']) : 'img/placeholder.png';
        echo '<div class="col-md-4 col-sm-6 col-xs-12" style="display: flex; justify-content: center; margin-bottom: 20px;">';
        echo '<div class="card product-card">';
        echo '<img src="' . $imagePath . '" class="product-image" alt="Product Image">';
        echo '<div class="card-body">';
        echo '<h3 class="product-name">' . htmlspecialchars($row['productname']) . '</h3>';
        echo '<p class="product-price">Price per Kilo: ₱' . number_format($row['price'], 2) . '</p>'; // Display price per kilo

        // Use number_format to ensure the quantity displays with two decimal places
        echo '<p class="product-quantity">Available Quantity(Kg): ' . number_format($row['quantity'], 2) . '</p>';

        if ($row['quantity'] > 0) {
            echo '<button type="button" class="btn btn-success buy-now-button" 
                  data-id="' . htmlspecialchars($row['productid']) . '"
                  data-name="' . htmlspecialchars($row['productname']) . '"
                  data-price="' . number_format($row['price'], 2) . '"
                  data-image="' . htmlspecialchars($row['image']) . '"
                  data-quantity="' . number_format($row['quantity'], 2) . '">Buy Now</button>';
        } else {
            echo '<button type="button" class="btn btn-danger" disabled>Out of Stock</button>';
        }
        echo '</div>'; // Close card-body
        echo '</div>'; // Close card
        echo '</div>'; // Close col
    }
} else {
    echo "<p>No available products found.</p>";
}

$conn->close();
?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.all.min.js"></script>
<script type="text/javascript">
$(document).on('click', '.buy-now-button', function() {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const pricePerUnit = parseFloat($(this).data('price'));
    const image = $(this).data('image');
    const maxQuantity = parseFloat($(this).data('quantity'));

Swal.fire({
    html: `
        <div style="display: flex; flex-direction: column; align-items: flex-start; width: 100%; max-width: 600px; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);">
            <div style="display: flex; align-items: flex-start; width: 100%; padding: 20px;">
                <img src="productimg/${image}" 
                    style="width: 200px; height: 200px; object-fit: cover; border-radius: 8px; margin-right: 20px;">
                <div style="flex: 1;">
                    <div style="font-size: 18px; font-weight: bold; margin-bottom: 8px; text-align: left;">${name}</div>
                    <div style="font-size: 16px; margin-bottom: 8px;">Price per Kilo: ₱${pricePerUnit.toFixed(2)}</div> <!-- Display price per kilo -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div style="font-size: 16px; font-weight: bold;">Available Quantity(Kg): ${maxQuantity.toFixed(2)}</div>
                        <div style="display: flex; align-items: center;">
                            <button id="decreaseQuantity" type="button" style="border: 1px solid #ccc; background-color: #f0f0f0; padding: 5px 10px; cursor: pointer;">-</button>
                            <input id="quantityInput" type="number" value="1" min="0.1" max="${maxQuantity.toFixed(2)}" step="0.1"
                                style="width: 60px; height: 30px; text-align: center; border: 1px solid #ccc; margin: 0 5px;">
                            <button id="increaseQuantity" type="button" style="border: 1px solid #ccc; background-color: #f0f0f0; padding: 5px 10px; cursor: pointer;">+</button>
                        </div>
                    </div>
                    <div id="calculatedPrice" style="font-size: 18px; font-weight: bold; color: #000; text-align: left;">₱${pricePerUnit.toFixed(2)}</div>
                </div>
            </div>
            <div style="display: flex; justify-content: space-between; width: 100%; padding: 20px; border-top: 1px solid #eee;">
                <form method="post" action="add_to_cart.php" style="margin: 0;">
                    <input type="hidden" name="productid" value="${id}">
                    <input type="hidden" name="productname" value="${name}">
                    <input type="hidden" name="price" id="hiddenPrice" value="${pricePerUnit}">
                    <input type="hidden" name="quantity" id="hiddenQuantity" value="1">
                    <input type="submit" value="Add to Cart" 
                        style="background-color: #AC1D1D; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 16px; text-align: center; width: 100%;">
                </form>
            </div>
        </div>`,
    showConfirmButton: false,
    showCloseButton: true,
    width: '600px',
    padding: '0',
});

    $('#quantityInput').on('input', function() {
        let inputVal = parseFloat($(this).val());
        if (inputVal < 0.1) {
            $(this).val(0.1);
            inputVal = 0.1;
        } else if (inputVal > maxQuantity) {
            $(this).val(maxQuantity);
            inputVal = maxQuantity;
        }
        const newPrice = (inputVal * pricePerUnit).toFixed(2);
        $('#calculatedPrice').text(`₱${newPrice}`);
        $('#hiddenQuantity').val(inputVal);
        $('#hiddenPrice').val(newPrice);
    });

    $('#decreaseQuantity').on('click', function() {
        let currentVal = parseFloat($('#quantityInput').val());
        if (currentVal > 0.1) {
            $('#quantityInput').val((currentVal - 0.1).toFixed(1)).trigger('input');
        }
    });

    $('#increaseQuantity').on('click', function() {
        let currentVal = parseFloat($('#quantityInput').val());
        if (currentVal < maxQuantity) {
            $('#quantityInput').val((currentVal + 0.1).toFixed(1)).trigger('input');
        }
    });

    $('#checkoutButton').on('click', function() {
        if (confirm('Are you sure you want to proceed to checkout?')) {
            window.location.href = 'checkout.php';
        }
    });
});
</script>

</body>
<footer>
  <footer>
    <div class="footer-container">
        <div class="footer-section about">
            <h3>About NESRAC</h3>
            <p>We are the Nueva Ecija Swine Raiser Cooperative (NESRAC), committed to providing quality pork products while supporting local farmers.</p>
        </div>
        <div class="footer-section links">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="homepage.php">Home</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </div>
        <div class="footer-section contact">
            <h3>Contact Us</h3>
            <ul>
                <li><i class="fas fa-map-marker-alt"></i> Campos, Talavera, Nueva Ecija</li>
                <li><i class="fas fa-phone-alt"></i> (044) 123-4567</li>
                <li><i class="fas fa-envelope"></i> contact@nesrac.com</li>
            </ul>
        </div>
        <div class="footer-section social">
            <h3>Follow Us</h3>
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> NESRAC. All rights reserved.</p>
    </div>
</footer>

<!-- Custom Styles -->
<style>
    /* General Footer Styling */
    footer {
        background-color: #333;
        color: white;
        padding: 40px 0;
        font-family: Arial, sans-serif;
    }

    .footer-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .footer-section {
        flex: 1;
        margin: 20px 0;
    }

    .footer-section h3 {
        font-size: 18px;
        margin-bottom: 15px;
    }

    .footer-section p, .footer-section ul li {
        font-size: 14px;
        line-height: 1.6;
        margin-bottom: 10px;
    }

    .footer-section ul {
        list-style: none;
        padding: 0;
    }

    .footer-section ul li {
        margin-bottom: 10px;
    }

    .footer-section ul li a {
        color: white;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .footer-section ul li a:hover {
        color: #00aaff;
    }

    .footer-section.social a {
        margin-right: 10px;
        color: white;
        font-size: 18px;
        transition: color 0.3s ease;
    }

    .footer-section.social a:hover {
        color: #00aaff;
    }

    .footer-bottom {
        text-align: center;
        padding: 20px;
        background-color: #222;
        font-size: 12px;
    }

    .footer-bottom p {
        margin: 0;
    }

    /* Responsive Footer */
    @media (max-width: 768px) {
        .footer-container {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .footer-section {
            margin-bottom: 20px;
            text-align: center;
        }

        .footer-section ul {
            padding: 0;
            text-align: center;
        }

        .footer-section ul li {
            display: inline-block;
            margin: 0 10px;
        }

        .footer-section.social a {
            font-size: 20px;
            margin: 0 10px;
        }
    }
</style>



</footer>
</html>