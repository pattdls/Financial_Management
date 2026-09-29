<?php
session_start();
include('../dbcon.php');

// Redirect if not logged in
if (!isset($_SESSION['auth_user'])) {
    header('Location: ../login_form.php');
    exit;
}

// Auto log out if account deleted/disabled/password changed
if (isset($_SESSION['auth_user'])) {
    $user_id = $_SESSION['auth_user']['id'];
    $stmt = $conn->prepare("SELECT password, verify_status FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || $user['verify_status'] === 'disabled') {
        header("Location: /forms_logic/logout.php");
        exit();
    }
} else {
    header("Location: /login_form.php");
    exit();
}

// Auto logout after 5 minutes inactivity
$inactive = 300; // 5 minutes
if (isset($_SESSION['last_activity'])) {
    $session_life = time() - $_SESSION['last_activity'];
    if ($session_life > $inactive) {
        header("Location: /forms_logic/logout.php");
        exit();
    }
}
$_SESSION['last_activity'] = time();

// Only proceed if user is logged in
if (
    !isset($_SESSION['authenticated']) ||
    $_SESSION['auth_user']['role'] !== 'user'
) {
    header("Location: ../login_form.php");
    exit();
}

$user_id = $_SESSION['auth_user']['id'] ?? null;
$client_id = $_SESSION['auth_user']['client_id'];

// Fetch projects pending approval (not yet approved by both)
$query = "SELECT * FROM projects WHERE client_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();

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

// for auto open modal
$open_project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
$modal_type = isset($_GET['modal_type']) ? $_GET['modal_type'] : 'view';
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <!-- Bootsrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <!-- System Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet"> -->
    <link href="https://fonts.cdnfonts.com/css/avenir" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="../resources/css/main.css">
    <link rel="stylesheet" href="../resources/css/user_dash.css">

    <!-- Jquery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    <!-- ChartJs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

</head>

<body class="d-flex">

    <div class="main-content-dash container-fluid p-0">
    
   <!-- Navigation Bar -->
<nav class="custom-nav px-5 py-3">
    <div class="container-fluid">
        <div class="row align-items-center w-100 gx-3">
            <!-- Left Side: Logo and Company Name -->
            <div class="col-auto">
                <div class="d-flex align-items-center">
                    <img src="../resources/images/login_page_logo.png" alt="RVR Logo" class="logo-img me-3">
                    <div>
                        <div class="rvr_smes">
                            RVR SMES
                            <small class="text-muted welcome-text d-block d-lg-inline">
                                Dashboard | Welcome, <?= htmlspecialchars($_SESSION['auth_user']['name']) ?>!
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Center: ScrollSpy Navigation Buttons -->
            <div class="col">
                <div class="d-flex gap-2 align-items-center justify-content-end flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3 scrollspy-btn"
                            onclick="scrollToSection('pendingSection')" data-section="pending" aria-label="Go to pending section">
                        <i class="bi bi-clock-history me-1" aria-hidden="true"></i>
                        <span class="btn-text">Pending</span>
                        <span class="badge bg-warning text-dark ms-1" id="pendingCount">0</span>
                    </button>

                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 scrollspy-btn"
                            onclick="scrollToSection('notStartedSection')" data-section="notstarted" aria-label="Go to not yet started section">
                        <i class="bi bi-hourglass me-1" aria-hidden="true"></i>
                        <span class="btn-text">Not Yet Started</span>
                        <span class="badge bg-secondary ms-1" id="notStartedCount">0</span>
                    </button>

                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 scrollspy-btn"
                            onclick="scrollToSection('ongoingSection')" data-section="ongoing" aria-label="Go to ongoing section">
                        <i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>
                        <span class="btn-text">Ongoing</span>
                        <span class="badge bg-success ms-1" id="ongoingCount">0</span>
                    </button>

                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 scrollspy-btn"
                            onclick="scrollToSection('completedSection')" data-section="completed" aria-label="Go to completed section">
                        <i class="bi bi-check-circle me-1" aria-hidden="true"></i>
                        <span class="btn-text">Completed</span>
                        <span class="badge bg-primary ms-1" id="completedCount">0</span>
                    </button>
                </div>
            </div>

            <!-- Right Side: Notifications / User (desktop only) -->
            <div class="col-auto d-none d-md-flex align-items-center">
                <p id="datetime" class="me-3 mb-0" aria-hidden="true"></p>

                <?php
                // safer casting of user id
                $user_id = (int)($_SESSION['auth_user']['id'] ?? 0);
                $client_query = "SELECT client_id FROM users WHERE id = $user_id LIMIT 1";
                $client_result = mysqli_query($conn, $client_query);
                $client_row = mysqli_fetch_assoc($client_result);
                $actual_user_id = isset($client_row['client_id']) ? (int)$client_row['client_id'] : $user_id;

                $notif_check = mysqli_query($conn, "SELECT COUNT(*) AS total FROM user_notifications WHERE user_id = $actual_user_id AND is_read = 0");
                $row = mysqli_fetch_assoc($notif_check);
                $total_unread = (int)($row['total'] ?? 0);
                $has_unread = $total_unread > 0;
                $notif_display = $total_unread > 9 ? '9+' : $total_unread;

                // ensure $profile_image and $role_display are defined and safe
                $profile_image = htmlspecialchars($profile_image ?? '/path/to/default.png');
                $role_display = htmlspecialchars($role_display ?? 'User');
                ?>
                
                <div class="notif-icon-wrapper me-2">
                    <a href="user_notifications.php" style="text-decoration: none; color: inherit;" aria-label="Notifications">
                        <!-- inline SVG bell -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="lucide lucide-bell-icon lucide-bell">
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
                       id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true">
                        <img src="<?php echo $profile_image; ?>" alt="Profile Image"
                             class="rounded-circle me-2" style="width:40px; height:40px; object-fit:cover;">
                        <div>
                            <div><?php echo $role_display; ?></div>
                        </div>
                    </a>
                    <ul class="user-account-dropdown dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                        <li><a class="dropdown-item" href="user_profile.php">My Profile</a></li>
                        <li>
                            <a class="dropdown-item" href="<?= htmlspecialchars($site_base_url . 'forms_logic/logout.php') ?>">Sign out</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>


