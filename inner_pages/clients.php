<?php
session_start();
include('../dbcon.php');
include('../layout/session_check.php');

// For breadcrumbs and active pages
$currentPage = 'clients';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Clients"; ?>
    <?php include('../layout/head.php'); ?>
    <link rel="stylesheet" href="../resources/css/client.css">
</head>

<body>
     <div class="d-flex">
        <!-- Side Nav Container -->
        <?php include('../layout/sidenav.php'); ?>

        <!-- Main Content -->
        <div class="main-content container-fluid ">

            <!-- Top Nav -->
            <?php
            $page_title = 'Clients';
            include '../layout/topnav.php';
            ?>

            <div class="nav-container-fluid">

                <!-- Breadcrumbs -->
                <div style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708'/%3E%3C/svg%3E&#34;);" aria-label="breadcrumb">
                    <ol class="breadcrumb   ">
                        <?php if ($currentPage !== 'clients'): ?>
                            <li class="breadcrumb-item">Clients</li>
                        <?php endif; ?>

                        <?php if ($currentPage === 'projects'): ?>
                            <li class="breadcrumb-item active" aria-current="page">Projects</li>
                        <?php elseif ($currentPage === 'project-details'): ?>
                            <li class="breadcrumb-item">Projects</li>
                            <li class="breadcrumb-item active" aria-current="page">Project Details</li>
                        <?php endif; ?>
                    </ol>
                </div>

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
                
          
                <!-- Added Client Notification & Success Modal -->
                <?php
                if (isset($_SESSION['status']) && $_SESSION['status'] != '') {
                ?>

                    <!-- Modal Notification -->
                    <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content bg-white border-0 shadow-sm">
                                <div class="modal-header border-0 ">
                                    <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
                                </div>
                                <div class="modal-body text-center" style="padding-top: 10px;">
                                    <i class="bi bi-check-circle-fill text-success" style="font-size: 70px; margin-bottom: 50px;"></i>

                                    <h4 class="mb-0" style="margin-top: -5px;">Added Client Successfully</h4>
                                    <p class="pt-3">
                                        The client has been successfully added to the system. <br> You can now manage their details in the client list.
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

                <div id="client-list">
                    <div class="">
                        <!-- Add Client Button -->
                        <?php
                        $addClientButton = '';
                        if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'finance') {
                            $addClientButton = '
                                <button type="button" class="btn add-custom-outline-btn btn-md px-3 py-2 ms-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addClientModal">
                                <i class="bi bi-plus-circle me-2"></i>
                                Add Client
                            </button>
                            ';
                        }
                        ?>

                        <!-- Add Client Modal -->
                        <div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
                            <div class="modal-dialog add-client-modal">
                                <div class="modal-content">
                                    <div class="modal-header d-flex align-items-center justify-content-between">
                                        <h5 class="modal-title" id="addClientModalLabel">Add New Client</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="px-3">
                                        <div class="text-muted mt-3 client-modal-header">
                                            Kindly provide the client’s information below. All fields marked with an
                                            asterisk <span style="color: red;">*</span> are required.
                                        </div>
                                    </div>
                                    <div class="modal-body">
                                        <div class="shadow-md" style="background-color: white; height: 100%;">
                                            <div class="body">
                                                <!-- Client Type Dropdown -->
                                                <div class="mb-3">
                                                    <label for="clientTypeSelect" class="form-label">Client Type <span style="color: red;">*</span></label>
                                                    <select class="form-select" id="clientTypeSelect" required>
                                                        <option value="" selected disabled>Select Client Type</option>
                                                        <option value="individual">Individual</option>
                                                        <option value="company">Company</option>
                                                    </select>
                                                </div>

                                                <!-- Individual Form -->
                                                <form id="individualForm" action="../forms_logic/code.php" method="POST" style="display: none;">
                                                    <input type="hidden" name="client_type" value="Individual">
                                                    <!-- Client Name -->
                                                    <p class="mb-2 mt-3">Client Name <span style="color: red;">*</span></p>
                                                    <div>
                                                        <input type="text"
                                                            class="form-control hover-effect"
                                                            name="client_name"
                                                            required
                                                            pattern="^[A-Za-z\s]+$"
                                                            title="Client name should only contain letters and spaces.">
                                                        <label class="text-muted form-label">Full Name</label>
                                                    </div>
                                                    <!-- Email and Phone -->
                                                    <div class="row">
                                                        <p class="mb-2 mt-3">Email and Phone Number <span style="color: red;">*</span></p>
                                                        <div class="col-md-6">
                                                            <input type="email"
                                                                class="form-control text-muted hover-effect"
                                                                name="email[]"
                                                                placeholder="ex: email@yahoo.com"
                                                                pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.com$"
                                                                required>
                                                            <div class="invalid-feedback">
                                                                Please enter a valid email address.
                                                            </div>
                                                            <label class="text-muted form-label">Email</label>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <input type="tel" name="phone_number[]" class="form-control text-muted hover-effect"
                                                                placeholder="e.g. 09XXXXXXXXX"
                                                                pattern="^09\d{9}$"
                                                                maxlength="11"
                                                                required>
                                                            <div class="invalid-feedback">
                                                                Please enter a valid phone number.
                                                            </div>
                                                            <label class="text-muted form-label">Phone Number</label>
                                                        </div>
                                                    </div>
                                                    <!-- Address Section -->
                                                    <p class="mb-2 mt-3">Address <span style="color: red;">*</span></p>
                                                    <div class="row g-3 mb-3">
                                                        <div class="col-md-4">
                                                            <select class="form-control select2 hover-effect" id="province" name="province" required>
                                                                <option value="" disabled selected hidden>Select Province</option>
                                                            </select>
                                                            <label class="text-muted form-label">Province</label>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <select class="form-control hover-effect" id="city" name="city" required>
                                                                <option disabled selected hidden>Select City/Municipality</option>
                                                            </select>
                                                            <label class="text-muted form-label">City/Municipality</label>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <select class="form-control hover-effect" id="barangay" name="barangay" required>
                                                                <option disabled selected hidden>Select Barangay</option>
                                                            </select>
                                                            <label class="text-muted form-label">Barangay</label>
                                                        </div>
                                                    </div>
                                                    <div class="row g-3 mb-3">
                                                        <div class="col-md-10">
                                                            <input type="text" class="form-control hover-effect" name="street_address" required>
                                                            <label class="text-muted form-label">Street Address / House / Building No.</label>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <input type="text" class="form-control hover-effect" name="zip_code" required>
                                                            <label class="text-muted form-label">ZIP Code</label>
                                                        </div>
                                                    </div>
                                                     <div class="mt-3">
                                                        <div class="d-flex justify-content-end">
                                                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="save_data" class="btn btn-success ms-2">Save Client</button>
                                                        </div>
                                                    </div>
                                                </form>

                                                <!-- Company Form -->
                                                <form id="companyForm" action="../forms_logic/code.php" method="POST" style="display: none;">
                                                    <input type="hidden" name="client_type" value="Company">
                                                    <!-- Company Name -->
                                                    <p class="mb-2 mt-3">Company Name <span style="color: red;">*</span></p>
                                                    <div class="">
                                                        <input type="text" class="form-control hover-effect" name="client_name" required>
                                                        <label class="text-muted form-label">Company Name</label>
                                                    </div>
                                                    <!-- Contact Persons Section -->
                                                    <div>
                                                        <div class="d-flex align-items-center gap-1 mb-2 mt-3">
                                                            <p class="mb-0">Contact Person 1</p>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor"
                                                                class="bi bi-info-circle text-primary" viewBox="0 0 16 16"
                                                                data-bs-toggle="popover"
                                                                data-bs-trigger="hover"
                                                                title="Important Note"
                                                                data-bs-html="true"
                                                                data-bs-content="The <strong>first contact person</strong> will get an email with account credentials for system login.">
                                                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                                                                <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0" />
                                                            </svg>
                                                        </div>
                                                        <div class="row g-3 mb-3">
                                                            <div class="col-md-4">
                                                                <input type="text" class="form-control hover-effect name-capitalize" name="first_name[]" required>
                                                                <label class="text-muted form-label">First Name<span style="color: red;">*</span></label>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <input type="text" class="form-control hover-effect" name="middle_name[]" maxlength="2" style="text-transform: uppercase;">
                                                                <label class="text-muted form-label">M.I</label>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <input type="text" class="form-control hover-effect name-capitalize" name="last_name[]" required>
                                                                <label class="text-muted form-label">Last Name<span style="color: red;">*</span></label>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <input type="text" class="form-control hover-effect" name="suffix_name[]">
                                                                <label class="text-muted form-label">Suffix</label>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <input type="email"
                                                                    class="form-control text-muted hover-effect"
                                                                    name="email[]"
                                                                    placeholder="ex: email@yahoo.com"
                                                                    pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[cC][oO][mM]$"
                                                                    required>
                                                                <div class="invalid-feedback">
                                                                    Please enter a valid email address.
                                                                </div>
                                                                <label class="text-muted form-label">Email <span style="color: red;">*</span></label>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <input type="tel" name="phone_number[]" class="form-control text-muted hover-effect"
                                                                    placeholder="e.g. 09XXXXXXXXX"
                                                                    pattern="^09\d{9}$"
                                                                    maxlength="11"
                                                                    required>
                                                                <div class="invalid-feedback">
                                                                    Please enter a valid phone number.
                                                                </div>
                                                                <label class="text-muted form-label">Phone Number <span style="color: red;">*</span></label>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <select class="form-control hover-effect" name="designation[]" required>
                                                                    <option value="" disabled selected hidden>Select Designation</option>
                                                                    <option value="Engineering">Engineering</option>
                                                                    <option value="Purchasing">Purchasing</option>
                                                                    <option value="Accounting">Accounting</option>
                                                                </select>
                                                                <label class="text-muted form-label">Designation<span style="color: red;">*</span></label>
                                                            </div>
                                                        </div>
                                                        <p class="mb-2 mt-3">Contact Person 2 <span class="text-muted ps-1">(Optional)</span></p>
                                                        <div class="row g-3 mb-3">
                                                            <div class="col-md-4">
                                                                <input type="text" class="form-control hover-effect name-capitalize" name="first_name[]" >
                                                                <label class="text-muted form-label">First Name</label>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <input type="text" class="form-control hover-effect" name="middle_name[]" maxlength="2" style="text-transform: uppercase;">
                                                                <label class="text-muted form-label">M.I</label>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <input type="text" class="form-control hover-effect name-capitalize" name="last_name[]" >
                                                                <label class="text-muted form-label">Last Name</label>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <input type="text" class="form-control hover-effect" name="suffix_name[]">
                                                                <label class="text-muted form-label">Suffix</label>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <input type="email"
                                                                    class="form-control text-muted hover-effect"
                                                                    name="email[]"
                                                                    placeholder="ex: email@yahoo.com"
                                                                    pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[cC][oO][mM]$"
                                                                    >
                                                                <div class="invalid-feedback">
                                                                    Please enter a valid email address.
                                                                </div>
                                                                <label class="text-muted form-label">Email </label>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <input type="tel" name="phone_number[]" class="form-control text-muted hover-effect"
                                                                    placeholder="e.g. 09XXXXXXXXX"
                                                                    pattern="^09\d{9}$"
                                                                    maxlength="11"
                                                                    >
                                                                <div class="invalid-feedback">
                                                                    Please enter a valid phone number.
                                                                </div>
                                                                <label class="text-muted form-label">Phone Number </label>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <select class="form-control hover-effect" name="designation[]" >
                                                                    <option value="" disabled selected hidden>Select Designation</option>
                                                                    <option value="Engineering">Engineering</option>
                                                                    <option value="Purchasing">Purchasing</option>
                                                                    <option value="Accounting">Accounting</option>
                                                                </select>
                                                                <label class="text-muted form-label">Designation</label>
                                                            </div>
                                                        </div>
                                                        <p class="mb-2 mt-3">Contact Person 3 <span class="text-muted ps-1">(Optional)</span></p>
                                                        <div class="row g-3 mb-3">
                                                            <div class="col-md-4">
                                                                <input type="text" class="form-control hover-effect name-capitalize" name="first_name[]" >
                                                                <label class="text-muted form-label">First Name</label>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <input type="text" class="form-control hover-effect" name="middle_name[]" maxlength="2" style="text-transform: uppercase;">
                                                                <label class="text-muted form-label">M.I</label>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <input type="text" class="form-control hover-effect name-capitalize" name="last_name[]" >
                                                                <label class="text-muted form-label">Last Name</label>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <input type="text" class="form-control hover-effect" name="suffix_name[]">
                                                                <label class="text-muted form-label">Suffix</label>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <input type="email"
                                                                    class="form-control text-muted hover-effect"
                                                                    name="email[]"
                                                                    placeholder="ex: email@yahoo.com"
                                                                    pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[cC][oO][mM]$"
                                                                    >
                                                                <div class="invalid-feedback">
                                                                    Please enter a valid email address.
                                                                </div>
                                                                <label class="text-muted form-label">Email </label>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <input type="tel" name="phone_number[]" class="form-control text-muted hover-effect"
                                                                    placeholder="e.g. 09XXXXXXXXX"
                                                                    pattern="^09\d{9}$"
                                                                    maxlength="11"
                                                                        >
                                                                <div class="invalid-feedback">
                                                                    Please enter a valid phone number.
                                                                </div>
                                                                <label class="text-muted form-label">Phone Number </label>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <select class="form-control hover-effect" name="designation[]" >
                                                                    <option value="" disabled selected hidden>Select Designation</option>
                                                                    <option value="Engineering">Engineering</option>
                                                                    <option value="Purchasing">Purchasing</option>
                                                                    <option value="Accounting">Accounting</option>
                                                                </select>
                                                                <label class="text-muted form-label">Designation</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Address Section -->
                                                    <p class="mb-2 mt-3">Address <span style="color: red;">*</span></p>
                                                    <div class="row g-3 mb-3">
                                                        <div class="col-md-4">
                                                            <select class="form-control select2 hover-effect" id="province_company" name="province" required>
                                                                <option value="" disabled selected hidden>Select Province</option>
                                                            </select>
                                                            <label class="text-muted form-label">Province</label>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <select class="form-control hover-effect" id="city_company" name="city" required>
                                                                <option disabled selected hidden>Select City/Municipality</option>
                                                            </select>
                                                            <label class="text-muted form-label">City/Municipality</label>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <select class="form-control hover-effect" id="barangay_company" name="barangay" required>
                                                                <option disabled selected hidden>Select Barangay</option>
                                                            </select>
                                                            <label class="text-muted form-label">Barangay</label>
                                                        </div>
                                                    </div>
                                                    <div class="row g-3 mb-3">
                                                        <div class="col-md-10">
                                                            <input type="text" class="form-control hover-effect" name="street_address" required>
                                                            <label class="text-muted form-label">Street Address / House / Building No.</label>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <input type="text" class="form-control hover-effect" name="zip_code" required>
                                                            <label class="text-muted form-label">ZIP Code</label>
                                                        </div>
                                                    </div>
                                                    <div class="mt-3">
                                                        <div class="d-flex justify-content-end">
                                                             <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="save_data" class="btn btn-success ms-2">Save Client</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Initialize Bootstrap Popover -->
                        <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
                                popoverTriggerList.map(function(popoverTriggerEl) {
                                    return new bootstrap.Popover(popoverTriggerEl);
                                });
                            });

                            document.addEventListener("DOMContentLoaded", function() {
                                const projectLinks = document.querySelectorAll(".project-select");
                                const selectedBtn = document.getElementById("selectedProjectBtn");
                            });
                        </script>

                        <!-- Loading Modal -->
                        <div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow-sm">
                                    <div class="modal-body text-center py-4">
                                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <h5 class="mt-3 mb-0">Adding Client...</h5>
                                        <p class="text-muted">
                                            Please wait while we create the account and send the credentials to the provided email address.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Auto open ADD CLIENT MODAL from EXPENSE FORM -->
                        <?php if (isset($_GET['open_add_client']) && $_GET['open_add_client'] == 1): ?>
                            <script>
                                window.addEventListener('load', function() {
                                    var addCategoryModal = new bootstrap.Modal(document.getElementById('addClientModal'), {
                                        keyboard: false
                                    });
                                    addCategoryModal.show();
                                });
                            </script>
                        <?php endif; ?>

                        <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                const clientTypeSelect = document.getElementById("clientTypeSelect");
                                const individualForm = document.getElementById("individualForm");
                                const companyForm = document.getElementById("companyForm");

                                clientTypeSelect.addEventListener("change", function() {
                                    if (this.value === "individual") {
                                        individualForm.style.display = "block";
                                        companyForm.style.display = "none";
                                    } else if (this.value === "company") {
                                        individualForm.style.display = "none";
                                        companyForm.style.display = "block";
                                    } else {
                                        individualForm.style.display = "none";
                                        companyForm.style.display = "none";
                                    }
                                });

                                // Remove required from all hidden fields before validation
                                document.querySelectorAll("form").forEach(form => {
                                    form.addEventListener("submit", function(event) {
                                        form.querySelectorAll("input, select, textarea").forEach(field => {
                                            if (field.offsetParent === null) {
                                                field.removeAttribute("required");
                                            }
                                        });
                                    });
                                });
                            });
                        </script>

                        <!-- Client already exist warning -->
                        <?php
                        if (isset($_SESSION['warning_status']) && $_SESSION['warning_status'] != '') {
                        ?>
                        <?php
                            unset($_SESSION['warning_status']);
                        }
                        ?>

                        <?php
                        // Database connection
                        $connection = mysqli_connect("localhost", "root", "", "financial_management");

                        // Query to fetch clients
                        $fetch_clients_query = "SELECT * FROM clients";
                        $fetch_clients_query_run = mysqli_query($connection, $fetch_clients_query);

                        // Check if there are clients
                        if (mysqli_num_rows($fetch_clients_query_run) > 0) {
                        ?>
                            <div class="col-lg-12">
                                <div class="shadow-md">
                                    <div class="table-wrapper">
                                        <div class="table-responsive">
                                            <!-- Data Tables JS -->
                                            <script>
                                                $(document).ready(function() {
                                                    $('#clientsTable').DataTable({
                                                        responsive: true,
                                                        pageLength: 10,
                                                        language: {
                                                            search: '',
                                                            searchPlaceholder: "Search clients...",
                                                            paginate: {
                                                                previous: '<i class="bi bi-chevron-bar-left"></i>', // icon only
                                                                next: '<i class="bi bi-chevron-bar-right"></i>' // icon only
                                                            }
                                                        },
                                                        initComplete: function() {
                                                            // Wrap the search input
                                                            let $filter = $('div.dataTables_filter');
                                                            let $input = $filter.find('input');
                                                            $input.wrap('<div class="input-group"></div>');
                                                            $input
                                                                .addClass('form-control');

                                                            // Append the Add Client button (from PHP variable)
                                                            <?php if ($addClientButton): ?>
                                                                $filter.append(`<?php echo $addClientButton; ?>`);
                                                            <?php endif; ?>
                                                        }
                                                    });
                                                });
                                            </script>


                                            <table class="table table-hover table-bordered " id="clientsTable">
                                                <thead>
                                                    <tr>
                                                        <th scope="col" style="text-transform: none;">Client</th>
                                                        <!-- <th scope="col" style="text-transform: none;">Address</th> -->
                                                        <th scope="col" class="text-center" style="text-transform: none;">Projects</th>
                                                        <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'finance'): ?>
                                                            <th scope="col" class="text-center" style="text-transform: none;">Actions</th>
                                                        <?php endif; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php
                                            } // Close the if block
                                                ?>
                                                <?php
                                                // Reset result pointer in case you looped earlier
                                                mysqli_data_seek($fetch_clients_query_run, 0);

                                                if (mysqli_num_rows($fetch_clients_query_run) > 0) {
                                                    while ($client_row = mysqli_fetch_array($fetch_clients_query_run)) {
                                                        $client_id = $client_row['client_id'];
                                                        $client_name = $client_row['client_name'];

                                                        // Fetch projects for the client
                                                        $fetch_projects_query = "SELECT * FROM projects WHERE client_id = '$client_id'";
                                                        $fetch_projects_query_run = mysqli_query($connection, $fetch_projects_query);

                                                        $project_names = [];
                                                        if (mysqli_num_rows($fetch_projects_query_run) > 0) {
                                                            while ($project_row = mysqli_fetch_array($fetch_projects_query_run)) {
                                                                $project_names[] = $project_row['project_name'];
                                                            }
                                                        } else {
                                                            $project_names[] = "No Project Assigned";
                                                        }

                                                        // Fetch contacts for the client
                                                        $fetch_contacts_query = "SELECT * FROM contacts WHERE client_id = '$client_id'";
                                                        $fetch_contacts_query_run = mysqli_query($connection, $fetch_contacts_query);

                                                        $contact_details = [];
                                                        if (mysqli_num_rows($fetch_contacts_query_run) > 0) {
                                                            while ($contact_row = mysqli_fetch_array($fetch_contacts_query_run)) {
                                                                // Add middle initial if available
                                                                $middle_initial = !empty($contact_row['middle_name']) ? strtoupper(substr($contact_row['middle_name'], 0, 1)) . '.' : '';

                                                                $contact_details[] = [
                                                                    'name' => $contact_row['first_name'] . ' ' . $middle_initial . ' ' . $contact_row['last_name'],
                                                                    'email' => $contact_row['email'],
                                                                    'phone' => $contact_row['phone_number'],
                                                                    'designation' => $contact_row['designation']
                                                                ];
                                                            }
                                                        } else {
                                                            $contact_details[] = "No Contacts Found";
                                                        }

                                                ?>
                                                        <tr class="clickable-row" onclick="window.location.href='projects.php?client_id=<?php echo $client_id; ?>'">
                                                            <td class="client-name-cell">
                                                                <div>
                                                                    <strong><?php echo htmlspecialchars($client_name); ?></strong>
                                                                    <br>
                                                                    <small class="text-muted">
                                                                        <?php echo $client_row['city'] . ', ' . $client_row['province']; ?>
                                                                    </small>
                                                                    <br>
                                                                    <small class="click-hint">
                                                                        <i class="bi bi-cursor-fill"></i> Click row to view projects
                                                                    </small>
                                                                </div>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="projects-count <?php echo mysqli_num_rows($fetch_projects_query_run) == 0 ? 'zero' : ''; ?>">
                                                                    <?php echo mysqli_num_rows($fetch_projects_query_run) > 0 ? mysqli_num_rows($fetch_projects_query_run) : '0'; ?>
                                                                </span>
                                                            </td>
                                                            <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'finance'): ?>
                                                            <td class="text-center action-buttons">
                                                                <div class="d-flex justify-content-center gap-2">
                                                                    <button class="btn btn-view btn-sm" onclick="event.stopPropagation();" data-bs-toggle="modal" data-bs-target="#viewClientModal<?php echo $client_id; ?>">
                                                                        <i class="bi bi-eye"></i> View
                                                                    </button>
                                                                    <button class="btn btn-edit btn-sm" onclick="event.stopPropagation();" data-bs-toggle="modal" data-bs-target="#editClientModal<?php echo $client_id; ?>">
                                                                        <i class="bi bi-pencil"></i> Edit
                                                                    </button>
                                                                </div>
                                                            </td>
                                                            <?php endif; ?>
                                                        </tr>


                                                        <!-- Individual and Client Edit Modal -->
                                                        <?php if ($client_row['client_type'] === 'Individual'): ?>
                                                            <?php include('../resources/modals/individual_edit.php'); ?>
                                                        <?php elseif ($client_row['client_type'] === 'Company'): ?>
                                                            <?php include('../resources/modals/edit_modal.php'); ?>
                                                        <?php endif; ?>


                                                        <!-- Include Delete Modal -->
                                                        <?php include('../resources/modals/delete_modal.php'); ?>

                                                        <!-- Include View Details Modal -->
                                                        <?php include('../resources/modals/client_details.php'); ?>

                                                    <?php
                                                    }
                                                } else { ?>
                                                    <tr>
                                                        <!-- First Client Welcome Screen -->
                                                        <div class="welcome-screen container d-flex justify-content-center">
                                                            <div class="text-center">
                                                                <img src="../resources/svg/undraw_interview_yz52 (1).svg" alt="Add Client"
                                                                    class="client-empty-svg">

                                                                <div class="add-first-client">Add Your Very First Client</div>
                                                                <div class="mx-auto">
                                                                    <p class="text-muted mb-3">
                                                                        Get started by creating your first client record in the system.
                                                                    </p>
                                                                </div>

                                                                <!-- First Add Client Button -->
                                                                <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'finance'): ?>
                                                                    <div class="d-flex justify-content-center mt-4 add-first-client-btn">
                                                                        <button type="button" class="btn px-4 py-2 shadow-sm"
                                                                            data-bs-toggle="modal" data-bs-target="#addClientModal"
                                                                            style="background-color: #183b4e; color: white; border: none;">
                                                                            <i class="bi bi-person-plus me-2"></i>
                                                                            Add Client
                                                                        </button>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </tr>
                                                <?php
                                                } ?>
                                                </tbody>
                                            </table>
                                        </div>

                                    </div>
                                </div>
                            </div>

                    </div>

                    <?php
                    if (isset($_SESSION['delete_status']) && $_SESSION['delete_status'] != '') {
                    ?>

                        <!-- Deleted Client Modal Notification -->
                        <div class="modal fade" id="delete_modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="delete_modalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-dialog-custom"> <!-- Added custom class -->
                                <div class="modal-content bg-white border-0 shadow-sm">
                                    <div class="modal-header border-0">
                                    </div>
                                    <div class="modal-body text-center" style="padding-top: 10px;">
                                        <i class="bi bi-check-circle-fill text-success" style="font-size: 70px; margin-bottom: 30px;"></i>

                                        <h4 class="mb-0" style="margin-top: -5px;">Client Deleted Successfully</h4>
                                        <p class="pt-3">
                                            The client has been successfully removed from the system. <br>
                                            You can continue managing other clients in the client list.
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
                                var successModal = new bootstrap.Modal(document.getElementById('delete_modal'));
                                successModal.show();
                            });
                        </script>
                    <?php
                        unset($_SESSION['delete_status']);
                    }
                    ?>
                </div>
            </div>
        </div>
    </div> <!-- Close row div -->

    <!-- For AUTO log out -->
    <script src="../resources/js/auto_logout.js"></script>

    <script>
            // Show loading modal on form submission
        document.addEventListener("DOMContentLoaded", function() {
            // Get the forms
            const individualForm = document.getElementById("individualForm");
            const companyForm = document.getElementById("companyForm");
            // Function to show loading modal
            function showLoadingModal() {
                var loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'), {
                    keyboard: false
                });
                loadingModal.show();
                // Close the Add Client Modal first
                var addClientModal = bootstrap.Modal.getInstance(document.getElementById('addClientModal'));
                if (addClientModal) {
                    addClientModal.hide();
                }
            }
            // Function to hide loading modal
            function hideLoadingModal() {
                var loadingModal = bootstrap.Modal.getInstance(document.getElementById('loadingModal'));
                if (loadingModal) {
                    loadingModal.hide();
                }
            }
            // Add submit event listener to individual form
            if (individualForm) {
                individualForm.addEventListener("submit", function(event) {
                    if (this.checkValidity()) {
                        showLoadingModal(); // Show loading modal on valid form submission
                    }
                });
            }
            // Add submit event listener to company form
            if (companyForm) {
                companyForm.addEventListener("submit", function(event) {
                    if (this.checkValidity()) {
                        showLoadingModal(); // Show loading modal on valid form submission
                    }
                });
            }
            // Optional: Simulate hiding the modal after submission (since page reloads)
            // Note: Since your form submission reloads the page, the modal will automatically disappear.
            // If using AJAX, you would call hideLoadingModal() in the success/error callback.
        });
        <?php if (isset($_GET['showAddModal']) && $_GET['showAddModal'] == 1): ?>

            document.addEventListener('DOMContentLoaded', function() {
                var addModal = new bootstrap.Modal(document.getElementById('addClientModal'));
                addModal.show();
            });
        <?php endif; ?>

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

        // Initialize Select2 for the province, city, and barangay dropdowns
        document.addEventListener("DOMContentLoaded", function() {
            const provinceSelect = document.getElementById("province");
            const citySelect = document.getElementById("city");
            const barangaySelect = document.getElementById("barangay");

            // Helper function to clear select options with custom placeholder
            function clearSelect(selectElement, placeholderText) {
                selectElement.innerHTML = `<option value="" disabled selected>${placeholderText}</option>`;
            }

            // Load Provinces on page load
            fetch("https://psgc.cloud/api/provinces")
                .then(res => res.json())
                .then(data => {
                    data.sort((a, b) => a.name.localeCompare(b.name));

                    clearSelect(provinceSelect, "-- Select Province --"); // Add default option first

                    data.forEach(province => {
                        const opt = document.createElement("option");
                        opt.value = province.name;
                        opt.textContent = province.name;
                        provinceSelect.appendChild(opt);
                    });
                });

            // Load Cities/Municipalities when Province is selected
            provinceSelect.addEventListener("change", function() {
                const provinceCode = this.value;

                clearSelect(citySelect, "-- Select City --");
                clearSelect(barangaySelect, "-- Select Barangay --");

                if (provinceCode) {
                    fetch(`https://psgc.cloud/api/provinces/${provinceCode}/cities-municipalities`) // Fixed variable name
                        .then(res => res.json())
                        .then(data => {
                            data.sort((a, b) => a.name.localeCompare(b.name));

                            data.forEach(city => {
                                const opt = document.createElement("option");
                                opt.value = city.name;
                                opt.textContent = city.name;
                                citySelect.appendChild(opt);
                            });
                        });
                }
            });

            // Load Barangays when City/Municipality is selected
            citySelect.addEventListener("change", function() {
                const cityCode = this.value;

                clearSelect(barangaySelect, "-- Select Barangay --");

                if (cityCode) {
                    fetch(`https://psgc.cloud/api/cities-municipalities/${cityCode}/barangays`)
                        .then(res => res.json())
                        .then(data => {
                            data.sort((a, b) => a.name.localeCompare(b.name));

                            data.forEach(brgy => {
                                const opt = document.createElement("option");
                                opt.value = brgy.name;
                                opt.textContent = brgy.name;
                                barangaySelect.appendChild(opt);
                            });
                        });
                }
            });
        });

        // JavaScript to handle the name capitalization
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('name-capitalize')) {
                let val = e.target.value.toLowerCase();
                // Capitalize first letter of each word
                e.target.value = val.replace(/\b\w/g, function(char) {
                    return char.toUpperCase();
                });
            }
        });


        // JavaScript to handle form validation
        (() => {
            'use strict';
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();

        // JavaScript to handle the custom email validation
        document.querySelector("form").addEventListener("submit", function(event) {
            const emailInput = document.getElementById("email");
            const emailValue = emailInput.value;

            // Regex to check if the email ends with .com
            const regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[cC][oO][mM]$/;

            if (!regex.test(emailValue)) {
                emailInput.setCustomValidity("Please enter a valid email address");
                emailInput.style.fontSize = "0.8em"; // Adjust the font size to make it smaller
                emailInput.reportValidity();
                event.preventDefault(); // Prevent form submission
            } else {
                emailInput.setCustomValidity(""); // Clear any custom validity if input is valid
            }
        });

        // Company Form Address Dropdowns
        document.addEventListener("DOMContentLoaded", function() {
            const provinceSelect = document.getElementById("province_company");
            const citySelect = document.getElementById("city_company");
            const barangaySelect = document.getElementById("barangay_company");

            function clearSelect(selectElement, placeholderText) {
                selectElement.innerHTML = `<option value="" disabled selected>${placeholderText}</option>`;
            }

            // Load Provinces on page load
            fetch("https://psgc.cloud/api/provinces")
                .then(res => res.json())
                .then(data => {
                    data.sort((a, b) => a.name.localeCompare(b.name));
                    clearSelect(provinceSelect, "-- Select Province --");
                    data.forEach(province => {
                        const opt = document.createElement("option");
                        opt.value = province.name;
                        opt.textContent = province.name;
                        provinceSelect.appendChild(opt);
                    });
                });

            // Load Cities/Municipalities when Province is selected
            provinceSelect.addEventListener("change", function() {
                const provinceCode = this.value;
                clearSelect(citySelect, "-- Select City --");
                clearSelect(barangaySelect, "-- Select Barangay --");
                if (provinceCode) {
                    fetch(`https://psgc.cloud/api/provinces/${provinceCode}/cities-municipalities`)
                        .then(res => res.json())
                        .then(data => {
                            data.sort((a, b) => a.name.localeCompare(b.name));
                            data.forEach(city => {
                                const opt = document.createElement("option");
                                opt.value = city.name;
                                opt.textContent = city.name;
                                citySelect.appendChild(opt);
                            });
                        });
                }
            });

            // Load Barangays when City/Municipality is selected
            citySelect.addEventListener("change", function() {
                const cityCode = this.value;
                clearSelect(barangaySelect, "-- Select Barangay --");
                if (cityCode) {
                    fetch(`https://psgc.cloud/api/cities-municipalities/${cityCode}/barangays`)
                        .then(res => res.json())
                        .then(data => {
                            data.sort((a, b) => a.name.localeCompare(b.name));
                            data.forEach(brgy => {
                                const opt = document.createElement("option");
                                opt.value = brgy.name;
                                opt.textContent = brgy.name;
                                barangaySelect.appendChild(opt);
                            });
                        });
                }
            });
        });
    </script>

</body>

</html>