<?php
// Include the database configuration file
include 'config.php';
include 'admin.php'; // Using admin.php as the navbar

// Handle the status toggle request
if (isset($_GET['toggle_id'])) {
    $article_id = $_GET['toggle_id'];

    // Get current status of the article
    $current_status_sql = "SELECT status FROM articles WHERE id = ?";
    $stmt = $conn->prepare($current_status_sql);
    $stmt->bind_param("i", $article_id);
    $stmt->execute();
    $stmt->bind_result($current_status);
    $stmt->fetch();
    $stmt->close();

    // Toggle the status: if it's active, make it inactive; if it's inactive, make it active
    $new_status = ($current_status === 'active') ? 'inactive' : 'active';

    // Update the article's status
    $update_sql = "UPDATE articles SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $new_status, $article_id);

    if ($stmt->execute()) {
        $message = "Article status updated successfully.";
    } else {
        $message = "Error updating status: " . $stmt->error;
    }

    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form inputs
    $title = $_POST['title'];
    $description = $_POST['description'];
    $link = $_POST['link'] ?? '';
    $source = $_POST['source'];
    $status = $_POST['status'];

    // Handle the file upload
    $upload_dir = 'uploads/';
    $image_path = '';

    if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === UPLOAD_ERR_OK) {
        $image_name = basename($_FILES['image_url']['name']);
        $target_path = $upload_dir . $image_name;

        if (move_uploaded_file($_FILES['image_url']['tmp_name'], $target_path)) {
            $image_path = $target_path;
        } else {
            $message = "Error uploading the file.";
        }
    }

    // Insert into the database
    $sql = "INSERT INTO articles (title, description, image_url, link, source, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $title, $description, $image_path, $link, $source, $status);

    if ($stmt->execute()) {
        $message = "Article added successfully!";
    } else {
        $message = "Error: " . $stmt->error;
    }

    $stmt->close();

    // Redirect to avoid form resubmission
    header("Location: manage_articles.php");
    exit();
}

// Fetch all articles from the database
$sql = "SELECT id, title, description, image_url, link, source, status, created_at FROM articles ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Articles</title>
    <style>
        /* General Page Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1300px;
            margin: 40px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
            font-size: 26px;
        }

        /* Button Styles */
        .btn {
            padding: 10px 20px;
            color: white;
            background-color: #007bff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-right: 10px;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .btn-secondary {
            background-color: #6c757d;
        }

        .btn-secondary:hover {
            background-color: #565e64;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #fff;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f8f8;
            color: #333;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .table-description {
            max-width: 350px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Toggle Switch Styles */
        .switch {
            position: relative;
            display: inline-block;
            width: 34px;
            height: 20px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 20px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 14px;
            width: 14px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #007bff;
        }

        input:checked + .slider:before {
            transform: translateX(14px);
        }

        /* Pop-up Form Styles */
        #addArticleForm {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            max-width: 500px;
            width: 90%;
        }

        #overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        form input, form textarea, form select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        form button {
            width: 100%;
        }
        /* Button Styles */
.btn {
    padding: 10px 20px;
    color: white;
    background-color: #007bff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
    text-decoration: none;
    display: inline-block;
    font-size: 14px;
}

.btn:hover {
    background-color: #0056b3;
}

/* Specific Styles for the "View Article" Button */
.btn-view {
    background-color: #28a745; /* Green Color */
}

.btn-view:hover {
    background-color: #218838; /* Darker Green on Hover */
}

    </style>
</head>
<body>

<!-- Main Container -->
<div class="container">
    <h2>Manage Articles</h2>

    <!-- Add Article Button -->
    <div class="text-center mb-4">
        <button onclick="openAddArticleForm()" class="btn">Add Article</button>
    </div>

    <!-- Article List Table -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th class="table-description">Description</th>
                <th>Image</th>
                <th>Source</th>
                <th>Date Created</th>
                <th>Toggle Status</th>
                <th>View Article</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $viewLink = (!empty($row['link'])) ? $row['link'] : '#';
                    $checked = ($row["status"] === "active") ? "checked" : "";

                    echo '
                    <tr>
                        <td>' . htmlspecialchars($row["id"]) . '</td>
                        <td>' . htmlspecialchars($row["title"]) . '</td>
                        <td class="table-description">' . htmlspecialchars($row["description"]) . '</td>
                        <td>
                            <img src="' . htmlspecialchars($row["image_url"]) . '" alt="Article Image" style="width: 50px; height: auto;">
                        </td>
                        <td>' . htmlspecialchars($row["source"]) . '</td>
                        <td>' . htmlspecialchars(date('F j, Y', strtotime($row["created_at"]))) . '</td>
                        <td>
                            <label class="switch">
                                <input type="checkbox" ' . $checked . ' onclick="toggleStatus(' . $row["id"] . ')">
                                <span class="slider"></span>
                            </label>
                        </td>
                        <td><a href="' . $viewLink . '" target="_blank" class="btn btn-view">
    <i class="fas fa-external-link-alt"></i> View Article
</a>
</td>
                    </tr>';
                }
            } else {
                echo '<tr><td colspan="8" class="text-center">No articles found.</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<!-- JavaScript -->
<script>
    function toggleStatus(articleId) {
        const xhr = new XMLHttpRequest();
        xhr.open("GET", "manage_articles.php?toggle_id=" + articleId, true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                alert("Status toggled successfully!");
                location.reload();
            } else {
                alert("Error toggling status.");
            }
        };
        xhr.send();
    }

    function openAddArticleForm() {
        const formHtml = `
            <div id="addArticleForm">
                <h3>Add New Article</h3>
                <form method="POST" action="manage_articles.php" enctype="multipart/form-data">
                    <label for="title">Title:</label>
                    <input type="text" name="title" id="title" required>

                    <label for="description">Description:</label>
                    <textarea name="description" id="description" required></textarea>

                    <label for="image_url">Image:</label>
                    <input type="file" name="image_url" id="image_url" accept="image/*">

                    <label for="link">Link (Optional):</label>
                    <input type="url" name="link" id="link">

                    <label for="source">Source:</label>
                    <input type="text" name="source" id="source" required>

                    <label for="status">Status:</label>
                    <select name="status" id="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>

                    <button type="submit" class="btn">Submit</button>
                    <button type="button" onclick="closeAddArticleForm()" class="btn btn-secondary">Cancel</button>
                </form>
            </div>
            <div id="overlay" onclick="closeAddArticleForm()"></div>
        `;
        document.body.insertAdjacentHTML('beforeend', formHtml);
    }

    function closeAddArticleForm() {
        const form = document.getElementById('addArticleForm');
        const overlay = document.getElementById('overlay');
        if (form) form.remove();
        if (overlay) overlay.remove();
    }
</script>

</body>
</html>
