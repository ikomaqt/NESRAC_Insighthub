$(document).ready(function() {
    $('#form_input').on('submit', function(event) {
        event.preventDefault();
        
        // Reset previous error message
        $('#email-error').text('');

        // Show loading indicator
        Swal.fire({
            title: 'Signing Up...',
            text: 'Please wait while we process your registration.',
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Perform Ajax request
        $.ajax({
            url: $(this).attr('action'),
            type: $(this).attr('method'),
            data: $(this).serialize(),
            success: function(response) {
                response = JSON.parse(response);
                
                setTimeout(function() {
                    if (response.status === "success") {
                        Swal.close();  
                        window.location.href = "homepage.php";
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message,
                            icon: 'error',
                            confirmButtonText: 'Try Again'
                        });

                        // Display error message next to email input
                        $('#email-error').text(response.message);
                    }
                }, 1500);  
            },
            error: function() {
                setTimeout(function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Sign Up Failed!',
                        icon: 'error',
                        confirmButtonText: 'Try Again'
                    });
                }, 2000);  
            }
        });
    });
});
