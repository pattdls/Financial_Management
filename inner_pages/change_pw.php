<?php
include('../dbcon.php'); 
include('../layout/session_check.php'); 

// Then prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Allow both admin and finance roles
// if (
//     !isset($_SESSION['authenticated']) ||
//     !in_array($_SESSION['auth_user']['role'], ['admin', 'finance'])
// ) {
//     $_SESSION['status'] = "<div class='alert'>Access Denied.</div>";
//     header("Location: login_form.php");
//     exit(0);
// }

// Database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get user data from session
$user_id = $_SESSION['auth_user']['id'] ?? null;
$name = $_SESSION['auth_user']['name'] ?? 'Guest';
$email = $_SESSION['auth_user']['email'] ?? '';
$role = $_SESSION['auth_user']['role'] ?? '';

// If we have user_id, fetch additional user data from database
$user_data = [
    'first_name' => '',
    'last_name' => '',
    'phone_number' => '',

];

if ($user_id) {
    $userQuery = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id' LIMIT 1");
    if ($userQuery && mysqli_num_rows($userQuery) > 0) {
        $userData = mysqli_fetch_assoc($userQuery);
        $user_data['first_name'] = $userData['name'] ?? '';
        $user_data['phone_number'] = $userData['phone_number'] ?? '';
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Clients"; ?>
    <?php include('../layout/head.php'); ?>
    <link rel="stylesheet" href="../resources/css/main.css">
    <link rel="stylesheet" href="../resources/css/my_profile.css">
</head>


<body class="d-flex">

    <!-- Side Nav Container -->
    <?php include('../layout/sidenav.php'); ?>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Log Out</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to log out?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <a href="/Financial_Management/forms_logic/logout.php" class="btn btn-danger rounded-pill px-3">Log Out</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content container-fluid">

        <!-- Top Nav -->
        <?php
        $page_title = 'My Profile';
        include '../layout/topnav.php';
        ?>

        <div class="container px-4" style="min-height: 90vh;">
            
            <!-- Success Alerts -->
            <div class="">
                <?php if (isset($_SESSION['success']) && $_SESSION['success'] != ''): ?>
                    <div class="message alert-success alert-dismissible fade show text-start" role="alert">
                        <span><?= htmlspecialchars($_SESSION['success']); ?></span>
                        
                    </div>
                    <?php unset($_SESSION['success']); ?>

                <!-- Failed Alerts -->
                <?php endif; ?>
                <?php if (isset($_SESSION['danger']) && $_SESSION['danger'] != ''): ?>
                    <div class="message alert-danger alert-dismissible fade show text-start" role="alert">
                        <span><?= htmlspecialchars($_SESSION['danger']); ?></span>
                       
                    </div>
                    <?php unset($_SESSION['danger']); ?>
                <?php endif; ?>

                <!-- Warning Alerts -->
                <?php if (isset($_SESSION['warning'])): ?>
                    <div class="message alert-warning alert-dismissible fade show text-start" role="alert">
                        <span><?= htmlspecialchars($_SESSION['warning']); ?></span>
                        
                    </div>
                    <?php unset($_SESSION['warning']); ?>
                <?php endif; ?>
            </div>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const alerts = document.querySelectorAll(".message");
                    alerts.forEach(alert => {
                        setTimeout(() => {
                            // Bootstrap fade out effect
                            alert.classList.remove("show");
                            alert.classList.add("fade");
                            setTimeout(() => alert.remove(), 500); // remove from DOM
                        }, 2000); // 5 seconds
                    });
                });
            </script>
            
            <!-- Profile Navigation -->
            <div class="nav profile-nav-tabs mt-4">
                <a class="nav-link" href="my_profile.php">My Profile</a>
                <!--<?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'finance' && $_SESSION['auth_user']['role'] !== 'user'): ?>-->
                <!--    <a class="nav-link" href="company_profile.php">Company Profile</a>-->
                <!--<?php endif; ?>-->
                <a class="nav-link active">Change Password</a>
            </div>

            <!-- Row container -->
            <div class="row ">
                <!-- Profile Picture Column -->

                <!-- Password Details Column -->
                <div class="col-xl-8">
                    <div class="card account-details-card h-100 mt-4">
                        <div class="card-header">Change Password</div>
                        <div class="card-body d-flex flex-column justify-content-between h-100">
                            <form id="changePasswordForm" method="POST" action="../forms_logic/update_pw.php">
                                <!-- Current Password -->
                                <div class="mb-3 position-relative">
                                    <label class="form-label" for="current_password">Current Password <span style="color: red;">*</span></label>
                                    <input class="form-control" id="current_password" name="current_password" type="password" placeholder="Enter current password" required>
                                    <button type="button" class="btn btn-sm position-absolute end-0 top-50 translate-middle-x "
                                        onclick="togglePassword('current_password', 'icon_current')">
                                        <i id="icon_current" class="bi bi-eye"></i>
                                    </button>
                                </div>

                                <!-- New Password -->
                                <div class="mb-3 position-relative">
                                    <label class="form-label" for="new_password">New Password <span style="color: red;">*</span></label>
                                    <input class="form-control" id="new_password" name="new_password" type="password" placeholder="Enter new password" required oninput="checkPasswordStrength()">
                                    <button type="button" class="btn btn-sm position-absolute end-0 top-50 translate-middle-x"
                                        onclick="togglePassword('new_password', 'icon_new')">
                                        <i id="icon_new" class="bi bi-eye"></i>
                                    </button>
                                </div>

                                <!-- Password Requirements Checklist -->
                                <div class=" mb-3 mt-2 " id="passwordChecklist">
                                    <small class="form-text text-muted d-block mb-1">Password Requirements:</small>
                                    <div class="d-flex flex-column gap-1">
                                        <small id="length-check" class="text-muted">
                                            <i class="bi bi-circle me-1"></i> At least 8 characters
                                        </small>
                                        <small id="uppercase-check" class="text-muted">
                                            <i class="bi bi-circle me-1"></i> One uppercase letter
                                        </small>
                                        <small id="number-check" class="text-muted">
                                            <i class="bi bi-circle me-1"></i> One number
                                        </small>
                                        <small id="special-check" class="text-muted">
                                            <i class="bi bi-circle me-1"></i> One special character
                                        </small>
                                    </div>
                                </div>

                                <!-- Confirm Password -->
                                <div class="mb-4 position-relative">
                                    <label class="form-label" for="confirm_password">Confirm Password <span style="color: red;">*</span></label>
                                    <input class="form-control" id="confirm_password" name="confirm_password" type="password" placeholder="Confirm new password" required>
                                    <button type="button" class="btn btn-sm position-absolute end-0 top-50 translate-middle-x "
                                        onclick="togglePassword('confirm_password', 'icon_confirm')">
                                        <i id="icon_confirm" class="bi bi-eye"></i>
                                    </button>
                                </div>

                                <!-- Save Button -->
                                <button class="btn btn-primary" type="submit" name="change_password">Save changes</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>


    <!-- Upload Image Modal -->
    <div class="modal fade" id="uploadImageModal" tabindex="-1" aria-labelledby="uploadImageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadImageModalLabel">Upload Profile Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="uploadImageForm" method="POST" action="forms_logic/upload_profile_image.php" enctype="multipart/form-data">
                    <div class="modal-body text-center">
                        <!-- Current Image Preview -->
                        <div class="mb-3">
                            <img src="resources/images/1x1_unif_bluebg.png" alt="Current Profile" class="profile-picture" id="currentImagePreview">
                        </div>

                        <!-- File Upload -->
                        <div class="mb-3">
                            <label for="profileImageFile" class="form-label">Choose New Image</label>
                            <input type="file" class="form-control" id="profileImageFile" name="profile_image" accept="image/*" required>
                            <small class="form-text text-muted">Supported formats: JPG, PNG, GIF. Max size: 5MB</small>
                        </div>

                        <!-- New Image Preview -->
                        <div class="mb-3" id="newImagePreview" style="display: none;">
                            <label class="form-label">Preview:</label>
                            <div>
                                <img id="imagePreview" class="profile-picture">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload Image</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!--<script src="./resources/js/auto_logout.js"></script>-->

    <script>
        // Update datetime
        function updateDateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            document.getElementById('datetime').textContent = now.toLocaleDateString('en-US', options);
        }

        updateDateTime();
        setInterval(updateDateTime, 60000);


        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("bi-eye");
                icon.classList.add("bi-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("bi-eye-slash");
                icon.classList.add("bi-eye");
            }
        }

        function checkPasswordStrength() {
            const password = document.getElementById('new_password').value;

            // Check length (at least 8 characters)
            const lengthCheck = document.getElementById('length-check');
            if (password.length >= 8) {
                lengthCheck.className = 'text-success';
                lengthCheck.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> At least 8 characters';
            } else {
                lengthCheck.className = 'text-muted';
                lengthCheck.innerHTML = '<i class="bi bi-circle me-1"></i> At least 8 characters';
            }

            // Check uppercase letter
            const uppercaseCheck = document.getElementById('uppercase-check');
            if (/[A-Z]/.test(password)) {
                uppercaseCheck.className = 'text-success';
                uppercaseCheck.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> One uppercase letter';
            } else {
                uppercaseCheck.className = 'text-muted';
                uppercaseCheck.innerHTML = '<i class="bi bi-circle me-1"></i> One uppercase letter';
            }

            // Check number
            const numberCheck = document.getElementById('number-check');
            if (/[0-9]/.test(password)) {
                numberCheck.className = 'text-success';
                numberCheck.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> One number';
            } else {
                numberCheck.className = 'text-muted';
                numberCheck.innerHTML = '<i class="bi bi-circle me-1"></i> One number';
            }

            // Check special character
            const specialCheck = document.getElementById('special-check');
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                specialCheck.className = 'text-success';
                specialCheck.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> One special character';
            } else {
                specialCheck.className = 'text-muted';
                specialCheck.innerHTML = '<i class="bi bi-circle me-1"></i> One special character';
            }
        }
    </script>
</body>

</html>