<style>
/* Navigation Bar Styling */
.custom-nav {
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.logo-img {
    height: 45px;
    width: auto;
}

.rvr_smes {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    line-height: 1.4;
}

.welcome-text {
    font-size: 0.875rem;
    font-weight: 400;
}

/* ScrollSpy Button Styling */
.scrollspy-btn {
    transition: all 0.3s ease;
    border-width: 2px;
    font-size: 0.875rem;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
}

.scrollspy-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.scrollspy-btn.active {
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.scrollspy-btn.active[data-section="pending"] {
    background-color: #ffc107;
    color: #000;
    border-color: #ffc107;
}

.scrollspy-btn.active[data-section="notstarted"] {
    background-color: #6c757d;
    color: #fff;
    border-color: #6c757d;
}

.scrollspy-btn.active[data-section="ongoing"] {
    background-color: #198754;
    color: #fff;
    border-color: #198754;
}

.scrollspy-btn.active[data-section="completed"] {
    background-color: #0d6efd;
    color: #fff;
    border-color: #0d6efd;
}

/* Responsive Design */
@media (max-width: 1400px) {
    .scrollspy-btn .btn-text {
        display: none;
    }
    
    .scrollspy-btn {
        padding: 0.375rem 0.75rem !important;
    }
}

@media (max-width: 992px) {
    .custom-nav {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
    
    .rvr_smes {
        font-size: 1rem;
    }
    
    .welcome-text {
        display: block !important;
        margin-top: 0.25rem;
    }
    
    .logo-img {
        height: 35px;
    }
}

@media (max-width: 768px) {
    .custom-nav .row {
        flex-direction: column;
        gap: 1rem;
    }
    
    .col-auto, .col {
        width: 100%;
    }
    
    .d-flex.gap-2 {
        justify-content: center !important;
    }
}
</style>



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
                        <span class="fw-semibold">Log Out</span>
                    </a>
                </div>
            </div>
        </div>

        <!--<hr class="p-0 m-1">-->

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
        <div class="px-5 py-4">

            <!-- Success Alerts -->
            <div class="">
                <?php if (isset($_SESSION['success']) && $_SESSION['success'] != ''): ?>
                    <div class="alert alert-success alert-dismissible fade show text-start" role="alert">
                        <span><?= htmlspecialchars($_SESSION['success']); ?></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>

                    <!-- Failed Alerts -->
                <?php endif; ?>
                <?php if (isset($_SESSION['danger']) && $_SESSION['danger'] != ''): ?>
                    <div class="alert alert-danger alert-dismissible fade show text-start" role="alert">
                        <span><?= htmlspecialchars($_SESSION['danger']); ?></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['danger']); ?>
                <?php endif; ?>

                <!-- Warning Alerts -->
                <?php if (isset($_SESSION['warning'])): ?>
                    <div class="alert alert-warning alert-dismissible fade show text-start" role="alert">
                        <span><?= htmlspecialchars($_SESSION['warning']); ?></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['warning']); ?>
                <?php endif; ?>
            </div>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const alerts = document.querySelectorAll(".alert");
                    alerts.forEach(alert => {
                        setTimeout(() => {
                            // Bootstrap fade out effect
                            alert.classList.remove("show");
                            alert.classList.add("fade");
                            setTimeout(() => alert.remove(), 500); // remove from DOM
                        }, 5000); // 5 seconds
                    });
                });
            </script>
            

           <!-- Project Cards Section -->   
            <div class="px-5 py-4">
    <?php if ($result->num_rows > 0): ?>

        <!-- Pending Confirmation Section -->
        <?php
        $result->data_seek(0);
        $has_pending = false;
        while ($row = $result->fetch_assoc()) {
            if ($row['user_approval'] == 0) {
                $has_pending = true;
                break;
            }
        }
        $result->data_seek(0);
        ?>

        <?php if ($has_pending): ?>
            <div class="mb-5" id="pendingSection">
                <div class="d-flex align-items-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffc107" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-folder-clock-icon lucide-folder-clock">
                        <path d="M16 14v2.2l1.6 1" />
                        <path d="M7 20H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H20a2 2 0 0 1 2 2" />
                        <circle cx="16" cy="16" r="6" />
                    </svg>
                    <h5 class="fw-bold mb-0 ms-2">Pending Confirmation</h5>
                </div>

                <div class="row">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php if ($row['user_approval'] == 0): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <!-- Pending Approval Card -->
                                <div class="card">
                                    <div class="card-body p-4">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <div class="card-title fw-bold mb-1"><?= htmlspecialchars($row['project_name']) ?></div>
                                            </div>
                                            <span class="badge bg-warning px-3 py-2 rounded-pill">PENDING CONFIRMATION</span>
                                        </div>
                                        <div class="row mb-4">
                                            <div class="col-6">
                                                <small class="text-muted d-block">PROJECT VALUE</small>
                                                <span class="fw-bold">₱ <?= number_format($row['projected_budget_cost'], 2, '.', ',') ?></span>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">AREA</small>
                                                <span class="fw-bold"><?= htmlspecialchars($row['project_type']) ?></span>
                                            </div>
                                        </div>
                                        <div class="row mb-4">
                                            <div class="col-6">
                                                <small class="text-muted d-block">START DATE</small>
                                                <span class="fw-bold"><?= date('M j, Y', strtotime($row['start_date'])) ?></span>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">EXPECTED END DATE</small>
                                                <span class="fw-bold"><?= date('M j, Y', strtotime($row['end_date'])) ?></span>
                                            </div>
                                        </div>
                                        <div class="row pending-card-btns">
                                            <div class="col-8 d-flex align-items-center gap-2">
                                                <button class="btn btn-warning rounded-pill approve-btn text-white"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#approvalModal"
                                                    data-project-id="<?= $row['project_id'] ?>"
                                                    data-project-name="<?= htmlspecialchars($row['project_name']) ?>">
                                                    Confirm
                                                </button>
                                                <?php
                                                $proj_id = $row['project_id'];
                                                $contract_query = "SELECT file_path FROM contracts WHERE project_id = ? LIMIT 1";
                                                $contract_stmt = $conn->prepare($contract_query);
                                                $contract_stmt->bind_param("i", $proj_id);
                                                $contract_stmt->execute();
                                                $contract_result = $contract_stmt->get_result();
                                                if ($contract = $contract_result->fetch_assoc()) {
                                                    $filename = basename($contract['file_path']);
                                                    echo '<a href="../uploads/contracts/' . htmlspecialchars($filename) . '" target="_blank" class="btn btn-outline-secondary rounded-pill view-btn">View</a>';
                                                } else {
                                                    echo '<span class="text-muted ms-2">No Contract</span>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>

     <?php
// Reset pointer and separate projects by status
$result->data_seek(0);
$not_yet_started = [];
$ongoing = [];
$completed = [];

// Define phase weights
$phaseWeights = [
    "Design & Permits" => 10,
    "Material Procurement" => 20,
    "Site Preparation" => 15,
    "Project Installation" => 40,
    "Final Inspection" => 15
];
$totalWeight = array_sum($phaseWeights);

while ($row = $result->fetch_assoc()) {
    // Only process approved projects
    if ($row['user_approval'] != 0) {
        // Calculate progress for this project
        $completedWeight = 0;
        $hasInProgress = false;
        
        $phase_query = $conn->prepare("SELECT phase_name, status FROM project_phases WHERE project_id = ? ORDER BY id ASC");
        $phase_query->bind_param("i", $row['project_id']);
        $phase_query->execute();
        $phase_result = $phase_query->get_result();

        $phases = [];
        while ($phase = $phase_result->fetch_assoc()) {
            $phases[] = $phase;
            
            // Check if phase is completed
            if ($phase['status'] === 'Completed' && isset($phaseWeights[$phase['phase_name']])) {
                $completedWeight += $phaseWeights[$phase['phase_name']];
            }
            
            // Check if any phase is "In Progress"
            if ($phase['status'] === 'In Progress') {
                $hasInProgress = true;
            }
        }

        $row['phases'] = $phases;
        $row['completedWeight'] = $completedWeight;
        $row['progress'] = $totalWeight > 0 ? round(($completedWeight / $totalWeight) * 100) : 0;

        // DEBUG: Uncomment these lines to see what's happening
        // echo "<!-- Project: " . $row['project_name'] . " | Completed Weight: $completedWeight | Total Weight: $totalWeight | Has In Progress: " . ($hasInProgress ? 'Yes' : 'No') . " -->";

        // Improved categorization logic
        if (empty($phases)) {
            // No phases defined - treat as not yet started
            $row['project_category'] = 'not_yet_started';
            $not_yet_started[] = $row;
        } elseif ($completedWeight === 0 && !$hasInProgress) {
            // No completed phases and nothing in progress - not yet started
            $row['project_category'] = 'not_yet_started';
            $not_yet_started[] = $row;
        } elseif ($completedWeight > 0 && $completedWeight < $totalWeight) {
            // Some phases completed but not all - ongoing
            $row['project_category'] = 'ongoing';
            $ongoing[] = $row;
        } elseif ($hasInProgress) {
            // Has at least one phase in progress - ongoing
            $row['project_category'] = 'ongoing';
            $ongoing[] = $row;
        } elseif ($completedWeight === $totalWeight && $totalWeight > 0) {
            // All phases completed - completed
            $row['project_category'] = 'completed';
            $completed[] = $row;
        } else {
            // Fallback - treat as not yet started
            $row['project_category'] = 'not_yet_started';
            $not_yet_started[] = $row;
        }
    }
}

// DEBUG: Show counts
// echo "<!-- Not Yet Started: " . count($not_yet_started) . " | Ongoing: " . count($ongoing) . " | Completed: " . count($completed) . " -->";
?>

        <!-- Not Yet Started Section -->
        <?php if (!empty($not_yet_started)): ?>
            <div class="mb-5" id="notStartedSection">
                <div class="d-flex align-items-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#6c757d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <h5 class="fw-bold mb-0 ms-2">Not Yet Started</h5>
                </div>

                <div class="row">
                    <?php foreach ($not_yet_started as $row): ?>
                        <?php include 'project_card_template.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Ongoing Projects Section -->
        <?php if (!empty($ongoing)): ?>
            <div class="mb-5" id="ongoingSection">
                <div class="d-flex align-items-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#198754" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m6 14 1.5-2.9A2 2 0 0 1 9.24 10H20a2 2 0 0 1 1.94 2.5l-1.54 6a2 2 0 0 1-1.95 1.5H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H18a2 2 0 0 1 2 2v2" />
                    </svg>
                    <h5 class="fw-bold mb-0 ms-2">Ongoing Projects</h5>
                </div>

                <div class="row">
                    <?php foreach ($ongoing as $row): ?>
                        <?php include 'project_card_template.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Completed Projects Section -->
        <?php if (!empty($completed)): ?>
            <div class="mb-5" id="completedSection">
                <div class="d-flex align-items-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0d6efd" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <h5 class="fw-bold mb-0 ms-2">Completed Projects</h5>
                </div>

                <div class="row">
                    <?php foreach ($completed as $row): ?>
                        <?php include 'project_card_template.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Empty State -->
        <div class="container d-flex justify-content-center mt-5">
            <div class="text-center my-5">
                <img src="../resources/svg/undraw_project-team_dip6.svg" alt="Project Initiation"
                    class="img-fluid mb-5" style="max-width: 600px;">

                <h3 class="fw-bold mb-3">No Projects Assigned Yet</h3>
                <div class="mx-auto mb-4" style="max-width: 500px;">
                    <p class="text-muted fs-6 mb-3">
                        There are currently no projects available for your review. Once the administrator adds new projects, they will automatically appear here for approval and monitoring.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

            <!-- Approval Modal -->
            <div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius: 12px;">
                        <div class="modal-body px-4 pb-4">
                            <!-- <div class="modal-title fw-bold mb-4 mt-2">Enter Purchase Order Number for Verification</div> -->

                            <form id="approvalForm" action="../forms_logic/approve_project.php" method="POST">
                                <input type="hidden" name="project_id">

                                <!-- Single PO input field -->
                                <div class="mb-4">
                                    <label for="poNumber" class="form-label fw-semibold">Purchase Order Number</label>
                                    <input type="text" class="form-control" id="poNumber" name="po_number" placeholder="Enter Project PO Number" required>
                                </div>

                                <div class="alert-info mb-4">
                                    <h6 class="fw-semibold mb-2">Security Requirements:</h6>
                                    <ul class="mb-0 small">
                                        <li>Enter the exact PO number provided by RVR Squared Mechanical Engineering Services</li>
                                        <li>Contact RVR Admin if you don't have the PO number</li>
                                    </ul>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary px-4 cancel-btn" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="approve_project" class="btn btn-success px-4 submit-btn">Confirm</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
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

        const approvalModal = document.getElementById('approvalModal');
        approvalModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const projectId = button.getAttribute('data-project-id');
            const modal = this;
            modal.querySelector('input[name="project_id"]').value = projectId;
        });

        // // For Logging out if tab closes
        // window.addEventListener("beforeunload", function(e) {
        //     // Detect if the user is navigating within the same site
        //     const destination = document.activeElement && document.activeElement.href;

        //     if (destination && destination.includes(window.location.hostname)) {
        //         // User is just navigating inside your app → do NOT logout
        //         return;
        //     }

        //     // User is closing tab, refreshing, or going outside → logout
        //     navigator.sendBeacon(
        //         "/Financial_Management/forms_logic/logout.php?beacon=1"
        //     );
        // });

        // to auto open modal
        document.addEventListener("DOMContentLoaded", function() {
            var openProjectId = <?= $open_project_id ?>;
            var modalType = "<?= $modal_type ?>"; // 'approval' or 'view'

            if (openProjectId) {
                if (modalType === 'approval') {
                    // Open approval modal
                    var approvalModalEl = document.getElementById('approvalModal');
                    if (approvalModalEl) {
                        var modal = new bootstrap.Modal(approvalModalEl);
                        modal.show();
                    }
                } else {
                    // Open project details modal
                    var modalEl = document.getElementById('projectDetailsModal' + openProjectId);
                    if (modalEl) {
                        var modal = new bootstrap.Modal(modalEl);
                        modal.show();
                    }
                }
            }
        });
    </script>
    
    <script>
         // REPLACE YOUR ENTIRE SCROLLSPY SCRIPT WITH THIS

