<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Signip</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link href="https://fonts.cdnfonts.com/css/avenir" rel="stylesheet">
    <link rel="stylesheet" href="resources/css/forms.css">
</head>
<body>
    <div class="card-header">
        <div class="logo-section d-flex align-items-center">
            <img src="resources/images/login_page_logo.png" alt="RVR Logo" class="logo-img ms-3">
            <div class="company-info ms-2">
                <div class="company-name">RVR SMES</div>
                <div class="company-subtitle">
                    Financial Management System
                </div>
            </div>
            <!-- DateTime pushed to the far right -->
            <p id="datetime" class="ms-auto me-3 mt-4"></p>
        </div>
    </div>
    <!-- Aligns the Sign up form at the center -->
     <div class="main-container">
        <div class="reset-card">
            <div class="card-body">
                <div class="form-title">
                    <i class="fas fa-key"></i>
                    Reset Password
                </div>
                <p class="form-subtitle">
                    Enter your email address and we'll a secure link to reset your password.
                </p>
               <?php
                    if(isset($_SESSION['status']))
                    {
                        echo $_SESSION['status'];
                        unset($_SESSION['status']);
                    }
                ?>
               <form action="forms_logic/reset_link.php" method="POST">
                    <div class="form-floating">
                        <input type="email" name="email" class="form-control" id="floatingEmail" placeholder="name@example.com" required>
                        <label for="floatingEmail">Email Address
                        </label>
                    </div>
                    
                    <button type="submit" name="resetlink_btn"  class="btn btn-reset w-100">
                        <i class="fas fa-paper-plane me-2"></i>
                        Send Reset Link
                    </button>
                </form>

                <div class="back-link">
                    <a href="login_form.php">
                        <i class="fas fa-arrow-left"></i>
                        Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>




    <!-- <div class="row justify-content-center d-flex align-items-center mt-5"> 
        <div class=" form col-md-6 col-lg-4"> 
                <?php
                    if(isset($_SESSION['status']))
                    {
                        echo $_SESSION['status'];
                        unset($_SESSION['status']);
                    }
                ?>
            <div class="form-name mb-3">Reset Password</div>
            <form action="forms_logic/reset_link.php" method="POST">
                <div class="form-floating mb-2">
                    <input type="email" name="email" class="form-control" id="floatingEmail" placeholder="name@example.com">
                    <label for="floatingEmail">Email Address</label>
                </div>
                <div class="form-floating mb-3">
                    <button type="submit" name="resetlink_btn" class="btn btn-dark w-100 mt-3">Send Reset Password Link</button>
                    <div class="mt-3 text-center">
                    </div>
                </div>
            </form>
    </div> -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
         // Date and Time Function
                function updateDateTime() {
                    const now = new Date();
                    const options = {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    };

                    document.getElementById('datetime').innerHTML = now.toLocaleDateString('en-US', options);
                }

                // Update every second
                setInterval(updateDateTime, 1000);

                // Initial call to display time immediately
                updateDateTime();
    </script>
</body>
</html>