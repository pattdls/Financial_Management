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
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&family=Squada+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../resources/css/forms.css">
</head>
<body>
  <div class="container">
    <!-- Aligns the Sign up form at the center -->
    <div class="row justify-content-center d-flex align-items-center vh-100"> 
        <div class=" form col-md-6 col-lg-4"> 
                <?php
                    if(isset($_SESSION['status']))
                    {
                        echo $_SESSION['status'];
                        unset($_SESSION['status']);
                    }
                ?>

            <div class="form-name mb-3">Sign Up</div>
            <form action="forms_logic/signup_logic.php" method="POST">
                <div class="form-floating mb-3">
                    <input type="text" name="name" class="form-control" id="floatingName" placeholder="Name"
                    value= "<?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : ''; ?>" required> 
                    <!-- to prevent this field from resetting -->
                    <label for="floatingName">Name</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="email" name="email" class="form-control" id="floatingEmail" placeholder="name@example.com"
                    value= "<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?>">
                    <label for="floatingEmail">Email</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" name="password" class="form-control" id="floatingPassword" placeholder="Password">
                    <label for="floatingPassword">Password</label>
                    <!-- Hide/Unhide Button functionality -->
                    <button type="button" class="btn position-absolute end-0 top-50 translate-middle-y me-2" onclick="togglePassword('floatingPassword', 'toggleIcon1')">
                        <i id="toggleIcon1" class="bi bi-eye"></i>
                    </button>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" name="conpassword" class="form-control" id="floatingConPassword" placeholder="Password">
                    <label for="floatingConPassword">Confirm Password</label>
                    <button type="button" class="btn position-absolute end-0 top-50 translate-middle-y me-2" onclick="togglePassword('floatingConPassword', 'toggleIcon2')">
                        <i id="toggleIcon2" class="bi bi-eye"></i>
                    </button>
                </div>
                <div class="form-floating mb-3">
                    <button type="submit" name="signup_btn" class="btn btn-dark w-100 mt-3">Signup</button>
                    <div class="mt-3 text-center">
                    </div>
                </div>
                <div class= "login_instead">
                    <p>Already have an account? <a href= "login_form.php">Log in</a> </p>
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
        </script>
        <?php
            // Unset the session variables after they are used
            unset($_SESSION['name']);
            unset($_SESSION['email']);
        ?>

</body>
</html>