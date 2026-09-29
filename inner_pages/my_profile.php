<?php
include('../dbcon.php');
include('../layout/session_check.php');

// Then prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Database connection
if (!isset($conn) || !$conn) {
    $conn = mysqli_connect('localhost', 'root', '', 'financial_management');
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }
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

$profile_image = '../resources/images/default_user.png'; // Default
if ($user_id) {
    $query = "SELECT profile_image FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $imgRes = mysqli_stmt_get_result($stmt);
    if ($imgRow = mysqli_fetch_assoc($imgRes)) {
        if (!empty($imgRow['profile_image'])) {
            $profile_image = '../' . $imgRow['profile_image'];
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "My Profile"; ?>
    <?php include('../layout/head.php'); ?>
    <link rel="stylesheet" href="../resources/css/client.css">
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
            <div class="nav profile-nav-tabs mb-4 mt-4">
                <a class="nav-link active">My Profile</a>
                <a class="nav-link" href="change_pw.php">Change Password</a>
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
                                <i class="fas fa-camera"></i>Upload new image
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8">
                    <!-- Account Details Card -->
                    <div class="card account-details-card h-100">
                        <div class="card-header">Account Details</div>
                        <div class="card-body d-flex flex-column justify-content-between" style="height: 100%;">
                            <form id="profileForm" method="POST" action="../forms_logic/update_profile.php">
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
                                    <i class="fas fa-save"></i>Save changes
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
                        <i class="fas fa-camera"></i>Upload Profile Image
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
                                        <i class="fas fa-times"></i>Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="uploadBtn" disabled>
                            <i class="fas fa-upload"></i>Upload Image
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="resources/js/session-protection.js"></script>
    <script src="./resources/js/auto_logout.js"></script>

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
            const datetimeElement = document.getElementById('datetime');
            if (datetimeElement) {
                datetimeElement.textContent = now.toLocaleDateString('en-US', options);
            }
        }

        updateDateTime();
        setInterval(updateDateTime, 60000);

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