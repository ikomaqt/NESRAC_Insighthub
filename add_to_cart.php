<?php
session_start();
include("config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['quantity'], $_POST['productid'], $_POST['price'])) {
        // Sanitize and validate inputs
        $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_FLOAT);
        $productid = filter_var($_POST['productid'], FILTER_VALIDATE_INT);
        $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);

        // Check for valid values
        if ($quantity === false || $productid === false || $price === false || $quantity <= 0) {
            header("Location: homepage.php?error=Invalid input values#shop");
            exit();
        }

        // Ensure user ID is set and valid
        if (isset($_SESSION['userid']) && is_int($_SESSION['userid'])) {
            $userId = $_SESSION['userid'];

            // Check if product already exists in the cart
            $stmt = $conn->prepare("SELECT cart_id, cart_quantity FROM cart WHERE cart_userid = ? AND cart_productid = ?");
            if (!$stmt) die("SQL Error: " . $conn->error);

            $stmt->bind_param("ii", $userId, $productid);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Product exists, update the quantity
                $row = $result->fetch_assoc();
                $cartId = $row['cart_id'];
                $stmt = $conn->prepare("UPDATE cart SET cart_quantity = cart_quantity + ? WHERE cart_id = ?");
                if (!$stmt) die("SQL Error: " . $conn->error);

                $stmt->bind_param("di", $quantity, $cartId);
                $stmt->execute();
                $stmt->close();
            } else {
                // Insert a new product into the cart
                $stmt = $conn->prepare("INSERT INTO cart (cart_userid, cart_productid, cart_price, cart_quantity) VALUES (?, ?, ?, ?)");
                if (!$stmt) die("SQL Error: " . $conn->error);

                $stmt->bind_param("iidd", $userId, $productid, $price, $quantity);
                $stmt->execute();
                $stmt->close();
            }

            // Redirect to the shop product page
            header("Location: shop_product.php");
            exit();
        } else {
            echo "Error: User ID not found. Please log in.";
        }
    } else {
        echo "Error: Required form data missing.";
    }
} else {
    echo "Error: Invalid request method.";
}

$conn->close();
?>
