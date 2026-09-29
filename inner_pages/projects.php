<?php
session_start();
include('../dbcon.php');
include('../layout/session_check.php');

// Redirect if not logged in
if (!isset($_SESSION['auth_user'])) {
    header('Location: ../login_form.php');
    exit();
}

$user_id = $_SESSION['auth_user']['id'] ?? null;

// if ($conn && $user_id) {
//     $stmt = $conn->prepare("SELECT verify_status, updated_at FROM users WHERE id = ?");
//     $stmt->bind_param("i", $user_id);
//     $stmt->execute();
//     $user = $stmt->get_result()->fetch_assoc();

//     // If account removed/disabled, log out
//     if (!$user || $user['verify_status'] === 'disabled') {
//         header("Location: /Financial_Management/forms_logic/logout.php");
//         exit();
//     }

//     // Auto logout if password was reset AFTER login
//     if (isset($_SESSION['auth_user']['login_time']) && isset($user['updated_at'])) {
//         if (strtotime($user['updated_at']) > $_SESSION['auth_user']['login_time']) {
//             header("Location: /Financial_Management/forms_logic/logout.php");
//             exit();
//         }
//     }
// }

// // Auto logout after inactivity of 30 MINUTES
// $inactive = 300; // 5 minutes

// // Initialize last_activity if not set (first visit)
// if (!isset($_SESSION['last_activity'])) {
//     $_SESSION['last_activity'] = time();
// }

// // Check for inactivity timeout
// if (isset($_SESSION['last_activity'])) {
//     $session_life = time() - $_SESSION['last_activity'];
//     if ($session_life > $inactive) {
//         session_unset();
//         session_destroy();
//         header("Location: /Financial_Management/login_form.php?message=Session expired due to inactivity");
//         exit();
//     }
// }

$connection = mysqli_connect("localhost", "root", "", "financial_management");

// Check if client_id is provided
if (isset($_GET['client_id'])) {
    $client_id = mysqli_real_escape_string($connection, $_GET['client_id']);

    // Fetch projects for the selected client
    $fetch_projects_query = "SELECT * FROM projects WHERE client_id = '$client_id'";
    $fetch_projects_run = mysqli_query($connection, $fetch_projects_query);
    $has_projects = mysqli_num_rows($fetch_projects_run) > 0;

    // Fetch client details (optional, for displaying client name)
    $client_query = "SELECT * FROM clients WHERE client_id = '$client_id'";
    $client_result = mysqli_query($connection, $client_query);
    $client = mysqli_fetch_assoc($client_result);
} else {
    echo "No client selected.";
    exit;
}

// Check if a contract is uploaded for this client
// $contract_uploaded = false;
// $contract_query = "SELECT * FROM contracts WHERE client_id = '$client_id' LIMIT 1";
// $contract_result = mysqli_query($connection, $contract_query);
// if (mysqli_num_rows($contract_result) > 0) {
//     $contract_uploaded = true;
// } else {
//     // Auto-upload contract if not present
//     $template_path = '../resources/contracts/contract_template.pdf'; // Path to your template
//     $upload_dir = '../uploads/contracts/';
//     if (!is_dir($upload_dir)) {
//         mkdir($upload_dir, 0777, true);
//     }
//     $new_file_name = $upload_dir . 'contract_' . $client_id . '_' . time() . '.pdf';
//     if (file_exists($template_path)) {
//         if (copy($template_path, $new_file_name)) {
//             $insert = "INSERT INTO contracts (client_id, file_path) VALUES ('$client_id', '$new_file_name')";
//             mysqli_query($connection, $insert);
//             $contract_uploaded = true;
//         }
//     }
// }

// $projects_query = "SELECT COUNT(*) as project_count FROM projects WHERE client_id = ?";
// $stmt = $conn->prepare($projects_query);
// $stmt->bind_param("i", $client_id);
// $stmt->execute();
// $projects_result = $stmt->get_result();
// $project_data = $projects_result->fetch_assoc();
// $has_projects = $project_data['project_count'] > 0;