// Smooth scroll to section
function scrollToSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (section) {
        const navHeight = document.querySelector('.custom-nav')?.offsetHeight || 80;
        const sectionTop = section.offsetTop - navHeight - 20;
        
        window.scrollTo({ 
            top: sectionTop,
            behavior: 'smooth'
        });
        
        // Manually activate the button immediately after clicking
        setTimeout(() => {
            document.querySelectorAll('.scrollspy-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            const clickedBtn = document.querySelector(`[onclick="scrollToSection('${sectionId}')"]`);
            if (clickedBtn) {
                clickedBtn.classList.add('active');
            }
        }, 100);
    }
}

// Update badge counts
function updateCounts() {
    console.log('=== Updating Counts ===');
    
    const pendingSection = document.getElementById('pendingSection');
    const notStartedSection = document.getElementById('notStartedSection');
    const ongoingSection = document.getElementById('ongoingSection');
    const completedSection = document.getElementById('completedSection');
    
    const pendingCount = pendingSection ? pendingSection.querySelectorAll('.card').length : 0;
    const notStartedCount = notStartedSection ? notStartedSection.querySelectorAll('.card').length : 0;
    const ongoingCount = ongoingSection ? ongoingSection.querySelectorAll('.card').length : 0;
    const completedCount = completedSection ? completedSection.querySelectorAll('.card').length : 0;

    console.log('Card counts:', {
        pending: pendingCount,
        notStarted: notStartedCount,
        ongoing: ongoingCount,
        completed: completedCount
    });

    const pendingBadge = document.getElementById('pendingCount');
    const notStartedBadge = document.getElementById('notStartedCount');
    const ongoingBadge = document.getElementById('ongoingCount');
    const completedBadge = document.getElementById('completedCount');

    if (pendingBadge) pendingBadge.textContent = pendingCount;
    if (notStartedBadge) notStartedBadge.textContent = notStartedCount;
    if (ongoingBadge) ongoingBadge.textContent = ongoingCount;
    if (completedBadge) completedBadge.textContent = completedCount;
}

