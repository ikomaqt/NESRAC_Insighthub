<?php
// Include database connection
session_start();
include('config.php');
include('navbar.php');

// Start session to get the logged-in user's id
$userid = $_SESSION['userid']; // Assuming userid is stored in session after login

// Initialize variables to hold the form data
$lastName = $firstName = $middleName = $bday = $gender = '';
$municipality = $barangay = $street = $profile_photo = '';
$role = 'Non-Member'; // Default role is 'Non-Member'

// Fetch existing user data including membership status and profile photo
$query = "SELECT 
            u.lastName, u.firstName, u.middleName, u.bday, u.gender, 
            ua.emailAdd, 
            u.municipality, u.barangay, u.street,
            IF(um.userid IS NOT NULL, 'Member', 'Non-Member') AS role,
            ua_assets.profile_photo
          FROM USER u
          JOIN USER_Account ua ON u.userID = ua.userid
          LEFT JOIN USER_Membership um ON u.userID = um.userid
          LEFT JOIN USER_Assets ua_assets ON u.userID = ua_assets.userid
          WHERE u.userID = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $userid);
$stmt->execute();
$stmt->bind_result($lastName, $firstName, $middleName, $bday, $gender, $emailAdd, $municipality, $barangay, $street, $role, $profile_photo);
$stmt->fetch();
$stmt->close();

