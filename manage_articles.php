<?php
// Include the database configuration file
include 'config.php';
include 'admin.php'; // Using admin.php as the navbar

// Handle the status toggle request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form inputs
    $title = $_POST['title'] ?? 'Untitled';
    $description = $_POST['description'] ?? 'No description provided';
    $link = $_POST['link'] ?? '';
    $source = $_POST['source'] ?? 'Unknown';
    $status = $_POST['status'] ?? 'active';

    // Handle the file upload
    $upload_dir = 'uploads/';
    $image_path = '';

    if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === UPLOAD_ERR_OK) {
        $image_name = basename($_FILES['image_url']['name']);
        $target_path = $upload_dir . $image_name;

        if (move_uploaded_file($_FILES['image_url']['tmp_name'], $target_path)) {
            $image_path = $target_path;
        } else {
            $image_path = 'uploads/default.jpg'; // Use a default image
        }
    }

    // Insert into the database
    $sql = "INSERT INTO articles (title, description, image_url, link, source, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $title, $description, $image_path, $link, $source, $status);

    if ($stmt->execute()) {
        echo "<script>alert('Article added successfully!'); window.location.href='add_article.php';</script>";
    } else {
        error_log("Error: " . $stmt->error);
        echo "Error adding article: " . $stmt->error;
    }
    $stmt->close();
}


// Pagination
$limit = 10; // Number of articles per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Fetch articles with pagination
$sql = "SELECT id, title, description, image_url, link, source, status, created_at 
        FROM articles 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Check for errors
if ($result === false) {
    die("Database error: " . $conn->error);
}

// Total Articles for Pagination
$total_articles_query = "SELECT COUNT(*) AS total FROM articles";
$total_result = $conn->query($total_articles_query);
$total_articles = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_articles / $limit);

header("Location: add_article.php");
exit();


?>