// ScrollSpy functionality - IMPROVED
function updateActiveButton() {
    const buttons = document.querySelectorAll('.scrollspy-btn');
    const navHeight = document.querySelector('.custom-nav')?.offsetHeight || 80;
    
    // Get current scroll position
    const scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
    
    const sections = [
        { id: 'pendingSection', btn: 'pending' },
        { id: 'notStartedSection', btn: 'notstarted' },
        { id: 'ongoingSection', btn: 'ongoing' },
        { id: 'completedSection', btn: 'completed' }
    ];
    
    let currentSection = '';
    
    // Find which section we're currently in
    sections.forEach((section, index) => {
        const sectionElement = document.getElementById(section.id);
        if (sectionElement) {
            const sectionTop = sectionElement.offsetTop - navHeight - 150;
            const sectionBottom = sectionTop + sectionElement.offsetHeight;
            
            // Check if we're within this section
            if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
                currentSection = section.btn;
            }
            
            // Special case: if we're at the very top, activate first visible section
            if (scrollPosition < 100 && index === 0) {
                currentSection = section.btn;
            }
        }
    });

    // Update button states
    buttons.forEach(btn => {
        const btnSection = btn.getAttribute('data-section');
        if (btnSection === currentSection) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
    
    console.log('Current section:', currentSection, 'Scroll position:', scrollPosition);
}

// Initialize ScrollSpy
function initScrollSpy() {
    console.log('Initializing ScrollSpy...');
    
    // Update counts
    updateCounts();
    
    // Initial active button check
    updateActiveButton();
    
    // Update on scroll with throttling
    let ticking = false;
    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                updateActiveButton();
                ticking = false;
            });
            ticking = true;
        }
    });
    
    console.log('ScrollSpy initialized successfully!');
}

// Run initialization when page is fully loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(initScrollSpy, 300);
    });
} else {
    // DOM already loaded
    setTimeout(initScrollSpy, 300);
}

// Also run on window load as backup
window.addEventListener('load', function() {
    setTimeout(initScrollSpy, 500);
});
    </script>
   

</body>


</html>