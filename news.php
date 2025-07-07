<?php
// Include the database configuration file
include 'config.php';
include("navbar.php");

// Fetch only active articles
$sql = "SELECT id, title, description, image_url, link, source, created_at FROM articles WHERE status = 'active' ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pork News</title>
    <link rel="stylesheet" href="style/homeuser.css">
    <link rel="stylesheet" href="style/footer.css">
    <style>
        /* Styling for header section and news articles */
        .section_1 {
            position: relative;
            height: 25vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f4f4f9;
            overflow: hidden;
        }

        .imagehead {
            max-height: 100%;
            width: auto;
            object-fit: contain;
            margin: 0 auto;
            display: block;
        }

        .text-overlay {
            position: absolute;
            z-index: 1;
            text-align: center;
            color: #FFF;
            padding: 0 20px;
        }

        .news-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            max-width: 1200px;
            margin: 20px auto;
        }

        .news-item {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .news-item:hover {
            transform: scale(1.02);
        }

        .news-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .news-content {
            padding: 20px;
        }

        .news-title {
            font-size: 1.5em;
            margin-bottom: 10px;
            color: #333;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .news-title:hover {
            color: #007bff;
        }

        .news-description {
            font-size: 1em;
            color: #555;
            margin-bottom: 15px;
        }

        .news-source, .news-time {
            font-size: 0.9em;
            color: #888;
        }

        .news-source {
            margin-right: 10px;
        }
    </style>
</head>
<body>

<!-- News Header Section -->
<section class="section_1">
    <img class="imagehead" src="img/News_Header.png" alt="News Header Image">
    <div class="text-overlay">
        <div class="headline">Pork News</div>
        <div class="subheadline">Latest Updates and Articles</div>
        <p>Stay updated with the latest trends, tips, and news in the pork industry.</p>
    </div>
</section>

<!-- News Articles Section -->
<section class="news-section" aria-label="Pork News">
    <div class="news-grid">
        <?php
        // Check if there are any articles
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $article_link = !empty($row["link"]) ? htmlspecialchars($row["link"]) : "article_" . htmlspecialchars($row["id"]) . ".php";
                $description = htmlspecialchars($row["description"]);
                if (strlen($description) > 150) {
                    $description = substr($description, 0, 150) . '... <a href="' . $article_link . '">Read more</a>';
                }

                echo '
                <div class="news-item">
                    <img src="' . htmlspecialchars($row["image_url"]) . '" alt="' . htmlspecialchars($row["title"]) . '" class="news-image">
                    <div class="news-content">
                        <a href="' . $article_link . '" class="news-title">' . htmlspecialchars($row["title"]) . '</a>
                        <p class="news-description">' . $description . '</p>
                        <span class="news-source">' . htmlspecialchars($row["source"]) . '</span>
                        <span class="news-time">' . htmlspecialchars(date('F j, Y', strtotime($row["created_at"]))) . '</span>
                    </div>
                </div>';
            }
        } else {
            echo "<p>No articles found.</p>";
        }

        $conn->close();
        ?>
    </div>
</section>

<footer>
    <div class="footer-container">
        <p>&copy; <?php echo date('Y'); ?> NESRAC. All rights reserved.</p>
    </div>
</footer>
</body>
</html>
