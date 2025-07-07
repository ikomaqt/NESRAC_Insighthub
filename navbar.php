<?php
// Check if the session is already started before calling session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure the user's membership status is stored in the session
$user_status = isset($_SESSION['status']) ? $_SESSION['status'] : 'Non-Member';  // Default to 'Non-Member' if not set
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NESRAC InsightHub</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css">
    <!-- Custom Navbar CSS -->
    <link rel="stylesheet" href="style/navbar.css">
</head>
<body>

    <!-- Start of Navbar Container -->
    <div id="navbar-container">
        <nav class="navbar navbar-expand-custom navbar-mainbg">
            <a class="navbar-brand navbar-logo" href="#">
                <h5>NESRAC InsightHub</h5>
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars text-white"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ml-auto">
                    <div class="hori-selector">
                        <div class="left"></div>
                        <div class="right"></div>
                    </div>
                    <li class="nav-item">
                        <a class="nav-link" href="homepage.php"><i class="fas fa-home"></i>Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="shop_product.php"><i class="fas fa-boxes"></i>Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="news.php"><i class="fas fa-newspaper"></i>News</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php"><i class="fas fa-info-circle"></i>About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php"><i class="fas fa-envelope"></i>Contact</a>
                    </li>
                    <!-- Shopping Cart Icon -->
                    <li class="nav-item">
                        <a class="nav-link" href="basket.php"><i class="fas fa-shopping-cart"></i></a>
                    </li>
                    
                    <?php if ($user_status === 'Member'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="survey.php"><i class="fas fa-tag"></i>Survey</a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><i class="fas fa-user"></i></a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
    <!-- End of Navbar Container -->

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

    <script>
    // Function to position the hori-selector under the active item
    function setHoriSelectorPosition(transition = true) {
        var tabsNewAnim = $('#navbarSupportedContent');
        var activeItemNewAnim = tabsNewAnim.find('.active');

        // Check if there is an active item
        if (activeItemNewAnim.length > 0) {
            var activeWidthNewAnimHeight = activeItemNewAnim.innerHeight();
            var activeWidthNewAnimWidth = activeItemNewAnim.innerWidth();
            var itemPosNewAnim = activeItemNewAnim.position();

            $(".hori-selector").css({
                "top": itemPosNewAnim.top + "px", 
                "left": itemPosNewAnim.left + "px",
                "height": activeWidthNewAnimHeight + "px",
                "width": activeWidthNewAnimWidth + "px",
                "transition": transition ? "all 0.8s ease-in-out" : "none"
            }).show(); // Show the selector if an active item exists
        } else {
            $(".hori-selector").hide(); // Hide the selector if no active item
        }
    }

    $(document).ready(function(){
        // Set active class based on the current URL path
        var path = window.location.pathname.split("/").pop();

        // Fallback to homepage.php if the path is empty
        if (path == '') {
            path = 'homepage.php';
        }

        var target = $('#navbarSupportedContent ul li a[href="'+path+'"]');
        target.parent().addClass('active');

        // Initially position the selector under the active menu item without transition
        setHoriSelectorPosition(false);

        // Add the transition after initial load
        setTimeout(function() {
            setHoriSelectorPosition(true);
        }, 10);

        // Adjust the .hori-selector position on clicking a menu item
        $("#navbarSupportedContent").on("click", "li", function(){
            $('#navbarSupportedContent ul li').removeClass("active");
            $(this).addClass('active');
            setHoriSelectorPosition(); // Apply transition on click
        });

        // Adjust the .hori-selector position on window resize
        $(window).on('resize', function(){
            setTimeout(function() {
                setHoriSelectorPosition();
            }, 500);
        });

        // Toggle navbar on small screens and adjust .hori-selector position
        $(".navbar-toggler").click(function(){
            setTimeout(function() {
                setHoriSelectorPosition();
            }, 300);
        });
    });
    </script>

</body>
</html>
