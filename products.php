<?php
// Include database connection file
include('config.php');
include('admin.php');

// Handle updating the product availability
if (isset($_POST['toggle_product_id']) && isset($_POST['new_availability'])) {
    $productId = $_POST['toggle_product_id'];
    $newAvailability = $_POST['new_availability'] === 'Available' ? 'Available' : 'Unavailable';

    $query = "UPDATE products SET availability='$newAvailability' WHERE productid='$productId'";
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'new_availability' => $newAvailability]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update product availability.']);
    }
    exit();
}

// Handle adding a new product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['productid'])) {
    $productname = $_POST['productname'];
    $price = $_POST['price'];

    // Handle image upload for new product
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $imageName = basename($_FILES['image']['name']);
        $targetDir = "img/";
        $targetFilePath = $targetDir . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
            $query = "INSERT INTO products (productname, price, quantity, image, availability, date_added, date_updated) 
                      VALUES ('$productname', '$price', 0, '$imageName', 'Available', CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP())";
            if (mysqli_query($conn, $query)) {
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                exit();
            } else {
                echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to add product.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        });
                      </script>";
            }
        } else {
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to upload image.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
                  </script>";
        }
    } else {
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Please upload a valid image.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
              </script>";
    }
}

// Handle editing a product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['productid'])) {
    $productId = $_POST['productid'];
    $productname = $_POST['productname'];
    $price = $_POST['price'];

    // Handle image upload for edit
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $imageName = basename($_FILES['image']['name']);
        $targetDir = "img/";
        $targetFilePath = $targetDir . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
            $query = "UPDATE products SET productname='$productname', price='$price', image='$imageName', date_updated=CURRENT_TIMESTAMP() WHERE productid='$productId'";
        }
    } else {
        $query = "UPDATE products SET productname='$productname', price='$price', date_updated=CURRENT_TIMESTAMP() WHERE productid='$productId'";
    }

    if (mysqli_query($conn, $query)) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?success_edit=1");
        exit();
    } else {
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to update product.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
              </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product List</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/addproduct.css">
    <style>
        /* General Styles */
body {
    font-family: 'Poppins', sans-serif;
    background-color: #f4f4f9;
    color: #333;
}

/* Container */
.container {
    width: 80%;
    margin: 40px auto;
    padding: 30px;
    background: #fff;
    box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.05);
    border-radius: 12px;
}

/* Header */
.header h2 {
    font-size: 24px;
    font-weight: 600;
    color: #333;
}

/* Search and Sort Section */
.search-sort-container {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
}

/* Search Bar */
.search-bar {
    flex: 1;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 14px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

/* Button Styles */
.add-product-btn {
    padding: 12px 20px;
    background-color: #007bff;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
    margin-bottom: 10px;
    margin-top: 10px;
}

/* Button Hover Effect */
.add-product-btn:hover {
    background-color: #0056b3;
}

/* Product Display Table */
.product-display-table {
    width: 100%;
    border-collapse: collapse;
}

.product-display-table th, 
.product-display-table td {
    padding: 15px;
    border-bottom: 1px solid #eaeaea;
    font-size: 15px;
    text-align: left;
}

.product-display-table th {
    font-weight: 600;
    color: #007bff;
}

.product-display-table td {
    color: #555;
}

/* Product Image */
.product-display-table img {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    object-fit: cover;
}

/* Edit Button */
.edit-product-btn {
    padding: 6px 12px;
    background-color: #28a745;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
}

.edit-product-btn:hover {
    background-color: #218838;
}

/* Switch */
.switch {
    position: relative;
    display: inline-block;
    width: 34px;
    height: 20px;
}

.switch input {
    display: none;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
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
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #28a745;
}

input:checked + .slider:before {
    transform: translateX(14px);
}

/* Drag-and-drop image box */
#drop-area {
    width: 90%;
    max-width: 400px;
    height: 150px;
    background-color: #f4f4f9;
    border: 2px dashed #007bff;
    border-radius: 12px;
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
    margin-bottom: 15px;
    overflow: hidden;
}

#drop-area p {
    color: #555;
    font-size: 14px;
}

#drop-area img {
    display: none;
    max-width: 100%;
    height: auto;
    object-fit: cover;
    border-radius: 8px;
}

/* Inputs */
input[type="text"], input[type="number"] {
    width: 90%;
    max-width: 400px;
    margin-bottom: 10px;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #ddd;
    background-color: #f4f4f9;
    font-size: 15px;
    color: #555;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

/* Custom Button Styles */
.custom-btn {
    padding: 10px 20px;
    border-radius: 5px;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.3s;
}

/* Custom Save Button */
.custom-btn#customSaveBtn {
    background-color: #007bff;
    color: #fff;
}

/* Custom Cancel Button */
.custom-btn#customCancelBtn {
    background-color: #f44336;
    color: #fff;
}

