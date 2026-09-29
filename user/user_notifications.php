<?php
include('../dbcon.php');
include('../layout/session_check.php');

// Redirect if not logged in
if (!isset($_SESSION['auth_user'])) {
    header('Location: ../login_form.php');
    exit;
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
// Only allow if user is authenticated and is an Admin
// if (!isset($_SESSION['authenticated']) || $_SESSION['auth_user']['role'] != 'admin') {
//     header("Location: login_form.php");
//     exit(0);
// }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Notifications"; ?>
    <?php include('../layout/head.php'); ?>
    <link rel="stylesheet" href="../resources/css/notification.css">
    <link rel="stylesheet" href="../resources/css/user_dash.css">
</head>

<body>
    <div class="d-flex">
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

            <div class="container-fluid px-4">
                <script>
                    $(document).ready(function() {
                        // Project Expense Accounts Table
                        $('#notification_table').DataTable({
                            responsive: true,
                            paging: true,
                            searching: true,
                            ordering: true,
                            order: [],
                            language: {
                                search: '',
                                searchPlaceholder: "Search record...",
                                paginate: {
                                    previous: '<i class="bi bi-chevron-bar-left"></i>', // icon only
                                    next: '<i class="bi bi-chevron-bar-right"></i>' // icon only
                                }
                            },
                            initComplete: function() {
                                $('.dataTables_length').addClass('mb-3');
                            }
                        });
                    })
                </script>
                <div class="card-body">
                    <table class="table table-bordered" id="notification_table" style="overflow: hidden;">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Notification Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $logged_in_user_id = $_SESSION['auth_user']['id'];

                            // Fetch client ID associated with this user
                            $client_query = "SELECT client_id FROM users WHERE id = $logged_in_user_id LIMIT 1";
                            $client_result = mysqli_query($conn, $client_query);

                            if ($client_result && mysqli_num_rows($client_result) > 0) {
                                $client_row = mysqli_fetch_assoc($client_result);
                                $client_id = $client_row['client_id'];
                            } else {
                                die("Client ID not found for this user.");
                            }

                            $notif_query = "SELECT * FROM user_notifications WHERE user_id = $client_id ORDER BY created_at DESC";
                            $notif_result = mysqli_query($conn, $notif_query);

                            while ($row = mysqli_fetch_assoc($notif_result)):
                                $user_notif_id = htmlspecialchars($row['notif_id']);
                                $project_id = $row['project_id'];
                                $created_at = htmlspecialchars($row['created_at']);
                                $message = strip_tags($row['message'], '<b>');

                                // Corrected project query
                                $proj_query = "SELECT project_name, po_num FROM projects WHERE project_id = $project_id";
                                $proj_result = mysqli_query($conn, $proj_query);
                                if ($proj_result && mysqli_num_rows($proj_result) > 0) {
                                    $proj = mysqli_fetch_assoc($proj_result);
                                    $project_name = $proj['project_name'];
                                    $po_number = $proj['po_num'];
                                } else {
                                    $project_name = "Unknown Project";
                                    $po_number = "N/A";
                                }

                                $is_read = $row['is_read'];
                                $url = "../forms_logic/read_notif.php?user_notif_id=$user_notif_id&user_project_id=$project_id";
                                $row_class = !$is_read ? 'unread-notif' : 'read-notif';
                            ?>
                                <tr onclick="location.href='<?php echo $url; ?>'" class="<?php echo $row_class; ?>">
                                    <td><?php echo $created_at; ?></td>
                                    <td><?php echo "$message ($project_name)"; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <script>
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

                // Toggle Password Visibility Function
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
            </script>
</body>

</html>