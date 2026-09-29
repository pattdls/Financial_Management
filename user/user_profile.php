<?php
include('../dbcon.php');
include('../layout/session_check.php');

// Then prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

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
    <?php $pageTitle = "My Profile"; ?>
    <?php include('../layout/head.php'); ?>
    <link rel="stylesheet" href="../resources/css/my_profile.css">
    <link rel="stylesheet" href="../resources/css/main.css">
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
            <div class="nav profile-nav-tabs mb-4 mt-4">
                <a class="nav-link active">My Profile</a>
                <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'finance' && $_SESSION['auth_user']['role'] !== 'user'): ?>
                    <a class="nav-link" href="company_profile.php">Company Profile</a>
                <?php endif; ?>
                <a class="nav-link" href="user_change_pw.php">Change Password</a>
            </div>

            <div class="row" style="min-height: 10vh;">
                <div class="col-xl-4">
                    <!-- Profile Picture Card -->
                    <div class="card profile-picture-card mb-4 mb-xl-0" style="height: 400px;">
                        <div class="card-header">Profile Picture</div>
                        <div class="card-body text-center d-flex flex-column justify-content-center align-items-center" style="height: 100%;">
                            <!-- Profile picture image -->
                            <img class="profile-picture mb-3" src="<?= htmlspecialchars($profile_image) ?>" alt="Profile Picture" id="profileImage">
                            <!-- Profile picture help block -->
                            <div class="small text-muted mb-3">JPG or PNG no larger than 5 MB</div>
                            <!-- Profile picture upload button -->
                            <button class="btn btn-upload" type="button" data-bs-toggle="modal" data-bs-target="#uploadImageModal">
                                <i class="fas fa-camera me-2"></i>Upload new image
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8">
                    <!-- Account Details Card -->
                    <div class="card account-details-card h-100">
                        <div class="card-header">Account Details</div>
                        <div class="card-body d-flex flex-column justify-content-between" style="height: 100%;">
                            <form id="profileForm" method="POST" action="user_update_profile.php">
                                <!-- Name Row -->
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold" for="inputFirstName">Full Name</label>
                                        <input class="form-control" id="inputFirstName" name="first_name" type="text" placeholder="Enter your first name" value="<?php echo htmlspecialchars($user_data['first_name']); ?>">
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="inputEmailAddress">Email address</label>
                                    <input class="form-control" id="inputEmailAddress" name="email" type="email" placeholder="Enter your email address" value="<?php echo htmlspecialchars($email); ?>">
                                </div>

                                <!-- Phone and Role Row -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" for="inputPhone">Phone number</label>
                                        <input class="form-control" id="inputPhone" name="phone_number" type="tel" placeholder="Enter your phone number" value="<?php echo htmlspecialchars($user_data['phone_number']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" for="inputRole">Role</label>
                                        <input class="form-control" id="inputRole" type="text" value="<?php echo ucfirst(htmlspecialchars($role)); ?>" readonly style="background-color: #f8f9fa;">
                                    </div>
                                </div>

                                <!-- Save Button -->
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-save me-2"></i>Save changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Image Modal -->
    <div class="modal fade" id="uploadImageModal" tabindex="-1" aria-labelledby="uploadImageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadImageModalLabel">
                        <i class="fas fa-camera me-2"></i>Upload Profile Image
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="uploadImageForm" method="POST" action="../forms_logic/upload_image.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <!-- Current Image -->
                        <div class="text-center mb-4">
                            <div class="small text-muted mb-2">Current Image</div>
                            <img src="<?= htmlspecialchars($profile_image) ?>" alt="Current Profile" class="current-image-preview" id="currentImagePreview">
                        </div>

                        <!-- File Upload Area -->
                        <div class="file-upload-area" onclick="document.getElementById('profileImageFile').click()">
                            <div class="upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="fw-semibold mb-1">Choose a new image</div>
                            <div class="small text-muted">Click here or drag and drop</div>
                            <div class="small text-muted mt-2">Supported: JPG, PNG, GIF • Max: 5MB</div>
                        </div>

                        <input type="file" class="file-input" id="profileImageFile" name="profile_image" accept="image/*" required>

                        <!-- Preview Container -->
                        <div class="preview-container" id="previewContainer" style="display: none;">
                            <div class="text-center">
                                <div class="small text-muted mb-2">New Image Preview</div>
                                <img id="imagePreview" class="new-image-preview" alt="Preview">
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearPreview()">
                                        <i class="fas fa-times me-1"></i>Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="uploadBtn" disabled>
                            <i class="fas fa-upload me-2"></i>Upload Image
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="resources/js/session-protection.js"></script>
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
        // File upload handling
        const fileInput = document.getElementById('profileImageFile');
        const uploadArea = document.querySelector('.file-upload-area');
        const previewContainer = document.getElementById('previewContainer');
        const imagePreview = document.getElementById('imagePreview');
        const uploadBtn = document.getElementById('uploadBtn');

        // File input change event
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                handleFileSelect(file);
            }
        });

        // Drag and drop functionality
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                if (file.type.startsWith('image/')) {
                    fileInput.files = files;
                    handleFileSelect(file);
                }
            }
        });

        function handleFileSelect(file) {
            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                clearPreview();
                return;
            }

            // Validate file type
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file');
                clearPreview();
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                previewContainer.style.display = 'block';
                uploadBtn.disabled = false;
            };
            reader.readAsDataURL(file);
        }

        function clearPreview() {
            fileInput.value = '';
            imagePreview.src = '';
            previewContainer.style.display = 'none';
            uploadBtn.disabled = true;
        }

        // Reset modal when closed
        document.getElementById('uploadImageModal').addEventListener('hidden.bs.modal', function() {
            clearPreview();
        });

        // Form validation
        document.getElementById('uploadImageForm').addEventListener('submit', function(e) {
            if (!fileInput.files[0]) {
                e.preventDefault();
                alert('Please select an image to upload');
            }
        });
    </script>
</body>

</html>