/* Custom Button Hover Effects */
.custom-btn#customSaveBtn:hover {
    background-color: #0056b3;
}

.custom-btn#customCancelBtn:hover {
    background-color: #d32f2f;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        width: 95%;
        margin: 20px auto;
        padding: 20px;
    }

    .add-product-btn, .search-bar {
        font-size: 14px;
        padding: 8px 16px;
    }

    .product-display-table th, 
    .product-display-table td {
        font-size: 13px;
        padding: 10px;
    }

    input[type="text"], input[type="number"] {
        width: 100%;
    }
}

    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Product List</h2>
        </div>
        <button class="add-product-btn" onclick="showAddProductForm()">Add Product</button>
        
        <!-- Real-time search bar -->
        <div class="search-sort-container">
        <!-- Real-time search bar -->
        <input type="text" id="searchInput" class="search-bar" placeholder="Search for products">
    </div>

        <table class="product-display-table" id="productTable">
            <thead>
                <tr>
                   <th>Image</th>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Quantity /kilo</th>                 
                    <th>Date Added</th>
                    <th>Date Updated</th>
                    <th>Availability</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT * FROM products";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $checked = $row['availability'] === 'Available' ? 'checked' : '';
                        echo "<tr>
                                <td><img src='img/{$row['image']}' alt='Product Image'></td>
                                <td>{$row['productname']}</td>
                                <td>{$row['price']}</td>
                                <td>{$row['quantity']}</td>
                                <td>{$row['date_added']}</td>
                                <td>{$row['date_updated']}</td>
                                <td>
                                    <label class='switch'>
                                        <input type='checkbox' class='availability-toggle' data-id='{$row['productid']}' $checked>
                                        <span class='slider'></span>
                                    </label>
                                </td>
                                <td>
                                    <button class='edit-product-btn' data-id='{$row['productid']}' 
                                            data-name='{$row['productname']}' data-price='{$row['price']}'
                                            data-quantity='{$row['quantity']}' data-image='{$row['image']}'>Edit</button>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='9'>No products found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
        // Real-time search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#productTable tbody tr');

            rows.forEach(row => {
                const productName = row.cells[1].textContent.toLowerCase();
                if (productName.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Function to handle adding a product
        function showAddProductForm() {
            Swal.fire({
                title: '<h3 style="font-family: Poppins, sans-serif; font-weight: 600; margin-bottom: 15px;">Add New Product</h3>',
                html: `
                    <form id="addProductForm" method="post" enctype="multipart/form-data" style="display: flex; flex-direction: column; align-items: center;">
                        <input type="text" name="productname" placeholder="Product Name" required class="swal2-input" style="margin-bottom: 10px; width: 90%; max-width: 400px;">
                        <input type="number" step="0.01" name="price" placeholder="Price" required class="swal2-input" style="margin-bottom: 10px; width: 90%; max-width: 400px;">
                        <input type="file" name="image" accept="image/*" required style="display: none;" id="fileInput">
                        <div id="drop-area" style="width: 90%; max-width: 400px; height: 150px; background-color: #f4f4f9; border: 2px dashed #007bff; border-radius: 12px; display: flex; justify-content: center; align-items: center; text-align: center; margin-bottom: 15px; overflow: hidden;">
                            <p style="color: #555; font-size: 14px; margin: 0;">Drag and drop an image here or click to upload</p>
                            <img id="preview" src="img/clean_placeholder_image.png" style="display: none; max-width: 100%; height: auto; object-fit: cover; border-radius: 8px;">
                        </div>
                        <div style="display: flex; gap: 15px; margin-top: 10px;">
                            <button type="button" id="customSaveBtn" class="custom-btn" style="padding: 10px 20px; background-color: #007bff; color: #fff; border: none; border-radius: 5px; cursor: pointer;">Save</button>
                            <button type="button" id="customCancelBtn" class="custom-btn" style="padding: 10px 20px; background-color: #f44336; color: #fff; border: none; border-radius: 5px; cursor: pointer;">Cancel</button>
                        </div>
                    </form>
                `,
                showConfirmButton: false,
                showCancelButton: false,
            });

            document.getElementById('customSaveBtn').addEventListener('click', () => {
                const form = document.getElementById('addProductForm');
                const inputs = form.querySelectorAll('input');
                let isValid = true;

                inputs.forEach(input => {
                    if (!input.value) {
                        Swal.showValidationMessage(`Please fill out all fields, including ${input.placeholder.toLowerCase()}.`);
                        isValid = false;
                    }
                });

                if (isValid) form.submit();
            });

            document.getElementById('customCancelBtn').addEventListener('click', () => {
                Swal.close();
            });

            document.querySelector('.swal2-actions').style.gap = '15px';

            const dropArea = document.getElementById('drop-area');
            const fileInput = document.getElementById('fileInput');
            const preview = document.getElementById('preview');
            const placeholderText = dropArea.querySelector('p');

            dropArea.addEventListener('click', () => fileInput.click());

            fileInput.addEventListener('change', () => {
                const file = fileInput.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                        if (placeholderText) {
                            placeholderText.style.display = 'none';
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });

            dropArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropArea.style.borderColor = '#0056b3';
            });

            dropArea.addEventListener('dragleave', () => {
                dropArea.style.borderColor = '#007bff';
            });

            dropArea.addEventListener('drop', (e) => {
                e.preventDefault();
                const file = e.dataTransfer.files[0];
                if (file) {
                    fileInput.files = e.dataTransfer.files;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                        if (placeholderText) {
                            placeholderText.style.display = 'none';
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Handle toggling availability
        document.querySelectorAll('.availability-toggle').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const productId = this.getAttribute('data-id');
                const newAvailability = this.checked ? 'Available' : 'Unavailable';

                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `toggle_product_id=${productId}&new_availability=${newAvailability}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: `Product availability updated to ${data.new_availability}.`,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: data.error || 'Failed to update product availability.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });
        });

        // Handle editing a product
        document.querySelectorAll('.edit-product-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                const productName = this.getAttribute('data-name');
                const productPrice = this.getAttribute('data-price');
                const productQuantity = this.getAttribute('data-quantity');
                const productImage = this.getAttribute('data-image');

                Swal.fire({
                    title: '<h3 style="font-family: Poppins, sans-serif; font-weight: 600; margin-bottom: 15px;">Edit Product</h3>',
                    html: `
                        <form id="editProductForm" method="post" enctype="multipart/form-data" style="display: flex; flex-direction: column; align-items: center;">
                            <input type="hidden" name="productid" value="${productId}">
                            <input type="text" name="productname" placeholder="Product Name" value="${productName}" required class="swal2-input" style="margin-bottom: 10px; width: 90%; max-width: 400px;">
                            <input type="number" step="0.01" name="price" placeholder="Price" value="${productPrice}" required class="swal2-input" style="margin-bottom: 10px; width: 90%; max-width: 400px;">
                            <div id="edit-drop-area" style="width: 90%; max-width: 400px; height: 150px; background-color: #f4f4f9; border: 2px dashed #007bff; border-radius: 12px; display: flex; justify-content: center; align-items: center; text-align: center; margin-bottom: 15px; overflow: hidden;">
                                <input type="file" name="image" accept="image/*" style="display: none;" id="editFileInput">
                                <img id="editPreview" src="img/${productImage}" style="display: block; max-width: 100%; height: auto; object-fit: cover; border-radius: 8px;">
                            </div>
                            <div style="display: flex; gap: 15px; margin-top: 10px;">
                                <button type="button" id="customEditSaveBtn" class="custom-btn" style="padding: 10px 20px; background-color: #007bff; color: #fff; border: none; border-radius: 5px; cursor: pointer;">Save Changes</button>
                                <button type="button" id="customEditCancelBtn" class="custom-btn" style="padding: 10px 20px; background-color: #f44336; color: #fff; border: none; border-radius: 5px; cursor: pointer;">Cancel</button>
                            </div>
                        </form>
                    `,
                    showConfirmButton: false,
                    showCancelButton: false,
                });

                document.getElementById('customEditSaveBtn').addEventListener('click', () => {
                    document.getElementById('editProductForm').submit();
                });

                document.getElementById('customEditCancelBtn').addEventListener('click', () => {
                    Swal.close();
                });

                // Drag-and-drop functionality for the edit form
                const editDropArea = document.getElementById('edit-drop-area');
                const editFileInput = document.getElementById('editFileInput');
                const editPreview = document.getElementById('editPreview');

                editDropArea.addEventListener('click', () => editFileInput.click());

                editFileInput.addEventListener('change', () => {
                    const file = editFileInput.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            editPreview.src = e.target.result;
                            editPreview.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    }
                });

                editDropArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    editDropArea.style.borderColor = '#0056b3';
                });

                editDropArea.addEventListener('dragleave', () => {
                    editDropArea.style.borderColor = '#007bff';
                });

                editDropArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    const file = e.dataTransfer.files[0];
                    if (file) {
                        editFileInput.files = e.dataTransfer.files;
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            editPreview.src = e.target.result;
                            editPreview.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    }
                });
            });
        });

        // Success message handling after product addition or update
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success') === '1') {
                Swal.fire({
                    title: 'Success!',
                    text: 'Product added successfully!',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.history.replaceState(null, null, window.location.pathname);
                });
            }
            if (urlParams.get('success_edit') === '1') {
                Swal.fire({
                    title: 'Success!',
                    text: 'Product updated successfully!',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.history.replaceState(null, null, window.location.pathname);
                });
            }
        });
    </script>
</body>
</html>

<?php
// Close the database connection
mysqli_close($conn);
?>
