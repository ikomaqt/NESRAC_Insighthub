<?php
// Check if the session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('config.php'); // Assuming config.php contains the database connection setup

// Get the admin account username from the session (assuming user has logged in)
$username = isset($_SESSION['username']) ? $_SESSION['username'] : ''; // Adjust based on your session logic

// Initialize variables for user data
$firstName = '';
$lastName = '';
$role_name = '';

if ($username) {
    // Updated SQL query to fetch the firstName, lastName, and role_name
    $query = "SELECT a.firstName, a.lastName, r.role_name 
              FROM ADMIN a
              INNER JOIN `ADMIN_account` ac ON a.admin_id = ac.admin_id
              INNER JOIN `role` r ON a.role_id = r.role_id
              WHERE ac.username = ?";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $username); // Bind the username from the session
        $stmt->execute();
        $stmt->bind_result($firstName, $lastName, $role_name);
        $stmt->fetch();
        $stmt->close();
    } else {
        echo "Error preparing query: " . $conn->error;
    }
} else {
    echo "No username found in session!";
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Responsive Sidebar Menu | Nesrac</title>
    <link rel="stylesheet" href="style/admin.css">
    <!-- Boxicons CDN Link -->
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="sidebar">
        <div class="logo-details">
            <img src="img/logo.png" alt="Nesrac Logo" class="logo-image">
            <div class="logo_name">Nesrac</div>
            <i class='bx bx-menu' id="btn"></i>
        </div>
        <div class="user-details">
            <span class="user-name"><?php echo htmlspecialchars($firstName . " " . $lastName); ?></span><br>
            <span class="user-role"><?php echo htmlspecialchars($role_name); ?></span>
        </div>
        <ul class="nav-list">
            <li>
                <a href="dashboard.php">
                    <i class='bx bx-grid-alt'></i>
                    <span class="links_name">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="products.php">
                    <i class="bx bx-box features-item-icon"></i>
                    <span class="links_name">Products</span>
                </a>
            </li>
            <li>
                <a href="order.php">
                    <i class="bx bx-cart features-item-icon"></i>
                    <span class="links_name">Orders</span>
                </a>
            </li>
            <li>
                <a href="member_monitoring.php">
                    <i class='bx bxs-user-detail'></i>
                    <span class="links_name">Members</span>
                </a>
            </li>
            <li>
                <a href="pending_user.php">
                    <i class='bx bxs-user-plus'></i>
                    <span class="links_name">Pending Users</span>
                </a>
            </li>
            <li>
                <a href="sell_requests.php">
                    <i class='bx bxs-dollar-circle'></i>
                    <span class="links_name">Sell Request</span>
                </a>
            </li>
            <li>
                <a href="add_article.php">
                    <i class='bx bxs-news'></i>
                    <span class="links_name">Article</span>
                </a>
            </li>

            <!-- Conditionally render "Admin" link based on session role -->
            <?php if ($role_name !== 'Admin'): ?>
                <li>
                    <a href="admin_signup.php">
                        <i class='bx bxs-group'></i>
                        <span class="links_name">Admin</span>
                    </a>
                </li>
            <?php endif; ?>

            <li class="profile">
                <div class="profile-details"></div>
                <a href="admin_logout.php">
                    <i class='bx bx-log-out' id="log_out"></i>
                </a>
            </li>
        </ul>
    </div>

    <script>
    let sidebar = document.querySelector(".sidebar");
    let closeBtn = document.querySelector("#btn");

    closeBtn.addEventListener("click", ()=> {
        sidebar.classList.toggle("open");
        menuBtnChange(); // calling the function (optional)
    });

    // Function to change sidebar button (optional)
    function menuBtnChange() {
        if (sidebar.classList.contains("open")) {
            closeBtn.classList.replace("bx-menu", "bx-menu-alt-right"); // replacing the icons class
        } else {
            closeBtn.classList.replace("bx-menu-alt-right", "bx-menu"); // replacing the icons class
        }
    }
    </script>
</body>
</html>