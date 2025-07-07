<?php
session_start();
include("config.php");
include("navbar.php");

// Check if the user is logged in
if (isset($_SESSION['userid'])) {
    $userid = $_SESSION['userid'];
    
    // Fetch the user's email from the user_account table (correct column is emailAdd)
    $sql = "SELECT emailAdd FROM user_account WHERE userAccountID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userid);  // Bind the user ID
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check if the email was found
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_email = $row['emailAdd'];  // Use the correct column name
    } else {
        $user_email = ""; // Fallback if no email found
    }
    $stmt->close();
} else {
    $user_email = ""; // Fallback for non-logged-in users
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NESRAC InsightHub - Contact Us</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style/contact.css">
</head>
<body>
  
  <!-- Contact Us Section -->
  <section class="contact-section">
    <div class="container contact-container">
      <div class="contact-header">
        <h2>Contact Us</h2>
        <p>We would like to hear from you. For questions, comments, and suggestions, please contact us at:</p>
        <div class="contact-details">
          <p>Email: <a href="mailto:nesrac22@gmail.com">nesrac22@gmail.com</a></p>
          <p>Mobile: 09175962963</p>
        </div>
      </div>

      <form class="contact-form" method="POST" action="send_feedback.php">
   
    <label for="email">Your email:</label>
    <input type="email" id="email" class="form-control" placeholder="Enter your email address" name="email" value="<?php echo htmlspecialchars($user_email); ?>" required>

    <label for="enquiry">Enquiry:</label>
    <textarea id="enquiry" class="form-control" placeholder="Enter your enquiry" name="message" required></textarea>

    <button type="submit" class="btn btn-success mt-3">Submit</button>
       </form>
    </div>
  </section>
   

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
                <li><i class="fas fa-map-marker-alt"></i> Campos, Talavera Nueva Ecija</li>
                <li><i class="fas fa-phone"></i> (044) 123-4567</li>
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

<style>
    footer {
        background-color: #333;
        color: white;
        padding: 10px 0; /* Further reduced padding */
    }
    .footer-container {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        max-width: 1200px;
        margin: 0 auto;
    }
    .footer-section {
        flex-basis: 22%;
        margin: 5px; /* Further reduced margin */
    }
    .footer-section h3 {
        margin-bottom: 32px; /* Further reduced margin */
        font-size: 15px; /* Further reduced font size */
    }
    .footer-section p {
        font-size: 12px; /* Further reduced font size */
        line-height: 1.6; /* Further reduced line-height */
    }
    .footer-section ul {
        list-style: none;
        padding: 0;
    }
    .footer-section ul li {
        margin-bottom: 3px; /* Further reduced margin */
    }
    .footer-section ul li i {
        margin-right: 3px; /* Further reduced margin */
    }
    .footer-section ul li a {
        color: white;
        text-decoration: none;
    }
    .footer-section ul li a:hover {
        color: #00aaff;
    }
    .footer-section.social a {
        display: inline-block;
        margin-right: 5px; /* Small margin for social icons */
        color: white;
        font-size: 16px; /* Further reduced font size */
        transition: color 0.3s ease;
    }
    .footer-section.social a:hover {
        color: #00aaff;
    }
    .footer-bottom {
        text-align: center;
        margin-top: 5px; /* Further reduced margin */
    }
    .footer-bottom p {
        font-size: 11px; /* Further reduced font size */
        margin: 0;
    }
</style>
</body>
</html>
