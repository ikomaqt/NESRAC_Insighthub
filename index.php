<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
    integrity="sha512-iBBXm8fW90+nuLcSKlbmrPcLa0OT92xO1BIsZ+ywDWZCvqsWgccV3gFoRBv0z+8dLJgyAHIhR35VZc2oM/gI1w=="
    crossorigin="anonymous" />
    <link rel="stylesheet" href="style/index_new.css">
    <title>Sliding Sign In Form</title>
</head>
<body>
    <div class="container" id="container">
        <div class="form-container sign-up-container">
            <div class="signup-card">
                <!-- Step Indicator inside the card -->
                <div class="step-indicator">
                    <div class="step-indicator-item active">
                        <div class="circle">1</div>
                        <div class="label">Step 1</div>
                    </div>
                    <div class="step-indicator-item inactive">
                        <div class="circle">2</div>
                        <div class="label">Step 2</div>
                    </div>
                    <div class="step-indicator-item inactive">
                        <div class="circle">3</div>
                        <div class="label">Step 3</div>
                    </div>

                    <!-- Inactive line (background) -->
                    <div class="step-indicator-line inactive-line"></div>

                    <!-- Active line (foreground, grows as steps progress) -->
                    <div class="step-indicator-line active-line"></div>
                </div>

                <form action="verify_email.php" method="POST" id="signupForm" enctype="multipart/form-data">
                    <!-- Step 1 -->
                    <div class="form-step active">
                        <div class="type">
                            <label for="lastname">Lastname:</label>
                            <input type="text" name="lastname" placeholder="Lastname" id="lastname" required>
                        </div>
                        <div class="type">
                            <label for="firstname">Firstname:</label>
                            <input type="text" name="firstname" placeholder="Firstname" id="firstname" required>
                        </div>
                        <div class="type">
                            <label for="middlename">Middlename:</label>
                            <input type="text" name="middlename" placeholder="Middlename" id="middlename" required>
                        </div>
                        <div class="type">
                            <label for="bday">Birthday:</label>
                            <input type="date" name="bday" placeholder="Birthday" id="bday" required>
                        </div>
                        <div class="rad">
                            <label for="gender">Gender:</label><br>
                            <select name="gender" id="gender" required>
                                <option value="" disabled selected>Select your gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                        <div class="button-group">
                            <button type="button" onclick="nextStep()">Next</button>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="form-step">
                        <div class="type">
                            <label for="municipality">Municipality:</label>
                            <select name="municipality" id="municipality" required>
                                <option value="" disabled selected>Select your municipality</option>
                            </select>
                        </div>
                        <div class="type">
                            <label for="barangay">Barangay:</label>
                            <select name="barangay" id="barangay" required>
                                <option value="" disabled selected>Select your barangay</option>
                            </select>
                        </div>
                        <div class="type">
                            <label for="address">Purok, Street:</label>
                            <input type="text" name="street" placeholder="Purok, Street" id="street" required>
                        </div>
                        <div class="button-group">
                            <button type="button" class="previous-btn" onclick="prevStep()">Previous</button>
                            <button type="button" onclick="nextStep()">Next</button>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="form-step">
                        <div class="type">
                            <label for="number">Phone Number:</label>
                            <input type="text" name="number" placeholder="Phone Number" id="number" required>
                        </div>
                        <div class="type">
                            <label for="email">Email:</label>
                            <input type="email" name="email" placeholder="Email" id="email_signup" required>
                        </div>
                        <div class="type">
                            <label for="password">Password:</label>
                            <input type="password" name="password" placeholder="Password" id="password_signup" required>
                        </div>
                        <div class="type">
                            <label for="confirm_password">Confirm Password:</label>
                            <input type="password" name="confirm_password" placeholder="Confirm Password" id="confirm_password_signup" required>
                        </div>
                        <div class="show-password-container">
                            <label class="show-password">
                                <input type="checkbox" id="showPasswords"> Show passwords
                            </label>
                        </div>
                        <div class="type">
                            <label for="photo">Upload Valid ID:</label>
                            <div class="upload-container" id="drop-area">
                                <input type="file" name="photo" id="file-upload" hidden onchange="showFilePreview()" required />
                                <label for="file-upload" class="upload-label">
                                    <div class="upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <span class="upload-text">Click to Upload</span>
                                    <span class="upload-text">OR DROP FILES HERE</span>
                                </label>
                                <div id="preview-container" class="preview-container">
                                    <!-- Preview of the uploaded image will appear here -->
                                </div>
                            </div>
                        </div>
                        <div class="button-group">
                            <button type="button" class="previous-btn" onclick="prevStep()">Previous</button>
                            <button type="submit">Sign Up</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="form-container sign-in-container">
            <form action="login.php" method="POST" id="loginForm">
                <h1>Sign in</h1>
                <span>or use your account</span>
                <input type="email" name="email" placeholder="Email" required />
                <div class="password-container">
                    <input type="password" id="myInput" name="password" placeholder="Password" required />
                    <div class="password-options">
                        <label class="show-password">
                            <input type="checkbox" onclick="myFunction()"> Show password
                        </label>
                        <a href="forgot_password.php" id="forgotPasswordBtn">Forgot your password?</a>
                    </div>
                </div>
                <button type="submit">Sign In</button>
                <button type="button" class="ghost" id="signUp">Sign Up</button>
            </form>
        </div>

        <div class="overlay-container">
            <div class="overlay" style="background-image: url('img/login_bg.png'); background-repeat: no-repeat; background-size: cover; background-position: center;">
                <div class="overlay-panel overlay-left">
                    <h1>Welcome Back!</h1>
                    <p>Use your personal info to login</p>
                    <button class="ghost" id="signIn">Sign In</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Welcome!</h1>
                    <p>Enter your details to start up your dashboard!</p>
                    <img src="img/logo.png" alt="Logo" class="logo" />
                </div>
            </div>
        </div>
    </div>

    <script src="js/index_new.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
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