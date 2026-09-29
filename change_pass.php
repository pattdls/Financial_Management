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
                    Change Password
                </div>
                <p class="form-subtitle">
                Please enter your registered email along with the new password provided.                
                </p>
                
                <?php
                    if(isset($_SESSION['status']))
                    {
                        echo $_SESSION['status'];
                        unset($_SESSION['status']);
                    }
                ?>

            <form action="forms_logic/reset_link.php" method="POST">
                <input type="hidden" name="password_token" value="<?php if(isset($_GET['token'])){echo $_GET['token'];} ?>">

                <div class="form-floating mb-3">
                    <input type="email" name="email" class="form-control" id="floatingEmail" placeholder="name@example.com" 
                    value="<?php if(isset($_GET['email'])){echo $_GET['email'];} ?>">
                    <label for="floatingEmail">Email</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" name="new_password" class="form-control" id="floatingPassword" placeholder="Password">
                    <label for="floatingPassword">New Password</label>
                    <!-- Hide/Unhide Button functionality -->
                    <button type="button" class="btn position-absolute end-0 top-50 translate-middle-y me-2" onclick="togglePassword('floatingPassword', 'toggleIcon1')">
                        <i id="toggleIcon1" class="bi bi-eye"></i>
                    </button>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" name="new_conpassword" class="form-control" id="floatingConPassword" placeholder="Password">
                    <label for="floatingConPassword">Confirm New Password</label>
                    <button type="button" class="btn position-absolute end-0 top-50 translate-middle-y me-2" onclick="togglePassword('floatingConPassword', 'toggleIcon2')">
                        <i id="toggleIcon2" class="bi bi-eye"></i>
                    </button>
                </div>
                <div class="form-floating mb-3">
                    <button type="submit" name="passupdate_btn" class="btn btn-update w-100 mt-3">Update Password</button>
                    <div class="mt-3 text-center">
                    </div>
                </div>
            </form>
    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        function togglePassword(inputId, iconId) {
            let inputField = document.getElementById(inputId);
            let icon = document.getElementById(iconId);
        
            if (inputField.type === "password") {
                inputField.type = "text";
                icon.classList.replace("bi-eye", "bi-eye-slash");
            } else {
                inputField.type = "password";
                icon.classList.replace("bi-eye-slash", "bi-eye");
            }
        }
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
                updateDateTime()
        </script>
        <?php
            // Unset the session variables after they are used
            unset($_SESSION['name']);
            unset($_SESSION['email']);
        ?>

</body>
</html>