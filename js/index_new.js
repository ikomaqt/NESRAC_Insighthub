const signUpButton = document.getElementById('signUp');
const signInButton = document.getElementById('signIn');
const container = document.getElementById('container');

// Add right-panel-active when Sign Up is clicked
signUpButton.addEventListener('click', () => {
    container.classList.add('right-panel-active');
});

// Remove right-panel-active when Sign In is clicked
signInButton.addEventListener('click', () => {
    container.classList.remove('right-panel-active');
});

document.getElementById('signupForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent form submission

    // Get password and confirm password values
    const password = document.getElementById('password_signup').value;
    const confirmPassword = document.getElementById('confirm_password_signup').value;

    // Define the password pattern: 8-16 characters, includes at least one uppercase, one lowercase, and one number
    const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,16}$/;

    // Check if the password matches the pattern
    if (!passwordPattern.test(password)) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Password',
            text: 'Password must be 8-16 characters long, contain at least one uppercase letter, one lowercase letter, and one number.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#d33'
        });
        return; // Stop the form submission and prevent further processing
    }

    // Validate if passwords match
    if (password !== confirmPassword) {
        Swal.fire({
            icon: 'error',
            title: 'Password Mismatch',
            text: 'Password and Confirm Password do not match. Please try again.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#d33'
        });
        return;
    }

    // Get the file input field value
    const fileInput = document.getElementById('file-upload');

    // Check if a file is uploaded
    if (fileInput.files.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Image Uploaded',
            text: 'Please upload a valid ID image before submitting.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#d33'
        });
        return;
    }

    Swal.fire({
        title: 'Verifying...',
        text: 'Please wait while we verify your email.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Capture form data after validations
    const formData = new FormData(this);

    fetch('verify_email.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();

        if (data.status === 'error') {
            Swal.fire({
                icon: 'warning',
                title: 'Email Issue',
                text: data.message,
                confirmButtonText: 'Try Again',
                confirmButtonColor: '#d33'
            });
        } else if (data.status === 'success') {
            window.location.href = 'verify_code.php';
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'A network error occurred while processing your request. Please try again.',
            confirmButtonText: 'Retry',
            confirmButtonColor: '#d33'
        });
    });
});

const formSteps = document.querySelectorAll('.form-step');
let currentStep = 0;

function updateStepIndicator(step) {
    const stepIndicators = document.querySelectorAll('.step-indicator-item');
    const activeLine = document.querySelector('.active-line');

    stepIndicators.forEach((indicator, index) => {
        if (index <= step) {
            indicator.classList.add('active');
            indicator.classList.remove('inactive');
        } else {
            indicator.classList.remove('active');
            indicator.classList.add('inactive');
        }
    });

    const totalSteps = stepIndicators.length - 1;
    const progress = step === 0 ? 0 : (step / totalSteps) * 63;
    activeLine.style.width = `${progress}%`;
}

function validateCurrentStep() {
    const inputs = formSteps[currentStep].querySelectorAll('input, select');
    let isValid = true;

    for (let i = 0; i < inputs.length; i++) {
        if (!inputs[i].checkValidity()) {
            inputs[i].reportValidity();
            isValid = false;
            break;
        }
    }

    return isValid;
}

function nextStep() {
    if (validateCurrentStep()) {
        formSteps[currentStep].classList.remove('active');
        currentStep++;
        formSteps[currentStep].classList.add('active');
        updateStepIndicator(currentStep);
    }
}

function prevStep() {
    formSteps[currentStep].classList.remove('active');
    currentStep--;
    formSteps[currentStep].classList.add('active');
    updateStepIndicator(currentStep);
}

function showFilePreview() {
    const fileUpload = document.getElementById('file-upload');
    const uploadLabel = document.querySelector('.upload-label');
    const file = fileUpload.files[0];

    if (file) {
        uploadLabel.innerHTML = '';
        const fileReader = new FileReader();
        fileReader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'cover';
            uploadLabel.appendChild(img);
        };
        fileReader.readAsDataURL(file);
    }
}

document.getElementById("loginForm").addEventListener("submit", async (e) => {
    e.preventDefault(); // Prevent the default form submission

    const formData = new FormData(e.target);

    try {
        const response = await fetch("login.php", {
            method: "POST",
            body: formData,
        });

        const result = await response.json();

        if (result.status === "success") {
            Swal.fire({
                title: "Verifying...",
                text: "Please wait while we verify your credentials.",
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading(); // Show loading spinner
                },
                timer: 4000, // Display for 4 seconds
                timerProgressBar: true, // Show a progress bar
            }).then(() => {
                // Redirect to homepage after loading
                window.location.href = "homepage.php";
            });
        } else if (result.status === "pending") {
            Swal.fire({
                icon: "warning",
                title: "Account Pending",
                text: result.message,
            });
        } else {
            Swal.fire({
                icon: "warning",
                title: "Invalid Email or Password",
                text: result.message,
            });
        }
    } catch (error) {
        console.error("Error:", error);
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "An unexpected error occurred. Please try again.",
        });
    }
});



document.getElementById('signUp').addEventListener('click', function() {
    document.querySelector('.sign-in-container').style.display = 'none';
    document.querySelector('.sign-up-container').style.display = 'block';
});

document.getElementById('signIn').addEventListener('click', function() {
    document.querySelector('.sign-up-container').style.display = 'none';
    document.querySelector('.sign-in-container').style.display = 'block';
});

document.getElementById('showPasswords').addEventListener('change', function() {
    var passwordField = document.getElementById('password_signup');
    var confirmPasswordField = document.getElementById('confirm_password_signup');

    if (this.checked) {
        passwordField.type = 'text';
        confirmPasswordField.type = 'text';
    } else {
        passwordField.type = 'password';
        confirmPasswordField.type = 'password';
    }
});
function togglePasswordVisibility() {
    const passwordInput = document.getElementById("myInput");
    const showPasswordCheckbox = document.querySelector("#loginForm .show-password input");

    if (showPasswordCheckbox.checked) {
        passwordInput.type = "text"; // Show password
    } else {
        passwordInput.type = "password"; // Hide password
    }
}

// Attach the event listener to the checkbox
document.querySelector("#loginForm .show-password input").addEventListener("change", togglePasswordVisibility);