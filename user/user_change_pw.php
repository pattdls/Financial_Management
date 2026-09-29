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

// Get user name from session
$name = $_SESSION['auth_user']['name'] ?? 'Guest';

$profile_image = '../resources/images/default_user.png'; // Default fallback image
$role_display = 'USER'; // Default fallback role

if (!empty($user_id)) {
    $query = "SELECT profile_image, role FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $imgRes = mysqli_stmt_get_result($stmt);

    if ($imgRow = mysqli_fetch_assoc($imgRes)) {
        // Set profile image if exists
        if (!empty($imgRow['profile_image'])) {
            $profile_image = '../' . htmlspecialchars($imgRow['profile_image']);
        }
        // Always set role display if found (correct variable is $imgRow, not $row)
        $role_display = strtoupper($imgRow['role']);
    }
    mysqli_stmt_close($stmt);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Change Password"; ?>
    <?php include('../layout/head.php'); ?>
    <link rel="stylesheet" href="../resources/css/client.css">
    <link rel="stylesheet" href="../resources/css/my_profile.css">
    <link rel="stylesheet" href="../resources/css/user_dash.css">
</head>


<body class="d-flex">

     <!-- Logout Confirmation Modal -->
        <div class="modal fade logout-modal" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered logout-modal-dialog">
                <div class="modal-content logout-modal-content rounded-4">
                    <div class="modal-header logout-modal-header">
                        <h1 class="modal-title logout-modal-title" id="logoutModalLabel">Confirm Log Out</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body logout-modal-body">
                        Are you sure you want to log out?
                    </div>
                    <div class="logout-modal-footer mt-3">
                        <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                        <a href="/Financial_Management/forms_logic/logout.php" class="btn btn-danger rounded-pill px-3">Log Out</a>
                    </div>
                </div>
            </div>
        </div>


    <!-- Main Content -->
    <div class="main-content-dash container-fluid p-0">
        <!-- Navigation Bar -->
        <nav class="d-flex flex-row align-items-center justify-content-between px-5 custom-nav">
            <!-- Left Side: Logo and Company Name -->
            <div class="d-flex align-items-center">
                <a href="dashboard.php"><img src="../resources/images/login_page_logo.png" alt="RVR Logo" class="logo-img me-3"></a>
                <div>
                    <div class="rvr_smes">RVR SMES</div>
                    <small class="text-muted welcome-text">Dashboard | Welcome, <?= htmlspecialchars($_SESSION['auth_user']['name']) ?>!</small>
                </div>
            </div>

            <!-- Desktop Right Side (Hidden on Mobile) -->
            <div class="d-none d-md-flex align-items-center">
                <p id="datetime" class="me-3 mb-4"></p>
                <?php
                $user_id = $_SESSION['auth_user']['id'];
                $client_query = "SELECT client_id FROM users WHERE id = $user_id LIMIT 1";
                $client_result = mysqli_query($conn, $client_query);
                $client_row = mysqli_fetch_assoc($client_result);
                $actual_user_id = $client_row['client_id'] ?? $user_id;
                $notif_check = mysqli_query($conn, "SELECT COUNT(*) AS total FROM user_notifications WHERE user_id = $actual_user_id AND is_read = 0");
                $row = mysqli_fetch_assoc($notif_check);
                $total_unread = (int)$row['total'];
                $has_unread = $total_unread > 0;
                $notif_display = $total_unread > 9 ? '9+' : $total_unread;
                ?>
                <div class="notif-icon-wrapper">
                    <a href="user_notifications.php" style="text-decoration: none; color: inherit;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-bell-icon lucide-bell me-3">
                            <path d="M10.268 21a2 2 0 0 0 3.464 0" />
                            <path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326" />
                        </svg>
                        <?php if ($has_unread): ?>
                            <span class="notif-badge"><?php echo $notif_display; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="dropdown">
                    <a href="#" class="user-account d-flex align-items-center text-decoration-none dropdown-toggle"
                        id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?php echo $profile_image; ?>" alt="Profile Image"
                            class="rounded-circle me-2" style="width:40px; height:40px; object-fit:cover;">
                        <div>
                            <div class=""><?php echo htmlspecialchars($role_display); ?></div>
                        </div>
                    </a>
                    <ul class="user-account-dropdown dropdown-menu dropdown-menu-dark text-small shadow">
                        <li><a class="dropdown-item" href="user_profile.php">My Profile</a></li>
                        <li><a class="dropdown-item" href="<?= $site_base_url ?>forms_logic/logout.php" class="nav-link d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#logoutModal">Sign out</a></li>
                    </ul>
                </div>
            </div>

            <!-- Mobile Menu Button (Visible on Mobile Only) -->
            <button class="btn btn-outline-primary d-md-none mobile-menu-btn" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#mobileNavOffcanvas"
                aria-controls="mobileNavOffcanvas">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        </nav>

        <!-- Mobile Offcanvas Menu -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileNavOffcanvas" aria-labelledby="mobileNavOffcanvasLabel">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title fw-bold" id="mobileNavOffcanvasLabel">Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-0">
                <!-- User Profile Section -->
                <div class="p-4 border-bottom bg-light">
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?php echo $profile_image; ?>" alt="Profile Image"
                            class="rounded-circle me-3" style="width:60px; height:60px; object-fit:cover;">
                        <div>
                            <div class="fw-bold"><?php echo htmlspecialchars($role_display); ?></div>
                            <small class="text-muted"><?= htmlspecialchars($_SESSION['auth_user']['name']) ?></small>
                        </div>
                    </div>
                </div>

                <!-- Date & Time -->
                <div class="p-4 border-bottom">
                    <div class="d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="me-3 text-primary">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <div>
                            <small class="text-muted d-block">Current Time</small>
                            <span id="datetime-mobile" class="fw-semibold" style="font-size: 0.9rem;"></span>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="p-4 border-bottom">
                    <a href="user_notifications.php" class="text-decoration-none text-dark d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="me-3 text-primary">
                                <path d="M10.268 21a2 2 0 0 0 3.464 0" />
                                <path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326" />
                            </svg>
                            <span class="fw-semibold">Notifications</span>
                        </div>
                        <?php if ($has_unread): ?>
                            <span class="badge bg-danger rounded-pill"><?php echo $notif_display; ?></span>
                        <?php endif; ?>
                    </a>
                </div>

                <!-- Menu Items -->
                <div class="p-4">
                    <a href="user_profile.php" class="d-flex align-items-center text-decoration-none text-dark mb-3 p-2 rounded hover-bg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="me-3 text-primary">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span class="fw-semibold">My Profile</span>
                    </a>

                    <a href="#" class="d-flex align-items-center text-decoration-none text-danger p-2 rounded hover-bg"
                        data-bs-toggle="modal" data-bs-target="#logoutModal" data-bs-dismiss="offcanvas">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="me-3">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        <span class="fw-semibold">Sign Out</span>
                    </a>
                </div>
            </div>
        </div>

        <hr class="p-0 m-1">

        <div class="container px-4" style="min-height: 90vh;">
            <?php
            if (isset($_SESSION['status'])) {
                echo $_SESSION['status'];
                unset($_SESSION['status']); // Clear the message after displaying
            }
            ?>
            <!-- Alert Fade Out Script -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(() => {
                        const alertEl = document.querySelector('.alert');
                        if (!alertEl) return;

                        // add fade-out class then remove after transition
                        alertEl.classList.add('alert-fade-out');
                        // remove from DOM after transition duration (350ms)
                        setTimeout(() => alertEl.remove(), 400);
                    }, 4000);
                });
            </script>
            <!-- Profile Navigation -->
            <div class="nav profile-nav-tabs mt-4">
                <a class="nav-link" href="user_profile.php">My Profile</a>
                <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'finance' && $_SESSION['auth_user']['role'] !== 'user'): ?>
                    <a class="nav-link" href="company_profile.php">Company Profile</a>
                <?php endif; ?>
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

    <script src="./resources/js/auto_logout.js"></script>

    <script>
        // Update both desktop and mobile datetime
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

            const dateTimeString = now.toLocaleDateString('en-US', options);

            // Update desktop datetime
            const desktopDateTime = document.getElementById('datetime');
            if (desktopDateTime) {
                desktopDateTime.innerHTML = dateTimeString;
            }

            // Update mobile datetime
            const mobileDateTime = document.getElementById('datetime-mobile');
            if (mobileDateTime) {
                mobileDateTime.innerHTML = dateTimeString;
            }
        }

        // Update every second
        setInterval(updateDateTime, 1000);
        updateDateTime();

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