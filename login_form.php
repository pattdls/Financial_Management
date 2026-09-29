<?php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// If user is already authenticated, redirect to dashboard
if (isset($_SESSION['auth_user'])) {
    header("Location: /Financial_Management/index.php");
    exit();
}

// Get email value from multiple sources (priority order: session, cookie, empty)
$email_value = '';
$remember_checked = '';

// First check if there's form data from a failed login attempt
if (isset($_SESSION['form_email'])) {
    $email_value = $_SESSION['form_email'];
    $remember_checked = isset($_SESSION['form_remember']) && $_SESSION['form_remember'] ? 'checked' : '';
    
    // Clear form session data after retrieving it
    unset($_SESSION['form_email']);
    unset($_SESSION['form_remember']);
}
// If no session form data, check for "Remember Me" cookie
elseif (isset($_COOKIE['cookie_email']) && isset($_COOKIE['cookie_rem'])) {
    $email_value = $_COOKIE['cookie_email'];
    $remember_checked = 'checked';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link href="https://fonts.cdnfonts.com/css/avenir" rel="stylesheet">
    <link rel="icon" type="image/png"  href="resources/images/login_page_logo.png">
    <link rel="stylesheet" href="resources/css/forms.css">
</head>

<body>
     <div class="full-width-container">
         <!-- Left Side-->
        <div class="info-side mt-0" id="infoSide">
            <div class="info-content mt-0 ">
                <div class="logo">
                    <img src="resources/images/login_page_logo.png"alt="RVR Logo">
                    <h4 class="main-title fw-bold mt-0">RVR Squared Mechanical Engineering Services</h4>
                    <h5 class="subtitle">Financial Management System</h5>
                </div>
                
                <div class="about-content mt-0" id="aboutContent">
                    <p class="about-description">
                        Designed for mechanical engineering service firms, the RVR SMES Financial Management System simplifies financial management by tracking expenses, monitoring costs, managing clients and projects, and generating reports such as the Balance Sheet and Income Statement.
                    </p>
                    
                    <ul class="feature-list">
                        <li class="feature-item">
                            <span class="feature-icon"><i class="bi bi-building-fill-gear"></i></span>
                            <span>Project-Based Financial Tracking</span>
                        </li>
                        <li class="feature-item">
                            <span class="feature-icon"><i class="bi bi-clipboard-data-fill"></i></span>
                            <span>Budget vs. Actual Monitoring</span>
                        </li>
                        <li class="feature-item">
                            <span class="feature-icon"><i class="bi bi-person-fill-lock"></i></span>
                            <span>Role-Based Access</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <button class="about-btn" id="aboutBtn" onclick="toggleAbout()">
                About Us
            </button>
        </div>
         <!-- Right Side -->
        
            <div class="form-side">
                <div class="form col-md-12 col-lg-8">
                    <?php
                    if (isset($_SESSION['status'])) {
                        echo $_SESSION['status'];
                        unset($_SESSION['status']);
                    }
                    ?>
                    <div class="form-name mb-3">Log in</div>
                    <form action="forms_logic/login_logic.php" method="POST">

                        <div class="form-floating mb-3 position-relative">
                            <input type="email" name="email" class="form-control" id="floatingEmail"
                                placeholder="Email"
                                value="<?php echo htmlspecialchars($email_value); ?>">
                            <label for="floatingEmail">Email</label>
                        </div>

                        <div class="form-floating mb-3 position-relative">
                            <input type="password" name="password" class="form-control" id="floatingPassword"
                                placeholder="Password"
                                value="">
                            <label for="floatingPassword">Password</label>

                            <!-- Show/Hide Password -->
                            <button type="button" class="btn position-absolute end-0 top-50 translate-middle-y me-2"
                                style="z-index: 2;" onclick="togglePassword('floatingPassword', 'toggleIcon1')">
                                <i id="toggleIcon1" class="bi bi-eye"></i>
                            </button>
                        </div>
                        <!-- Remember Me -->
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="remember" id="rememberMe" <?php echo $remember_checked; ?>>
                            <label class="form-check-label" for="rememberMe">Remember Me</label>
                        </div>

                        <div class="forgot_pass">
                            <a href="to_fpass.php" class="fpass"> Forgot password? </a>
                        </div>
                        <div class="form-floating mb-3">
                            <button type="submit" name="login_btn" class="login_btn w-100 mt-3">Login</button>
                            <div class="mt-3 text-center">
                            </div>
                        </div>
                    </form>
                </div>
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

        // Add loading animation to login button on form submission
        document.getElementById('loginForm').addEventListener('submit', function() {
            const loginBtn = document.querySelector('.login_btn');
            loginBtn.classList.add('loading');
            loginBtn.innerHTML = 'Logging in...';
        });

        // Add interactive focus effects
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.transition = 'transform 0.3s ease';
            });

            input.addEventListener('blur', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        function toggleAbout() {
            const infoSide = document.getElementById('infoSide');
            const aboutContent = document.getElementById('aboutContent');
            const aboutBtn = document.getElementById('aboutBtn');

            const isShown = infoSide.classList.toggle('about-shown'); 
            aboutContent.classList.toggle('show', isShown);

            if (isShown) {
                aboutBtn.innerHTML = '<i class="bi bi-x-circle"></i>';
                aboutBtn.classList.add('active');
            } else {
                aboutBtn.innerHTML = 'About Us';
                aboutBtn.classList.remove('active');
            }
        }
        </script>

</body>

</html>