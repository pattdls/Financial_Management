<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
include('../dbcon.php'); 

$site_base_url =  "https://rvrsmes-fms.com/"; // adjust as needed

$profile_image = '/Financial_Management/uploads/images/default_user.png'; // default fallback

if (isset($_SESSION['auth_user']['id'])) {
    $user_id = $_SESSION['auth_user']['id'];

    $query = "SELECT profile_image, role FROM users WHERE id = '$user_id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        if (!empty($row['profile_image'])) {
            // Store paths are relative to the project directory, not the web root.
            $profile_image = '/Financial_Management/' . ltrim($row['profile_image'], '/');
        }
        $role_display = strtoupper($row['role']);
    }
}

// Enhanced logout.php file with user choice

?>
<html>
    
<head>
    <link rel="stylesheet" href="../resources/css/main.css">
</head>
    

<body>
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

    <!-- Top Nav -->
    <nav class="d-flex flex-row align-items-start justify-content-between ps-0 pt-2">

        <div class="page-title">
            <?php echo isset($page_title) ? $page_title : 'Dashboard'; ?>
        </div>

        <div class="d-flex align-items-center">
            <p id="datetime" class="me-3 mt-4"></p>
             <?php
            $user_id = $_SESSION['auth_user']['id'];
            // Count from user_notifications (user-specific)
            $user_notif_check = mysqli_query($conn, "SELECT COUNT(*) AS total FROM user_notifications WHERE user_id = $user_id AND is_read = 0");
            $user_row = mysqli_fetch_assoc($user_notif_check);
            $user_unread = (int)$user_row['total'];
            
            // Count from notifications (system-wide alerts)
            $system_notif_check = mysqli_query($conn, "SELECT COUNT(*) AS total FROM notifications WHERE is_read = 0");
            $system_row = mysqli_fetch_assoc($system_notif_check);
            $system_unread = (int)$system_row['total'];
            
            // Combine both counts
            $total_unread = $user_unread + $system_unread;
            $has_unread = $total_unread > 0;

            // Display logic
            $notif_display = $total_unread > 9 ? '9+' : $total_unread;
            ?>
            <div class="notif-icon-wrapper">
                <a href="notifications.php" class="notif-icon-wrapper" style="text-decoration: none; color: inherit;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-bell-icon lucide-bell mt-2 me-3">
                        <path d="M10.268 21a2 2 0 0 0 3.464 0" />
                        <path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326" />
                    </svg>
                    <?php if ($has_unread): ?>
                        <span class="notif-badge"><?php echo $notif_display; ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <!-- User Account -->
            <div class="dropdown">
                <a href="#" class="user-account d-flex align-items-center text-decoration-none dropdown-toggle"
                    id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?php echo htmlspecialchars($profile_image, ENT_QUOTES, 'UTF-8'); ?>" alt="Profile Image"
                        class="rounded-circle me-2 profile-img" style="width:40px; height:40px; object-fit:cover;">
                    <div>
                        <div class=""><?php echo htmlspecialchars($role_display); ?></div>
                    </div>
                </a>
                <ul class="user-account-dropdown dropdown-menu dropdown-menu-dark text-small shadow">
                    <li><a class="dropdown-item" href="/Financial_Management/inner_pages/my_profile.php">My Profile</a></li>
                    <li><a class="dropdown-item d-flex align-items-center" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">Sign out</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <script>
    //     Force logout modal button to absolute URL
    // document.getElementById('modalLogoutBtn').addEventListener('click', function(e) {
    //     e.preventDefault();
    //     window.location.href = 'forms_logic/logout.php';
    // });
        document.querySelectorAll('.dropdown-toggle').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const menu = btn.nextElementSibling;
                menu.classList.toggle('show');
                e.stopPropagation();
            });
        });

        document.addEventListener('click', function() {
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => menu.classList.remove('show'));
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<style>
    .dropdown-menu {
        opacity: 0;
        transition: opacity 0.3s ease, transform 0.3s ease;
        display: block;
        pointer-events: none;
    }

    /* When .show is added */
    .dropdown-menu.show {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
        /* allow clicks */
    }
    nav {
        display: flex;
        align-items: center;  
        justify-content: space-between;
        width: 100%;
        padding: 0.5rem 1rem; 
        box-sizing: border-box; 
        }
        
    @media (min-width: 577px) and (max-width: 992px) {
    .main-content {
        margin-left: 50px;
    }
     .nav{
        vertical-align: middle !important;
    }
    .user-account{
        font-size: 12px;
    }
    .profile-img {
        width: 30px !important;
        height: 30px !important;
    }
    .page-title{
        padding-left: 0 !important;
        margin-left: 0;
        margin-top: 10px;
        font-size: 20px !important;
        white-space: nowrap !important;
    }
    #datetime {
     font-size: 12px;
    white-space: nowrap !important;     
    }
    .notif-badge { 
    font-size: 8px !important;
    min-width: 8px !important;
    height: 8px !important;
    padding: 2px !important;
    line-height: 12px;
    }
     .notif-icon-wrapper svg{
        width: 20px !important;
        height: 20px !important;
    }
    body {
        overflow-x: hidden; /* only if needed */
    }

}
@media (max-width: 576px) {
  .main-content {
        margin-left: 30px;
    }
     .nav{
        vertical-align: middle !important;
    }
    body{
  outline: 1px solid red; /* highlight all elements */
}
    .user-account{
        font-size: 12px;
    }
    .profile-img {
        width: 20px !important;
        height: 20px !important;
    }
    .page-title{
        padding-left: 0 !important;
        margin-left: 0;
        margin-top: 15px;
        font-size: 16px !important;
        white-space: nowrap !important;
    }
    #datetime {
    font-size: 12px;
    white-space: nowrap !important;    
    }
    .notif-badge { 
    font-size: 8px !important;
    min-width: 8px !important;
    height: 8px !important;
    padding: 2px !important;
    line-height: 12px;
    }
     .notif-icon-wrapper svg{
        width: 20px !important;
        height: 20px !important;
    }
    body {
        overflow-x: hidden; /* only if needed */
    }

}

</style>