include('../forms_logic/update_project_status.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Projects"; ?>
    <?php include('../layout/head.php'); ?>
    <link rel="stylesheet" href="../resources/css/projects.css">
</head>

<body>

<div class="d-flex">
        <!-- Side Nav Container -->
        <?php include('../layout/sidenav.php'); ?>

        <!-- Main Content -->
        <div class="main-content container-fluid ">

            <!-- Top Nav -->
            <?php
            $page_title = 'Projects';
            include '../layout/topnav.php';
            ?>
            <div class="nav-container-fluid">

                 <!-- Breadcrumbs -->
                <div style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708'/%3E%3C/svg%3E&#34;);" aria-label="breadcrumb">
                    <ol class="breadcrumb" id="projects-breadcrumb">
                        <li class="breadcrumb-item"><a href="clients.php">Clients</a></li>
                        <li class="breadcrumb-item active"> <?php echo isset($client['client_name']) ? $client['client_name'] : 'Unknown Client'; ?></li>
                        <li class="breadcrumb-item d-none" id="details-li">Project Details</li>
                    </ol>
                </div>

                <script>
                    // Show "Project Details" breadcrumb only when "Projects" is clicked
                    document.addEventListener('DOMContentLoaded', function() {
                        var projectsLi = document.getElementById('projects-li');
                        var detailsLi = document.getElementById('details-li');
                        if (projectsLi && detailsLi) {
                            projectsLi.style.cursor = 'pointer';
                            projectsLi.addEventListener('click', function() {
                                detailsLi.classList.remove('d-none');
                                detailsLi.classList.add('active');
                                projectsLi.classList.remove('active');
                                projectsLi.removeAttribute('aria-current');
                            });
                        }
                    });
                </script>

                <?php if (!$has_projects): ?>

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
                        }, 4000); // 5 seconds
                    });
                });
            </script>
            

                    <!-- First Project Welcome Screen -->
                    <div class="welcome-screen container d-flex justify-content-center">
                        <div class="text-center ">
                            <img src="../resources/svg/undraw_city-life_l74x (1).svg" alt="Project Initiation"
                                class="proj-empty-svg img-fluid " style="max-width: 600px;">

                            <div class="add-first-proj-title">Add Your Very First Project</div>
                            <div class="mx-auto">
                                <div class="text-muted mb-3 mt-2 first-proj-desc">
                                    Get started by creating your first project for this client.
                                </div>
                            </div>

                            <!-- First Add Project Button -->
                            <div class="d-flex justify-content-center mt-4">
                                <button type="button " class="btn px-4 py-2 shadow-sm add-first-proj-btn"
                                    data-bs-toggle="modal" data-bs-target="#insertdata" style="background-color: #183b4e; color: white; border: none;">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    Add Project
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- First Add Project Modal -->
                    <div class="modal fade" id="insertdata" tabindex="-1" aria-labelledby="insertdataLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" style="max-width: 650px;"> <!-- Custom width in pixels -->
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="insertdata">Project Information</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                
                                <div class="modal-body">
                                    <form id="projectForm" action="../forms_logic/project_logic.php" method="POST" enctype="multipart/form-data">
                                        <div class="">
                                            <input type="hidden" name="client_id" value="<?php echo $_GET['client_id']; ?>">
                                        </div>

                                        <div class="row mb-1">
                                            <!-- Project Name -->
                                            <div class="col-md-8">
                                                <label class="form-label">Project Name<span style="color: red;">*</span></label>
                                                <input type="text" class="form-control" name="project_name" required>
                                            </div>
                                            <!-- P.O Number -->
                                             <div class="col-md-4">
                                                <label class="form-label">P.O Number<span style="color: red;">*</span></label>
                                                <input type="text" name="po_num" class="form-control" required>
                                            </div>
                                        </div>

                                          <!-- Project Dates -->
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="start_date" class="form-label">Start Date <span style="color: red;">*</span></label><br>
                                                <input type="date" class="form-control" id="start_date" name="start_date" required placeholder="Please select a start date.">
                                            </div>

                                            <div class="col-md-6">
                                                <label for="end_date" class="form-label">End Date <span style="color: red;">*</span></label><br>
                                                <input type="date" class="form-control" id="end_date" name="end_date" required placeholder="Please select an end date.">
                                            </div>
                                        </div>

                                        <!-- Total Project Cost -->
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Project Cost<span style="color: red;">*</span></label>
                                                <div class="input-group">
                                                     <span class="input-group-text fs-6 px-2 py-1">₱</span>
                                                    <input type="number"
                                                        id="project_cost"
                                                        class="budget-input form-control fs-5 text-muted"
                                                        name="projected_budget_cost"
                                                        required
                                                        placeholder="0.00"
                                                        style="box-shadow: none;"
                                                        min="1000"
                                                        step="any">
                                                </div>
                                                <span class="budget-warning text-danger mt-1" style="display: none; font-size: 0.83rem;">Project budget cost cannot be less than ₱1,000.</span>
                                            </div>

                                            <!-- Project Area -->
                                            <div class="col-md-6">
                                                <label class="form-label">Project Area<span style="color: red;">*</span></label>
                                                <input type="text" class="form-control py-2" name="project_type" required placeholder="Enter Project Area">
                                            </div>
                                        </div>

                                        <label class="form-label mb-2">Allocated Budget Cost<span style="color: red;">*</span></label>
                                        <div class="row">
                                            <!-- Materials Cost -->
                                            <div class="col-md-4">
                                                <div class="input-group">
                                                     <span class="input-group-text fs-6 px-2 py-1">₱</span>
                                                    <input type="number" id="materials_cost" class="budget2-input form-control fs-5 text-muted" name="materials_cost" required placeholder="0.00" style="box-shadow: none;" min="0" step="any">
                                                </div>
                                                <label class="form-label text-muted">Materials Cost</label>
                                            </div>

                                            <!-- Labor Cost -->
                                            <div class="col-md-4">
                                                <div class="input-group">
                                                     <span class="input-group-text fs-6 px-2 py-1">₱</span>
                                                    <input type="number" id="labor_cost" class="budget2-input form-control fs-5 text-muted" name="labor_cost" required placeholder="0.00" style="box-shadow: none;" min="0" step="any">
                                                </div>
                                                <label class="form-label text-muted">Labor Cost</label>
                                            </div>

                                            <!-- Other Expenses Cost -->
                                            <div class="col-md-4">
                                                <div class="input-group">
                                                     <span class="input-group-text fs-6 px-2 py-1">₱</span>
                                                    <input type="number" id="other_expenses_cost" class="budget2-input form-control fs-5 text-muted" name="other_expenses_cost" required placeholder="0.00" style="box-shadow: none;" min="0" step="any">
                                                </div>
                                                <label class="form-label text-muted">Other Expenses Cost</label>
                                            </div>
                                        </div>

                                        <!-- Allocated Budget -->
                                        <label class="form-label mb-2">Total Allocated Budget Cost<span style="color: red;">*</span></label>
                                        <div class="row mb-4">
                                            <div class="col-md-12">
                                                <div class="input-group">
                                                     <span class="input-group-text fs-6 px-2 py-1">₱</span>
                                                    <input type="number" id="allocated_budget" class="budget2-input form-control fs-5 text-muted" name="allocated_budget" readonly placeholder="0.00" style="box-shadow: none;">
                                                </div>
                                                <div id="budgetWarning" class="text-danger mt-1" style="display: none; font-size: 0.83rem;"></div>
                                            </div>
                                        </div>

                                        <!-- File Upload -->
                                        <div class="row mb-4">
                                            <div class="col-md-12">
                                                <label for="project_file_main" class="form-label">Upload Contract Document</label>
                                                <input type="file" class="form-control" id="project_file_main" name="project_file" accept="application/pdf,image/png,image/jpeg">
                                                <small class="form-text text-muted">Optional: Upload a PDF, PNG, JPEG, and JPG files. Max size: 5MB.</small>
                                            </div>
                                        </div>

                                        <!-- Preview Modal for Add Project Form -->
                                        <div class="modal fade" id="previewContractMain" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Preview Contract Document</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body text-center">
                                                        <p id="previewFileName_main" class="fw-bold"></p>
                                                        <img id="previewImage_main" src="" class="img-fluid d-none" alt="Preview Image">
                                                        <iframe id="previewPDF_main" src="" class="w-100 d-none" style="height:500px;"></iframe>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" id="cancelFile_main" class="btn btn-secondary">Cancel</button>
                                                        <button type="button" id="confirmFile_main" class="btn btn-primary">Confirm</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Save and Cancel Button -->
                                        <div class="row">
                                            <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="save_project" class="btn btn-success">Save Project</button>
                                            </div>
                                        </div>
                                </div>

                                </form>
                            </div>

                        </div>
                    </div>
            </div>

        <?php endif; ?>

        <?php if ($has_projects): ?>
        
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
                        }, 4000); // 5 seconds
                    });
                });
            </script>
            

           
            <!-- Add Project Button -->
            <?php
            // Add Project Button (Fixed to use #insertdata)
            $addProjectButton = '';
            if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'finance') {
                $addProjectButton = '
                                    <button type="button"
                                        class="btn add-custom-outline-btn btn-md px-3 py-2 ms-2 shadow-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#insertdata">
                                        Add Project <i class="bi bi-plus-circle ms-1"></i>
                                    </button>
                                    ';
            }
            ?>

            <!-- Add Project Modal -->
            <div class="modal fade" id="insertdata" tabindex="-1" aria-labelledby="insertdataLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 650px;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="insertdata">Project Information</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        
                        <div class="modal-body">
                            <form id="projectForm" action="../forms_logic/project_logic.php" method="POST" enctype="multipart/form-data">
                                <div>
                                    <input type="hidden" name="client_id" value="<?php echo $_GET['client_id']; ?>">
                                </div>

                                <div class="row mb-3">
                                    <!-- Project Name -->
                                    <div class="col-md-8">
                                        <label class="form-label">Project Name<span style="color: red;">*</span></label>
                                        <input type="text" class="form-control" name="project_name" required>
                                    </div>
                                    <!-- P.O Number -->
                                    <div class="col-md-4">
                                        <label class="form-label">P.O Number<span style="color: red;">*</span></label>
                                        <input type="text" name="po_num" class="form-control" required>
                                    </div>
                                </div>

                                <!-- Project Dates -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="start_date" class="form-label">Start Date: <span style="color: red;">*</span></label><br>
                                        <input type="date" class="form-control" id="start_date" name="start_date" required placeholder="Select Start Date">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="end_date" class="form-label">End Date: <span style="color: red;">*</span></label><br>
                                        <input type="date" class="form-control" id="end_date" name="end_date" required placeholder="Select End Date">
                                    </div>
                                </div>

                                <!-- Total Project Cost -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Project Price<span style="color: red;">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text fs-6 px-2 py-1">₱</span>
                                            <input type="number"
                                                id="project_cost"
                                                class="budget-input form-control fs-5 text-muted py-1"
                                                name="projected_budget_cost"
                                                required
                                                placeholder="0.00"
                                                style="box-shadow: none;"
                                                min="1000"
                                                step="any">
                                        </div>
                                        <span class="budget-warning text-danger mt-1" style="display: none; font-size: 0.83rem;">Project budget cost cannot be less than ₱1,000.</span>
                                    </div>

                                    <!-- Project Area -->
                                    <div class="col-md-6">
                                        <label class="form-label">Project Area<span style="color: red;">*</span></label>
                                        <input type="text" class="form-control py-2" name="project_type" required placeholder="Enter Project Area">
                                    </div>
                                </div>

                                <label class="form-label mb-2">Allocated Budget Cost<span style="color: red;">*</span></label>
                                <div class="row">
                                    <!-- Materials Cost -->
                                    <div class="col-md-4">
                                        <div class="input-group">
                                            <span class="input-group-text fs-6 px-2">₱</span>
                                            <input type="number" id="materials_cost" class="budget2-input form-control fs-5 text-muted py-1" name="materials_cost" required placeholder="0.00" style="box-shadow: none;" min="0" step="any">
                                        </div>
                                        <label class="form-label text-muted">Materials Cost</label>
                                    </div>

                                    <!-- Labor Cost -->
                                    <div class="col-md-4">
                                        <div class="input-group">
                                            <span class="input-group-text fs-6 px-2">₱</span>
                                            <input type="number" id="labor_cost" class="budget2-input form-control fs-5 text-muted py-1" name="labor_cost" required placeholder="0.00" style="box-shadow: none;" min="0" step="any">
                                        </div>
                                        <label class="form-label text-muted">Labor Cost</label>
                                    </div>

                                    <!-- Other Expenses Cost -->
                                    <div class="col-md-4">
                                        <div class="input-group">
                                            <span class="input-group-text fs-6 px-2">₱</span>
                                            <input type="number" id="other_expenses_cost" class="budget2-input form-control fs-5 text-muted py-1" name="other_expenses_cost" required placeholder="0.00" style="box-shadow: none;" min="0" step="any">
                                        </div>
                                        <label class="form-label text-muted">Other Expenses Cost</label>
                                    </div>
                                </div>

                                <!-- Allocated Budget -->
                                <label class="form-label mb-2">Total Allocated Budget Cost<span style="color: red;">*</span></label>
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <div class="input-group">
                                            <span class="input-group-text fs-6 px-2">₱</span>
                                            <input type="number" id="allocated_budget" class="budget2-input form-control fs-5 text-muted py-1" name="allocated_budget" readonly placeholder="0.00" style="box-shadow: none;">
                                        </div>
                                        <div id="budgetWarning" class="text-danger mt-1" style="display: none; font-size: 0.83rem;"></div>
                                    </div>
                                </div>

                                <!-- File Upload -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <label for="project_file_main" class="form-label">Upload Contract Document</label>
                                        <input type="file" class="form-control" id="project_file_main" name="project_file" accept="application/pdf,image/png,image/jpeg">
                                        <small class="form-text text-muted">Optional: Upload a PDF, PNG, JPEG, and JPG files. Max size: 5MB.</small>
                                    </div>
                                </div>

                                <!-- Preview Modal for Add Project Form -->
                                <div class="modal fade" id="previewContractMain" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Preview Contract Document</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-center">
                                                <p id="previewFileName_main" class="fw-bold"></p>
                                                <img id="previewImage_main" src="" class="img-fluid d-none" alt="Preview Image">
                                                <iframe id="previewPDF_main" src="" class="w-100 d-none" style="height:500px;"></iframe>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" id="cancelFile_main" class="btn btn-secondary">Cancel</button>
                                                <button type="button" id="confirmFile_main" class="btn btn-primary">Confirm</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Save and Cancel Button -->
                                <div class="row">
                                     <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                                         <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="save_project" class="btn btn-success">Save Project</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>


            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const startDateInput = document.getElementById("start_date");
                    const startDateInfoModalEl = document.getElementById("startDateInfoModal");
                    const understoodBtn = startDateInfoModalEl.querySelector(".btn-primary");
                    const startDateInfoModal = new bootstrap.Modal(startDateInfoModalEl, {
                        focus: false // Prevent Bootstrap from auto-focusing anything in the modal
                    });

                    let infoShown = false;

                    startDateInput.addEventListener("focus", function() {
                        if (!infoShown) {
                            // Prevent the focus from jumping away
                            setTimeout(() => startDateInfoModal.show(), 100);
                        }
                    });

                    understoodBtn.addEventListener("click", function() {
                        infoShown = true;
                        // Focus back to start date input to show calendar
                        setTimeout(() => startDateInput.focus(), 300);
                    });

                    const addProjectModalEl = document.getElementById("insertdata");
                    addProjectModalEl.addEventListener("hidden.bs.modal", function() {
                        infoShown = false;
                    });
                });
            </script>

            <!-- Added Project Notification -->
            <?php
            if (isset($_SESSION['status']) && $_SESSION['status'] != '') {
            ?>
                <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-white border-0 shadow-sm">
                            <div class="modal-header border-0 ">
                                <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
                            </div>
                            <div class="modal-body text-center" style="padding-top: 10px;">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 70px; margin-bottom: 50px;"></i>

                                <h4 class="mb-0" style="margin-top: -5px;">Project Added Successfully</h4>
                                <p class="pt-3">
                                    The project has been successfully added to the system. <br> You can now manage their details in the project list.
                                </p>
                                <div class="modal-footer border-0 d-flex justify-content-center">
                                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">Proceed</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Auto-trigger the modal when a session status exists -->
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        var successModal = new bootstrap.Modal(document.getElementById('staticBackdrop'));
                        successModal.show();
                    });
                </script>
            <?php
                unset($_SESSION['status']);
            }
            ?>

            <!-- Project List -->
            <div id="client-list" class="container-fluid table-bordered row mt-4">
                <script>
                    $(document).ready(function() {
                        $('#projectsTable').DataTable({
                            responsive: true,
                            pageLength: 10, // Default number of rows per page
                            language: {
                                search: '',
                                searchPlaceholder: "Search projects...",
                                paginate: {
                                    previous: '<i class="bi bi-chevron-left"></i>', // icon only
                                    next: '<i class="bi bi-chevron-right"></i>' // icon only
                                }
                            },
                            dom: "<'row mb-3'<'col-sm-6'l><'col-sm-6 text-end'f>>" +
                                "<'table-responsive'tr>" +
                                "<'row mt-3'<'col-sm-6'i><'col-sm-6 text-end'p>>",
                            initComplete: function() {
                                // Wrap the search box
                                let $filter = $('div.dataTables_filter');
                                let $input = $filter.find('input');
                                $input.wrap('<div class="input-group"></div>');
                                $input
                                    .addClass('form-control');

                                // Append the Add Project button (from PHP variable)
                                <?php if ($addProjectButton): ?>
                                    $filter.append(`<?php echo $addProjectButton; ?>`);
                                <?php endif; ?>
                            }
                        });
                    });
                </script>
                <div class="table-container">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered rounded-3" id="projectsTable">
                            <thead>
                                <tr>
                                    <th scope="col" style="text-transform: none;">Project Name</th>
                                    <th scope="col" style="text-transform: none;">P.O Number</th>
                                    <th scope="col" style="text-transform: none;">Status</th>
                                    <th scope="col" style="text-transform: none;">Approval</th>
                                    <th scope="col" style="text-transform: none;" class="text-center">Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                $connection = mysqli_connect("localhost", "root", "", "financial_management");

                                // Order by project_id DESC to show latest added project first
                                $fetch_query = "SELECT * FROM projects WHERE client_id = '$client_id' ORDER BY project_id ASC";
                                $fetch_query_run = mysqli_query($connection, $fetch_query);

                                if (mysqli_num_rows($fetch_query_run) > 0) {
                                    while ($row = mysqli_fetch_array($fetch_query_run)) {
                                ?>

                                        <!-- Enhanced Clickable Project Row -->
                                        <tr class="clickable-row" onclick="window.location.href='proj_stat.php?project_id=<?php echo $row['project_id']; ?>'">
                                            <!-- Project Name -->
                                            <td class="project-name-cell">
                                                <div class="project-info">
                                                    <div class="project-title">
                                                        <?php echo htmlspecialchars($row['project_name']); ?>
                                                    </div>
                                                    <div class="project-budget">
                                                        Total Budget Cost: ₱<?php echo number_format(floatval(str_replace(',', '', $row['projected_budget_cost'])), 2); ?>
                                                    </div>
                                                    <?php
                                                    // Check if the project has add-ons
                                                    $project_id_check = $row['project_id'];
                                                    $addon_check_sql = "SELECT COUNT(*) as total_addons 
                                FROM project_addons 
                                WHERE project_id = '$project_id_check'";
                                                    $addon_check_result = mysqli_query($connection, $addon_check_sql);
                                                    $addon_count = mysqli_fetch_assoc($addon_check_result)['total_addons'];

                                                    if ($addon_count > 0) {
                                                        echo '<div class="project-addon-badge">
                        <span class="badge bg-info text-dark">With Project Add-ons</span>
                      </div>';
                                                    }
                                                    ?>
                                                    <div class="click-hint-project">
                                                        <i class="bi bi-cursor-fill"></i> Click to view project details
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- P.O Number -->
                                            <td class="po-number-cell">
                                                <span class="po-number"><?php echo htmlspecialchars($row['po_num']); ?></span>
                                            </td>

                                            <!-- Project Status -->
                                            <td class="status-cell">
                                                <?php
                                                $today = date("Y-m-d");
                                                $project_status = $row['project_status'];
                                                $completed_date = $row['completed_date'] ?? null;

                                                // Check if project is pending approval
                                                $clientApproved = $row['user_approval'] ?? 0;
                                                $financeApproved = $row['finance_approval'] ?? 0;

                                                if (!$clientApproved || !$financeApproved) {
                                                    echo '<span class="status-badge badge-pending">Pending Approval</span>';
                                                } elseif ($project_status == "Completed") {
                                                    echo '<span class="status-badge badge-completed">Completed</span>';
                                                    if ($completed_date) {
                                                        echo "<div class='completion-date'>Completed on: " . date('M d, Y', strtotime($completed_date)) . "</div>";
                                                    }
                                                } elseif ($today > $row['end_date']) {
                                                    echo '<span class="status-badge badge-overdue">Overdue</span>';
                                                } elseif ($today >= $row['start_date'] && $today <= $row['end_date']) {
                                                    echo '<span class="status-badge badge-ongoing">Ongoing</span>';
                                                } else {
                                                    echo '<span class="status-badge badge-upcoming">Not yet started</span>';
                                                }
                                                ?>
                                            </td>

                                            <!-- Approval -->
                                            <td class="approval-cell">
                                                <?php
                                                $user_approval = $row['user_approval'];
                                                $finance_approval = $row['finance_approval'];
                                                $total_approvals = $user_approval + $finance_approval;

                                                if ($total_approvals == 2) {
                                                    echo "<span class='approval-badge badge-approved'>Approved 2/2</span>";
                                                } else {
                                                    echo "<span class='approval-badge badge-pending-approval'>Pending {$total_approvals}/2</span>";
                                                }
                                                ?>
                                            </td>

                                            <!-- Actions -->
                                            <td class="action-buttons" onclick="event.stopPropagation();">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'finance') { ?>
                                                        <?php
                                                        // Check approvals
                                                        $financeApproved = ($finance_approval == 1);
                                                        $clientApproved = ($user_approval == 1);

                                                        // Check if all project phases are completed
                                                        $allPhasesCompleted = false;
                                                        $phaseQuery = "SELECT COUNT(*) AS total, 
                              SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed
                       FROM project_phases 
                       WHERE project_id = '{$row['project_id']}'";
                                                        $phaseResult = mysqli_query($conn, $phaseQuery);
                                                        if ($phaseResult && $phaseData = mysqli_fetch_assoc($phaseResult)) {
                                                            $allPhasesCompleted = ($phaseData['total'] > 0 && $phaseData['total'] == $phaseData['completed']);
                                                        }

                                                        // Show button only if all conditions met
                                                        if ($row['project_status'] != "Completed" && $financeApproved && $clientApproved && $allPhasesCompleted) { ?>
                                                            <button class="btn btn-sm btn-confirm" data-bs-toggle="modal" data-bs-target="#confirmModal<?php echo $row['project_id']; ?>" onclick="event.stopPropagation();">
                                                                <i class="bi bi-check2-square" data-bs-toggle="tooltip" title="Mark as Completed"></i>
                                                            </button>
                                                        <?php } ?>

                                                        <?php if ($row['project_status'] != "Completed") { ?>
                                                            <button class="btn btn-sm btn-edit" data-bs-toggle="modal" data-bs-target="#editProjectModal<?php echo $row['project_id']; ?>" onclick="event.stopPropagation();">
                                                                <i class="bi bi-pencil-square" data-bs-toggle="tooltip" title="Edit Project"></i>
                                                            </button>
                                                        <?php } ?>
                                                    <?php } ?>

                                                    <?php if ($row['project_status'] != "Completed") { ?>
                                                        <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'finance'): ?>
                                                            <!-- Project Addons Button -->
                                                            <button class="btn btn-sm btn-addon" data-bs-toggle="modal" data-bs-target="#projectAddon<?php echo $row['project_id']; ?>" onclick="event.stopPropagation();">
                                                                <i class="bi bi-plus-square" data-bs-toggle="tooltip" title="Add Project Addon"></i>
                                                            </button>
                                                        <?php endif; ?>

                                                        <!-- Cancel Project Button -->
                                                        <!--<button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#cancelProjectModal<?php echo $row['project_id']; ?>" onclick="event.stopPropagation();">-->
                                                        <!--    <i class="bi bi-x-circle" data-bs-toggle="tooltip" title="Cancel Project"></i>-->
                                                        <!--</button>-->
                                                    <?php } ?>

                                                    <!-- Archive Button -->
                                                    <button class="btn btn-sm btn-archive" data-bs-toggle="modal" data-bs-target="#archiveProjectModal<?php echo $row['project_id']; ?>" onclick="event.stopPropagation();">
                                                        <i class="bi bi-archive" data-bs-toggle="tooltip" title="Archive Project"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Project Addon Modal Template -->
                                        <div class="modal fade project-addon-modal" id="projectAddon<?php echo $row['project_id']; ?>" tabindex="-1" aria-labelledby="projectAddonLabel<?php echo $row['project_id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered" style="max-width: 650px;">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="background-color: #20c997; color: white;">
                                                        <h5 class="modal-title" id="projectAddonLabel<?php echo $row['project_id']; ?>">Project Add-on</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
                                                    </div>
                                                    <div class="px-3">
                                                        <div class="text-muted mt-3 project-modal-header">
                                                            Kindly provide the project addon details below. All fields marked with an asterisk
                                                            <span style="color: red;">*</span> are required.
                                                        </div>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form action="../forms_logic/project_addon.php" method="POST" enctype="multipart/form-data">
                                                            <div class="">
                                                                <input type="hidden" name="client_id" value="<?php echo $_GET['client_id']; ?>">
                                                                <input type="hidden" name="parent_project_id" value="<?php echo $row['project_id']; ?>">
                                                            </div>

                                                            <div class="row mb-3">
                                                                <!-- Project Name -->
                                                                <div class="col-md-12">
                                                                    <label class="form-label">Project Add-on Name<span style="color: red;">*</span></label>
                                                                    <input type="text" class="form-control " name="addon_name" required>
                                                                </div>
                                                            </div>

                                                            <!-- Total Project Cost -->
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <label class="form-label">Project Cost<span style="color: red;">*</span></label>
                                                                    <div class="input-group">
                                                                        <span class="input-group-text fs-6 px-2 py-1">₱</span>
                                                                        <input type="number" class="budget-input form-control fs-5 text-muted py-1 addon-project-cost " name="projected_budget_cost" required placeholder="00.00" min="0" step="any">
                                                                    </div>
                                                                    <span class="budget-warning text-danger mt-1" style="display: none; font-size: 0.83rem;">Project budget cost cannot be less than ₱1,000.</span>
                                                                </div>
                                                                <!-- Project Area -->
                                                                <div class="col-md-6">
                                                                    <label class="form-label">Project Area<span style="color: red;">*</span></label>
                                                                    <input type="text" class="form-control py-2" name="project_type" required placeholder="Enter Project Area">
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <!-- Materials Cost -->
                                                                <div class="col-md-4">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text fs-6 px-2 py-1">₱</span>
                                                                        <input type="number" class="form-control fs-5 text-muted addon-materials-cost" name="materials_cost" required placeholder="00.00" style="box-shadow: none;" min="0" step="any">
                                                                    </div>
                                                                    <label class="form-label text-muted">Materials Cost</label>
                                                                </div>
                                                                <!-- Labor Cost -->
                                                                <div class="col-md-4">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text fs-6 px-2 py-1">₱</span>
                                                                        <input type="number" class="form-control fs-5 text-muted addon-labor-cost" name="labor_cost" required placeholder="00.00" style="box-shadow: none;" min="0" step="any">
                                                                    </div>
                                                                    <label class="form-label text-muted">Labor Cost</label>
                                                                </div>
                                                                <!-- Other Expenses Cost -->
                                                                <div class="col-md-4">
                                                                    <div class="input-group">
                                                                         <span class="input-group-text fs-6 px-2 py-1">₱</span>
                                                                        <input type="number" class="form-control fs-5 text-muted addon-other-expenses-cost" name="other_expenses_cost" required placeholder="00.00" style="box-shadow: none;" min="0" step="any">
                                                                    </div>
                                                                    <label class="form-label text-muted">Other Expenses Cost</label>
                                                                </div>
                                                            </div>
                                                            <!-- Allocated Budget -->
                                                            <p class="mb-2">Allocated Budget Cost<span style="color: red;">*</span></p>
                                                            <div class="row mb-4">
                                                                <div class="col-md-12">
                                                                    <div class="input-group">
                                                                         <span class="input-group-text fs-6 px-2 py-1">₱</span>
                                                                        <input type="number" class="form-control fs-5 text-muted addon-allocated-budget" name="allocated_budget" required placeholder="00.00" style="box-shadow: none;" min="0" step="any">
                                                                    </div>
                                                                    <div class="text-danger mt-3 addon-budget-warning" style="display: none; font-size: 0.875rem;"></div>
                                                                </div>
                                                            </div>
                                                            <!-- File Upload -->
                                                            <div class="row mb-4 mt-3">
                                                                <div class="col-md-12">
                                                                    <label class="form-label">Upload Contract Document</label>
                                                                    <input type="file" class="form-control" id="project_file_<?php echo $row['project_id']; ?>" name="project_file" accept="application/pdf,image/png">
                                                                    <small class="form-text text-muted">Optional: Upload a PDF or PNG file. Max size: 5MB.</small>
                                                                </div>
                                                            </div>

                                                            <!-- Modal for File Preview -->
                                                            <div class="modal fade" id="previewContract<?php echo $row['project_id']; ?>" tabindex="-1">
                                                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                                                    <div class="modal-content text-center">
                                                                        <div class="modal-body">
                                                                            <!-- Image preview -->
                                                                            <img id="previewImage_<?php echo $row['project_id']; ?>" class="img-fluid mb-2 d-none" alt="Preview">

                                                                            <!-- PDF preview -->
                                                                            <iframe id="previewPDF_<?php echo $row['project_id']; ?>" class="w-100 d-none" style="height: 500px;" frameborder="0"></iframe>

                                                                            <p id="previewFileName_<?php echo $row['project_id']; ?>" class="mt-2"></p>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" id="cancelFile_<?php echo $row['project_id']; ?>" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                                                            <button type="button" id="confirmFile_<?php echo $row['project_id']; ?>" class="btn btn save-btn" data-bs-dismiss="modal">Confirm</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <!-- Save and Cancel Button -->
                                                            <div class="row">
                                                                <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                                                                     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="save_project_addon" class="btn btn-success">Save Add-on</button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                         <!-- Archive Project Modal -->
                                        <div class="modal fade" id="archiveProjectModal<?php echo $row['project_id']; ?>" tabindex="-1" aria-labelledby="archiveProjectLabel<?php echo $row['project_id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-md">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="archiveProjectLabel<?php echo $row['project_id']; ?>">Confirm Archive</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body" style="line-height: 1.2; margin-bottom: 0; padding: 20px;">
                                                        <form method="POST" action="../forms_logic/archive.php">
                                                            <input type="hidden" name="project_id" value="<?php echo $row['project_id']; ?>">
                                                            <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                                                            <p>Are you sure you want to archive project <strong><?php echo $row['project_name']; ?></strong>?</p>

                                                             <div class="d-flex justify-content-end mt-4 ">
                                                                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="archive_project_btn" class="btn btn-danger">Archive</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <style>
                                            /* Hide any form-text directly after the textarea that isn't the #otherNote */
                                            textarea+.form-text:not(#otherNote<?php echo $row['project_id']; ?>) {
                                                display: none !important;
                                                font-size: 1px;
                                            }
                                        </style>

                                        <!-- Cancel Project Modal -->
                                        <div class="modal fade" id="cancelProjectModal<?php echo $row['project_id']; ?>" tabindex="-1" aria-labelledby="cancelProjectLabel<?php echo $row['project_id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-md">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="cancelProjectLabel<?php echo $row['project_id']; ?>">Cancel Project</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body" style="line-height: 1.2; margin-bottom: 0; padding: 20px;">
                                                        <form method="POST" action="../forms_logic/archive.php" id="cancelForm<?php echo $row['project_id']; ?>">
                                                            <input type="hidden" name="project_id" value="<?php echo $row['project_id']; ?>">
                                                            <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">

                                                            <p>Are you sure you want to cancel project <strong><?php echo $row['project_name']; ?></strong>?</p>

                                                            <!-- Cancellation Reason Selection -->
                                                            <div class="mb-3">
                                                                <label for="cancellation_reason<?php echo $row['project_id']; ?>" class="form-label">
                                                                    <strong>Reason for Cancellation <span class="text-danger">*</span></strong>
                                                                </label>
                                                                <select
                                                                    class="form-select"
                                                                    id="cancellation_reason<?php echo $row['project_id']; ?>"
                                                                    name="cancellation_reason"
                                                                    required
                                                                    onchange="toggleAdditionalDetails_<?php echo $row['project_id']; ?>(this.value)">
                                                                    <option value="">Select a reason...</option>
                                                                    <option value="Client Request">Client Request</option>
                                                                    <option value="Budget Constraints">Budget Constraints</option>
                                                                    <option value="Timeline Issues">Timeline Issues</option>
                                                                    <option value="Technical Difficulties">Technical Difficulties</option>
                                                                    <option value="Resource Unavailability">Resource Unavailability</option>
                                                                    <option value="Scope Changes">Scope Changes</option>
                                                                    <option value="Priority Shift">Priority Shift</option>
                                                                    <option value="External Dependencies">External Dependencies</option>
                                                                    <option value="Quality Concerns">Quality Concerns</option>
                                                                    <option value="Force Majeure">Force Majeure</option>
                                                                    <option value="Other">Other (Please specify)</option>
                                                                </select>
                                                            </div>

                                                            <!-- Additional Details -->
                                                            <div class="mb-3">
                                                                <label for="additional_details<?php echo $row['project_id']; ?>" class="form-label">
                                                                    <strong>Additional Details</strong>
                                                                    <span class="text-danger" id="requiredStar<?php echo $row['project_id']; ?>" style="display: none;">*</span>
                                                                </label>
                                                                <textarea
                                                                    class="form-control"
                                                                    id="additional_details<?php echo $row['project_id']; ?>"
                                                                    name="additional_details"
                                                                    rows="3"
                                                                    maxlength="500"
                                                                    placeholder="Please provide additional details about the cancellation..."></textarea>
                                                                <div class="form-text" id="otherNote<?php echo $row['project_id']; ?>" style="display: none; color: #0d6efd;">
                                                                    <i>Please specify the reason below (required)</i>
                                                                </div>
                                                                <div class="form-text" id="charCount<?php echo $row['project_id']; ?>">
                                                                    <small class="text-muted">0/500 characters</small>
                                                                </div>
                                                            </div>

                                                            <div class="modal-footer" style="padding-bottom: 0; margin-top: 25px;">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="cancel_project_btn" class="btn btn-danger">Cancel Project</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <style>
                                            /* Hide any form-text directly after the textarea that isn't the #otherNote */
                                            textarea+.form-text:not(#otherNote<?php echo $row['project_id']; ?>) {
                                                display: none !important;
                                                font-size: 1px;
                                            }
                                        </style>

                                        <script>
                                            document.addEventListener("DOMContentLoaded", function() {
                                                // Initialize character counter for this specific project
                                                const textarea_<?php echo $row['project_id']; ?> = document.getElementById('additional_details<?php echo $row['project_id']; ?>');
                                                const charCount_<?php echo $row['project_id']; ?> = document.getElementById('charCount<?php echo $row['project_id']; ?>');

                                                if (textarea_<?php echo $row['project_id']; ?> && charCount_<?php echo $row['project_id']; ?>) {
                                                    textarea_<?php echo $row['project_id']; ?>.addEventListener('input', function() {
                                                        const length = this.value.length;
                                                        const counter = charCount_<?php echo $row['project_id']; ?>.querySelector('small');
                                                        counter.textContent = length + '/500 characters';

                                                        if (length > 450) {
                                                            counter.classList.remove('text-muted');
                                                            counter.classList.add('text-warning');
                                                        } else {
                                                            counter.classList.remove('text-warning');
                                                            counter.classList.add('text-muted');
                                                        }
                                                    });
                                                }

                                                // Form validation for this specific project
                                                const cancelForm_<?php echo $row['project_id']; ?> = document.getElementById('cancelForm<?php echo $row['project_id']; ?>');
                                                if (cancelForm_<?php echo $row['project_id']; ?>) {
                                                    cancelForm_<?php echo $row['project_id']; ?>.addEventListener('submit', function(e) {
                                                        const reasonSelect = document.getElementById('cancellation_reason<?php echo $row['project_id']; ?>');
                                                        const additionalDetails = document.getElementById('additional_details<?php echo $row['project_id']; ?>');

                                                        // Check if reason is selected
                                                        if (!reasonSelect.value.trim()) {
                                                            e.preventDefault();
                                                            alert('Please select a reason for cancellation.');
                                                            reasonSelect.focus();
                                                            return false;
                                                        }

                                                        // Check if "Other" is selected and additional details are provided
                                                        if (reasonSelect.value === 'Other' && !additionalDetails.value.trim()) {
                                                            e.preventDefault();
                                                            alert('Please specify the reason for cancellation in the additional details field.');
                                                            additionalDetails.focus();
                                                            return false;
                                                        }

                                                        // Check character limit
                                                        if (additionalDetails.value.length > 500) {
                                                            e.preventDefault();
                                                            alert('Additional details must be 500 characters or less.');
                                                            additionalDetails.focus();
                                                            return false;
                                                        }

                                                        // Final confirmation
                                                        const projectName = '<?php echo addslashes($row['project_name']); ?>';
                                                        if (!confirm(`Are you sure you want to cancel the project "${projectName}"? This action cannot be undone.`)) {
                                                            e.preventDefault();
                                                            return false;
                                                        }

                                                        // Show loading state
                                                        const submitBtn = this.querySelector('button[type="submit"]');
                                                        if (submitBtn) {
                                                            submitBtn.disabled = true;
                                                            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Processing...';
                                                        }
                                                    });
                                                }

                                                // Existing logic for main project forms
                                                const projectForms = document.querySelectorAll('form[action="../forms_logic/project_logic.php"]');
                                                projectForms.forEach(function(form) {
                                                    form.addEventListener("submit", function(event) {
                                                        if (this.checkValidity()) {
                                                            const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'), {
                                                                keyboard: false,
                                                                backdrop: 'static'
                                                            });
                                                            loadingModal.show();
                                                            setTimeout(() => {
                                                                const openModals = document.querySelectorAll('.modal.show');
                                                                openModals.forEach(modal => {
                                                                    if (modal.id !== 'loadingModal') {
                                                                        const modalInstance = bootstrap.Modal.getInstance(modal);
                                                                        if (modalInstance) {
                                                                            modalInstance.hide();
                                                                        }
                                                                    }
                                                                });
                                                            }, 100);
                                                        }
                                                    });
                                                });

                                                // Logic for Project Add-on forms
                                                const addonForms = document.querySelectorAll('form[action="../forms_logic/project_addon.php"]');
                                                addonForms.forEach(function(form) {
                                                    form.addEventListener("submit", function(event) {
                                                        if (this.checkValidity()) {
                                                            const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'), {
                                                                keyboard: false,
                                                                backdrop: 'static'
                                                            });
                                                            loadingModal.show();
                                                            setTimeout(() => {
                                                                const openModals = document.querySelectorAll('.modal.show');
                                                                openModals.forEach(modal => {
                                                                    if (modal.id !== 'loadingModal') {
                                                                        const modalInstance = bootstrap.Modal.getInstance(modal);
                                                                        if (modalInstance) {
                                                                            modalInstance.hide();
                                                                        }
                                                                    }
                                                                });
                                                            }, 100);
                                                        }
                                                    });
                                                });

                                                // Hide loading modal on page show
                                                window.addEventListener('pageshow', function() {
                                                    const loadingModalElement = document.getElementById('loadingModal');
                                                    if (loadingModalElement) {
                                                        const loadingModalInstance = bootstrap.Modal.getInstance(loadingModalElement);
                                                        if (loadingModalInstance) {
                                                            loadingModalInstance.hide();
                                                        }
                                                    }
                                                });

                                                // Initialize tooltips
                                                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                                                tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                                                    new bootstrap.Tooltip(tooltipTriggerEl);
                                                });
                                            });

                                            // Toggle function for additional details requirement
                                            function toggleAdditionalDetails_<?php echo $row['project_id']; ?>(selectedValue) {
                                                const additionalDetails = document.getElementById('additional_details<?php echo $row['project_id']; ?>');
                                                const requiredStar = document.getElementById('requiredStar<?php echo $row['project_id']; ?>');
                                                const otherNote = document.getElementById('otherNote<?php echo $row['project_id']; ?>');

                                                if (selectedValue === 'Other') {
                                                    additionalDetails.required = true;
                                                    additionalDetails.setAttribute('required', 'required');
                                                    requiredStar.style.display = 'inline';
                                                    otherNote.style.display = 'block';
                                                    additionalDetails.placeholder = 'Please specify the reason for cancellation...';
                                                } else {
                                                    additionalDetails.required = false;
                                                    additionalDetails.removeAttribute('required');
                                                    requiredStar.style.display = 'none';
                                                    otherNote.style.display = 'none';
                                                    additionalDetails.placeholder = 'Please provide additional details about the cancellation...';
                                                }
                                            }
                                        </script>

                                        <!-- Delete Confirmation Modal -->
                                        <div class="modal fade" id="deleteProjectModal<?php echo $row['project_id']; ?>" tabindex="-1" aria-labelledby="deleteProjectLabel<?php echo $row['project_id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteProjectLabel<?php echo $row['project_id']; ?>">Confirm Deletion</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to delete project <strong><?php echo $row['project_name']; ?></strong>?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <a href="../forms_logic/delete_project.php?project_id=<?php echo $row['project_id']; ?>&client_id=<?php echo $client_id; ?>" class="btn btn-danger">Delete</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Include Project Edit Modal -->
                                        <?php include('../resources/modals/edit_project_modal.php'); ?>

                                        <!-- Mark Completed Confirmation Modal -->
                                        <div class="modal fade" id="confirmModal<?php echo $row['project_id']; ?>" tabindex="-1" aria-labelledby="confirmModalLabel<?php echo $row['project_id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="confirmModalLabel<?php echo $row['project_id']; ?>">Confirm Completion</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to mark this project as <strong>Completed</strong>?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <form action="../forms_logic/mark_completed.php" method="POST">
                                                            <input type="hidden" name="project_id" value="<?php echo $row['project_id']; ?>">
                                                            <button type="submit" class="btn btn-success">Yes, Complete</button>
                                                        </form>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    <?php
                                    } // end while loop
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="6">No Record Found</td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>


            </div>
        <?php endif; ?>

        <!-- Loading Modal -->
        <div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-sm">
                    <div class="modal-body text-center py-4">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h5 class="mt-3 mb-0">Creating Project...</h5>
                        <p class="text-muted">Please wait while we set up the project and notify the client via email.</p>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
  

    <script src="./resources/js/auto_logout.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Get all forms that should show loading modal
            const projectForms = document.querySelectorAll('form[action="../forms_logic/project_logic.php"]');

            projectForms.forEach(function(form) {
                form.addEventListener("submit", function(event) {
                    // Check if form is valid
                    if (this.checkValidity()) {
                        // Show loading modal immediately
                        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'), {
                            keyboard: false,
                            backdrop: 'static'
                        });
                        loadingModal.show();

                        // Hide any open modals after a brief delay
                        setTimeout(() => {
                            const openModals = document.querySelectorAll('.modal.show');
                            openModals.forEach(modal => {
                                if (modal.id !== 'loadingModal') {
                                    const modalInstance = bootstrap.Modal.getInstance(modal);
                                    if (modalInstance) {
                                        modalInstance.hide();
                                    }
                                }
                            });
                        }, 100);
                    }
                });
            });

            // Hide loading modal on page show (handles back button, redirects, etc.)
            window.addEventListener('pageshow', function() {
                const loadingModalElement = document.getElementById('loadingModal');
                if (loadingModalElement) {
                    const loadingModalInstance = bootstrap.Modal.getInstance(loadingModalElement);
                    if (loadingModalInstance) {
                        loadingModalInstance.hide();
                    }
                }
            });
        });
    </script>

    <script src="resources/js/session-protection.js"></script>
    <script src="../resources/js/projects.js"></script>

</body>

</html>