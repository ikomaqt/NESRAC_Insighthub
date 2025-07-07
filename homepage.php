<?php
session_start();
include("config.php");
include("navbar.php");

// Close the connection after fetching data
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NESRAC</title>
  <link rel="stylesheet" href="style/homeuser.css"> <!-- Link to your main CSS file -->
  <!-- SweetAlert2 CSS/JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

  <section class="section_1">
    <img src="img/section1.png" alt="Header Photo">
    <div class="text-overlay">
      <div class="headline">NESRAC</div>
      <div class="subheadline">Insighthub</div>
      <p class="description">
        Crafted from premium, ethically sourced meats, our products are designed to satisfy every palate.
      </p>
    </div>
  </section>

  <section class="section feature" aria-label="feature" data-section>
    <div class="container">
      <h2 class="h2-large section-title">Why Shop with NESRAC?</h2>
      <ul class="flex-list">
        <li class="flex-item">
          <div class="feature-card">
            <img src="img/fresh_meat.png" width="204" height="236" loading="lazy" class="card-icon">
            <h3 class="h3 card-title">Guaranteed Freshness</h3>
            <p class="card-text">
              All our meats adhere to strict freshness standards and are never processed with harmful additives.
            </p>
          </div>
        </li>

        <li class="flex-item">
          <div class="feature-card">
            <img src="img/ethically.png" width="204" height="236" loading="lazy" alt="Completely Cruelty-Free" class="card-icon">
            <h3 class="h3 card-title">Ethically Sourced</h3>
            <p class="card-text">
              Our meats come from farms that practice humane and sustainable farming methods.
            </p>
          </div>
        </li>

        <li class="flex-item">
          <div class="feature-card">
            <img src="img/high_quality.png" width="204" height="236" loading="lazy" alt="Ingredient Sourcing" class="card-icon">
            <h3 class="h3 card-title">Quality Assurance</h3>
            <p class="card-text">
              We ensure that all our meats meet the highest standards of quality and taste, from farm to table.
            </p>
          </div>
        </li>
      </ul>
    </div>
  </section>

  <section class="about-nesrac">
    <div class="container">
      <div class="about-content">
        <div class="about-image">
          <img src="img/about.jpg" alt="About NESRAC">
        </div>
        <div class="about-text">
          <h2 class="about-title">About NESRAC</h2>
          <p>
            The Nueva Ecija Swine Raiser Cooperative (NESRAC) is a group of dedicated individuals and businesses committed to promoting sustainable swine farming in Nueva Ecija, Philippines. Founded with the goal of providing quality pork products to the community, NESRAC also aims to support local farmers by fostering an environment of collaboration and innovation.

At NESRAC, we believe in responsible and ethical farming practices that benefit both the farmers and the consumers. We focus on maintaining the highest standards of care for our animals and ensuring the quality of our products.

Our mission is to be the leading provider of pork products in the region while empowering local swine raisers with the tools and knowledge they need to succeed in a competitive market.
          </p>
        </div>
      </div>
    </div>
  </section>



  <!-- JavaScript function for SweetAlert -->
  <script>
    function showSurvey() {
      Swal.fire({
        title: 'Do you want to sell and track your pigs?',
        text: 'Please let us know if you are interested in managing your pigs.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No',
        reverseButtons: true,
        allowOutsideClick: false,  // Prevent closing by clicking outside
        allowEscapeKey: false,     // Prevent closing by pressing escape
        allowEnterKey: false,      // Prevent closing by pressing enter
        showCloseButton: false     // Disable the close button
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'survey.php'; // Redirect for "Yes"
        } else {
          Swal.fire('Okay!', 'You can always track them later.', 'info');
        }
        updateLastSurveyDate(); // Update survey date regardless of answer
      });
    }

    function updateLastSurveyDate() {
      const xhr = new XMLHttpRequest();
      xhr.open("POST", "last_survey_update.php", true);
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
      xhr.send();  // AJAX request to update last_survey_date
    }
  </script>

  <!-- Show SweetAlert if the user is a member and survey is due -->
  <?php if (isset($_SESSION['show_survey']) && $_SESSION['show_survey'] === true): ?>
    <script>
      showSurvey();
    </script>
    <?php 
    // Reset the session variable after showing the survey
    $_SESSION['show_survey'] = false;
    ?>
  <?php endif; ?>

</body>
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
        margin-bottom: 5px; /* Further reduced margin */
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
</html>