// Handle form submission for updating profile information
$successFlag = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['lastName'])) {
        // Update profile details
        $lastName = $_POST['lastName'];
        $firstName = $_POST['firstName'];
        $middleName = $_POST['middleName'];
        $bday = $_POST['bday'];
        $gender = $_POST['gender'];
        $municipality = $_POST['municipality'];
        $barangay = $_POST['barangay'];
        $street = $_POST['street'];

        // Update user data in the database
        $update_query = "UPDATE USER 
                         SET lastName = ?, firstName = ?, middleName = ?, bday = ?, gender = ?, 
                             municipality = ?, barangay = ?, street = ?
                         WHERE userID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('ssssssssi', $lastName, $firstName, $middleName, $bday, $gender, $municipality, $barangay, $street, $userid);

        if ($stmt->execute()) {
            $successFlag = true; // Set the success flag to trigger SweetAlert
        }
        $stmt->close();
    }

    // Handle profile photo update separately as shown previously
    if (isset($_FILES['profile_photo'])) {
        if ($_FILES['profile_photo']['error'] == 0) {
            $targetDir = "uploads/";  
            $fileName = basename($_FILES["profile_photo"]["name"]);
            $targetFilePath = $targetDir . $fileName;

            $check = getimagesize($_FILES["profile_photo"]["tmp_name"]);
            if ($check !== false) {
                if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $targetFilePath)) {
                    $update_photo_query = "UPDATE USER_Assets SET profile_photo = ? WHERE userid = ?";
                    $stmt = $conn->prepare($update_photo_query);
                    $stmt->bind_param('si', $targetFilePath, $userid);
                    $stmt->execute();
                    $stmt->close();
                    $profile_photo = $targetFilePath;
                }
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/edit_profile.css">
    <title>Edit Profile</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .profile-photo {
            max-width: 150px;
            max-height: 150px;
            border-radius: 50%;
        }
        .edit-icon {
            font-size: 1.5em;
            color: #007bff;
            margin-top: 10px;
            cursor: pointer;
        }
        .edit-icon:hover {
            color: #0056b3;
        }
        .file-input {
            margin-top: 10px;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .edit-icon-wrapper {
            text-align: center;
        }
        .swal2-image-preview {
            max-width: 150px;
            max-height: 150px;
            border-radius: 50%;
            margin-bottom: 20px;
        }
        .swal2-confirm {
            background-color: #6c5ce7 !important;
            color: #fff;
            border: none;
        }
        .swal2-cancel {
            background-color: #636e72 !important;
            color: #fff;
            border: none;
        }
        .profile-info {
            display: flex;
            justify-content: space-between;
        }
        .profile-form {
            flex: 1;
            margin-left: 20px;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="main-body">
            <div class="profile-info">
                <!-- Left Column (Profile Info) -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body text-center position-relative">
                            <!-- Profile Photo -->
                            <img src="<?= $profile_photo ?>" alt="Profile Photo" class="profile-photo mb-3">
                            <div class="edit-icon-wrapper">
                                <i class="fas fa-edit edit-icon" id="edit-icon"></i>
                            </div>
                            <h4><?= $firstName ?> <?= $lastName ?></h4>
                            <p class="text-secondary mb-1"><?= $role ?></p>
                        </div>
                    </div>
                </div>

                <!-- Right Column (Edit Form) -->
                <div class="profile-form">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <!-- Full Name -->
                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <h6 class="mb-0">Last Name</h6>
                                    </div>
                                    <div class="col-sm-9 text-secondary">
                                        <input type="text" class="form-control" name="lastName" value="<?= $lastName ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <h6 class="mb-0">First Name</h6>
                                    </div>
                                    <div class="col-sm-9 text-secondary">
                                        <input type="text" class="form-control" name="firstName" value="<?= $firstName ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <h6 class="mb-0">Middle Name</h6>
                                    </div>
                                    <div class="col-sm-9 text-secondary">
                                        <input type="text" class="form-control" name="middleName" value="<?= $middleName ?>">
                                    </div>
                                </div>

                                <!-- Birthday -->
                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <h6 class="mb-0">Birthday</h6>
                                    </div>
                                    <div class="col-sm-9 text-secondary">
                                        <input type="date" class="form-control" name="bday" value="<?= $bday ?>" required>
                                    </div>
                                </div>

                                <!-- Gender -->
                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <h6 class="mb-0">Gender</h6>
                                    </div>
                                    <div class="col-sm-9 text-secondary">
                                        <select name="gender" class="form-control" required>
                                            <option value="Male" <?= $gender == 'Male' ? 'selected' : '' ?>>Male</option>
                                            <option value="Female" <?= $gender == 'Female' ? 'selected' : '' ?>>Female</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Municipality -->
                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <h6 class="mb-0">Municipality</h6>
                                    </div>
                                    <div class="col-sm-9 text-secondary">
                                        <select id="municipality" name="municipality" class="form-control" required>
                                            <option value="">Select Municipality</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Barangay -->
                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <h6 class="mb-0">Barangay</h6>
                                    </div>
                                    <div class="col-sm-9 text-secondary">
                                        <select id="barangay" name="barangay" class="form-control" required>
                                            <option value="">Select Barangay</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Street -->
                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <h6 class="mb-0">Street</h6>
                                    </div>
                                    <div class="col-sm-9 text-secondary">
                                        <input type="text" class="form-control" name="street" value="<?= $street ?>" required>
                                    </div>
                                </div>

                                <!-- Save Changes -->
                                <div class="row">
                                    <div class="col-sm-3"></div>
                                    <div class="col-sm-9 text-secondary">
                                        <input type="submit" class="btn btn-primary px-4" value="Save Changes">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div> <!-- End Right Column -->
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
        $(document).ready(function() {
            // When the edit icon is clicked, trigger the SweetAlert file input
            $('#edit-icon').on('click', function() {
                Swal.fire({
                    title: 'Upload New Profile Photo',
                    html: `
                        <div style="display: flex; flex-direction: column; align-items: center;">
                            <img src="<?= $profile_photo ?>" id="preview-photo" class="swal2-image-preview" alt="Current Profile Photo" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin-bottom: 20px;">
                            <input type="file" id="file-input" accept="image/*" class="form-control file-input" style="display: block;">
                        </div>`,
                    showCancelButton: true,
                    confirmButtonText: 'Upload',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#6c5ce7',
                    cancelButtonColor: '#636e72',
                    customClass: {
                        confirmButton: 'btn btn-primary',
                        cancelButton: 'btn btn-secondary',
                    },
                    preConfirm: () => {
                        const file = document.getElementById('file-input').files[0];
                        if (!file) {
                            Swal.showValidationMessage('Please select a file');
                            return;
                        }
                        const formData = new FormData();
                        formData.append('profile_photo', file);

                        return $.ajax({
                            url: 'edit_profile.php',
                            method: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                        });
                    },
                    didOpen: () => {
                        const input = document.getElementById('file-input');
                        input.addEventListener('change', previewFile);  
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Profile Photo Updated',
                            text: 'Your profile photo has been updated.'
                        }).then(() => {
                            location.reload();
                        });
                    }
                });
            });

            function previewFile() {
                const file = document.getElementById('file-input').files[0];
                const preview = document.getElementById('preview-photo');
                const reader = new FileReader();

                reader.onloadend = function() {
                    preview.src = reader.result;  
                };

                if (file) {
                    reader.readAsDataURL(file);  
                } else {
                    preview.src = "<?= $profile_photo ?>";
                }
            }

            // Load municipalities on page load
            $.post('address_container.php', { getMunicipalities: true }, function(data) {
                $('#municipality').html('<option value="">Select Municipality</option>');
                $.each(data, function(index, municipality) {
                    $('#municipality').append('<option value="' + municipality + '">' + municipality + '</option>');
                });
                $('#municipality').val('<?= $municipality ?>').trigger('change');
            }, 'json');

            $('#municipality').on('change', function() {
                var municipality = $(this).val();
                if (municipality) {
                    $.post('address_container.php', { municipality: municipality }, function(data) {
                        $('#barangay').html('<option value="">Select Barangay</option>');
                        $.each(data, function(index, barangay) {
                            $('#barangay').append('<option value="' + barangay + '">' + barangay + '</option>');
                        });
                        $('#barangay').val('<?= $barangay ?>');
                    }, 'json');
                } else {
                    $('#barangay').html('<option value="">Select Barangay</option>');
                }
            });

            $('#municipality').trigger('change');

            // Trigger SweetAlert if form was successfully updated
            <?php if ($successFlag): ?>
            Swal.fire({
                icon: 'success',
                title: 'Profile Updated',
                text: 'Your profile information has been updated successfully.'
            });
            <?php endif; ?>

        });
       const PSGC_API_URL = "https://psgc.gitlab.io/api";

// Fetch and populate cities for Nueva Ecija
async function fetchCities() {
    try {
        const response = await fetch(`${PSGC_API_URL}/provinces/034900000/cities-municipalities/`);
        const cities = await response.json();

        const municipalitySelect = document.getElementById("municipality");
        municipalitySelect.innerHTML = `<option value="" disabled selected>Select your city/municipality</option>`;
        cities.forEach((city) => {
            const option = document.createElement("option");
            option.value = city.code;
            option.textContent = city.name;
            municipalitySelect.appendChild(option);
        });
    } catch (error) {
        console.error("Error fetching cities for Nueva Ecija:", error);
    }
}

// Fetch and populate barangays for selected city/municipality
async function fetchBarangays(cityCode) {
    try {
        const response = await fetch(`${PSGC_API_URL}/cities-municipalities/${cityCode}/barangays/`);
        const barangays = await response.json();

        const barangaySelect = document.getElementById("barangay");
        barangaySelect.innerHTML = `<option value="" disabled selected>Select your barangay</option>`;
        barangays.forEach((barangay) => {
            const option = document.createElement("option");
            option.value = barangay.code;
            option.textContent = barangay.name;
            barangaySelect.appendChild(option);
        });
    } catch (error) {
        console.error("Error fetching barangays:", error);
    }
}

// Event listener for city/municipality change
document.getElementById("municipality").addEventListener("change", (e) => {
    const selectedCityCode = e.target.value;
    fetchBarangays(selectedCityCode);
});

// Initialize cities on page load
document.addEventListener("DOMContentLoaded", fetchCities);

    </script>
</body>
</html>
