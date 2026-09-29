<?php
include('../dbcon.php'); 
include('../layout/session_check.php'); 

// Restrict access to admin only
if (!isset($_SESSION['auth_user']) || $_SESSION['auth_user']['role'] !== 'admin') {
    header('Location: ../unauthorized.php');
    exit;
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Fetch company data based on session company_id
$companyId = $_SESSION['auth_user']['company_id'] ?? 1;
$company_query = "SELECT * FROM company_profile WHERE id = ?";
$stmt = mysqli_prepare($conn, $company_query);
mysqli_stmt_bind_param($stmt, 'i', $companyId);
mysqli_stmt_execute($stmt);
$company_result = mysqli_stmt_get_result($stmt);
$company_data = mysqli_fetch_assoc($company_result) ?? [];
mysqli_stmt_close($stmt);
$_SESSION['company_data'] = $company_data; // Cache in session

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Company Profile"; ?>
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
        $page_title = 'Clients';
        include '../layout/topnav.php';
        ?>

        <div class="container px-4" style="min-height: 90vh;">
            <?php
            if (isset($_SESSION['status'])) {
                echo $_SESSION['status'];
                unset($_SESSION['status']);
            }
            ?>
            <!-- Profile Navigation -->
            <div class="nav profile-nav-tabs">
                <a class="nav-link" href="my_profile.php">My Profile</a>
                <a class="nav-link active">Company Profile</a>
                <a class="nav-link" href="change_pw.php">Change Password</a>
            </div>

            <div class="row" style="min-height: 10vh;">
                <?php
                $default_image = '../resources/images/rvr logo.png';
                // Check for NULL explicitly
                $image_to_show = !empty($company_data['company_image']) && $company_data['company_image'] !== null
                    ? htmlspecialchars($company_data['company_image'])
                    : $default_image;
                $image_to_show .= '?t=' . time(); // Cache-busting
                ?>

                <div class="col-xl-4">
                    <!-- Profile Picture Card -->
                    <div class="card profile-picture-card mb-4 mb-xl-0 mt-4" style="height: 400px;">
                        <div class="card-header">Company Logo</div>
                        <div class="card-body text-center d-flex flex-column justify-content-center align-items-center" style="height: 100%;">
                            <img class="profile-picture mb-3" src="<?= $image_to_show ?>" alt="Company Logo" id="profileImage" style="width:200px; height:200px;">
                            <div class="small text-muted mb-3">JPG or PNG no larger than 5 MB</div>
                            <button class="btn btn-upload" type="button" data-bs-toggle="modal" data-bs-target="#uploadImageModal">
                                Upload new image
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8">
                    <!-- Company Details Card -->
                    <div class="card account-details-card h-100 mt-4">
                        <div class="card-header">Company Details</div>
                        <div class="card-body d-flex flex-column justify-content-between" style="height: 100%;">
                            <form id="companyForm" method="POST" action="../forms_logic/company_profile_logic.php">
                                <input type="hidden" name="company_id" value="<?php echo htmlspecialchars($company_data['id'] ?? $companyId); ?>">
                                <div class="mb-3">
                                    <label class="form-label" for="companyName">Company Name <span style="color: red;">*</span></label>
                                    <input class="form-control" id="companyName" name="company_name" type="text" placeholder="Enter company name" value="<?php echo htmlspecialchars($company_data['company_name'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="registrationNumber">Business Registration Number</label>
                                    <input class="form-control" id="registrationNumber" name="business_reg_num" type="text" placeholder="Enter business registration number" value="<?php echo htmlspecialchars($company_data['business_reg_num'] ?? ''); ?>">
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="industry">Industry</label>
                                        <select class="form-control" id="industry" name="industry">
                                            <option value="">Select Industry</option>
                                            <?php
                                            $industries = ["Technology", "Finance", "Healthcare", "Education", "Retail", "Manufacturing", "Construction", "Other"];
                                            foreach ($industries as $industry) {
                                                $selected = ($company_data['industry'] ?? '') === $industry ? 'selected' : '';
                                                echo "<option value=\"$industry\" $selected>$industry</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="companySize">Company Size</label>
                                        <select class="form-control" id="companySize" name="company_size">
                                            <option value="">Select Size</option>
                                            <?php
                                            $sizes = ["1-10" => "1-10 employees", "11-50" => "11-50 employees", "51-200" => "51-200 employees", "201-500" => "201-500 employees", "501+" => "501+ employees"];
                                            foreach ($sizes as $key => $label) {
                                                $selected = ($company_data['company_size'] ?? '') === $key ? 'selected' : '';
                                                echo "<option value=\"$key\" $selected>$label</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="companyAddress">Company Address</label>
                                    <textarea class="form-control" id="companyAddress" name="company_address" rows="3" placeholder="Enter complete company address"><?php echo htmlspecialchars($company_data['company_address'] ?? ''); ?></textarea>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="companyPhone">Company Phone</label>
                                        <input class="form-control" id="companyPhone" name="company_phone" type="tel" placeholder="Enter company phone number" value="<?php echo htmlspecialchars($company_data['phone_number'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="companyEmail">Company Email</label>
                                        <input class="form-control" id="companyEmail" name="company_email" type="email" placeholder="Enter company email address" value="<?php echo htmlspecialchars($company_data['email'] ?? ''); ?>">
                                    </div>
                                </div>
                                <button class="btn btn-primary" type="submit" name="update_company">Save changes</button>
                            </form>
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
                    <h5 class="modal-title" id="uploadImageModalLabel">Upload Company Logo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="uploadImageForm" method="POST" action="../forms_logic/upload_company_image.php" enctype="multipart/form-data">
                    <div class="modal-body text-center">
                        <div class="mb-3">
                            <img src="<?= $image_to_show ?>" alt="Current Logo" class="profile-picture" id="currentImagePreview">
                        </div>
                        <div class="mb-3">
                            <label for="profileImageFile" class="form-label">Choose New Image</label>
                            <input type="file" class="form-control" id="profileImageFile" name="company_image" accept="image/jpeg,image/png" required>
                            <small class="form-text text-muted">Supported formats: JPG, PNG. Max size: 5MB</small>
                        </div>
                        <div class="mb-3" id="newImagePreview" style="display: none;">
                            <label class="form-label">Preview:</label>
                            <div>
                                <img id="imagePreview" class="profile-picture">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="upload_company_image">Upload Image</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="./resources/js/auto_logout.js"></script>

    <script>
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

        document.getElementById('profileImageFile').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                    document.getElementById('newImagePreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>

</html>