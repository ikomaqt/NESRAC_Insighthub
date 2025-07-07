<?php
session_start();
include("config.php");
include("navbar.php");

// Check if the user is logged in
$user_email = ""; // Initialize variable

if (isset($_SESSION['userid'])) {
    $userid = $_SESSION['userid'];

    // Fetch the user's email from the user_account table
    $sql = "SELECT emailAdd FROM user_account WHERE userAccountID = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $user_email = $row['emailAdd']; // Updated column name
        }
        $stmt->close();
    } else {
        // Log the error or show a user-friendly message
        error_log("Failed to prepare statement for user email retrieval");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NESRAC InsightHub - About Us</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style/about.css">
</head>
<body>

<!-- About Us Section -->
<section class="about-section">
    <div class="container about-container">
        <div class="about-header text-center">
            <h2>About NESRAC</h2>
            <p>Learn more about the Nueva Ecija Swine Raiser Cooperative</p>
        </div>

        <div class="about-content">
            <div class="row">
                <div class="col-md-6">
                    <img src="img/logo.png" alt="About NESRAC" class="img-fluid">
                </div>
                <div class="col-md-6">
                    <p>The Nueva Ecija Swine Raiser Cooperative (NESRAC) is a group of dedicated individuals and businesses committed to promoting sustainable swine farming in Nueva Ecija, Philippines. Founded with the goal of providing quality pork products to the community, NESRAC also aims to support local farmers by fostering an environment of collaboration and innovation.</p>
                    <p>At NESRAC, we believe in responsible and ethical farming practices that benefit both the farmers and the consumers. We focus on maintaining the highest standards of care for our animals and ensuring the quality of our products.</p>
                    <p>Our mission is to be the leading provider of pork products in the region while empowering local swine raisers with the tools and knowledge they need to succeed in a competitive market.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
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





</body>
</html>
