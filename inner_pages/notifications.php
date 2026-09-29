<?php
include('../dbcon.php');
include('../layout/session_check.php');

// Redirect if not logged in
if (!isset($_SESSION['auth_user'])) {
    header('Location: ../login_form.php');
    exit;
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
</head>

<body>
    <div class="d-flex">
        <!-- Side Nav Container -->
        <?php include('../layout/sidenav.php'); ?>

        <!-- Main Content -->
        <div class="main-content container-fluid ">

            <!-- Top Nav -->
            <?php
            $page_title = 'Notifications';
            include '../layout/topnav.php';
            ?>
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
                            }
                        });
                        // Wrap toolbar and add margin
                        var toolbar = $('<div id="toolbar" class="d-flex align-items-center justify-content-between"></div>');
                        toolbar.append($('#notification_table_wrapper .dataTables_length'));
                        toolbar.append($('#notification_table_wrapper .dataTables_filter'));
                        $('#notification_table_wrapper').prepend(toolbar);
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
                            $connection = mysqli_connect("localhost", "root", "", "financial_management");
                            $user_id = $_SESSION['auth_user']['id'];
                            $role = $_SESSION['auth_user']['role'];

                            $merged_notifs = [];

                            // For finance and admin: merge user_notifications + system notifications
                            if (in_array($role, ['finance', 'admin'])) {

                                // 1. Fetch from user_notifications (includes project_deadline)
                                $query1 = "SELECT notif_id, project_id, message, created_at, is_read, type 
               FROM user_notifications 
               WHERE user_id = ? 
               ORDER BY created_at DESC";
                                $stmt1 = $con->prepare($query1);
                                $stmt1->bind_param("i", $user_id);
                                $stmt1->execute();
                                $result1 = $stmt1->get_result();

                                while ($row = $result1->fetch_assoc()) {
                                    $merged_notifs[] = $row;
                                }
                                $stmt1->close();

                                // 2. Fetch from system notifications table
                                $query2 = "SELECT notif_id, project_id, message, created_at, is_read, 'system' as type 
               FROM notifications 
               ORDER BY created_at DESC";
                                $result2 = mysqli_query($con, $query2);

                                while ($row = mysqli_fetch_assoc($result2)) {
                                    $merged_notifs[] = $row;
                                }

                                // 3. Sort all by created_at DESC
                                usort($merged_notifs, function ($a, $b) {
                                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                                });
                            } else {
                                // Non-admin/finance: show only system notifications
                                $query = "SELECT notif_id, project_id, message, created_at, is_read, 'system' as type 
              FROM notifications 
              ORDER BY created_at DESC";
                                $result = mysqli_query($con, $query);

                                while ($row = mysqli_fetch_assoc($result)) {
                                    $merged_notifs[] = $row;
                                }
                            }

                            // Debug: log count
                            error_log("Total merged notifications: " . count($merged_notifs));

                            foreach ($merged_notifs as $row):
                                $notif_id   = htmlspecialchars($row['notif_id']);
                                $project_id = $row['project_id'];
                                $created_at = htmlspecialchars($row['created_at']);
                                $message    = $row['message'];
                                $is_read    = isset($row['is_read']) ? (int)$row['is_read'] : 0;
                                $type       = htmlspecialchars($row['type']);
                                $url        = "../forms_logic/read_notif.php?notif_id=$notif_id&project_id=$project_id";
                                $row_class  = !$is_read ? 'unread-notif' : 'read-notif';
                            ?>
                                <tr onclick="location.href='<?php echo $url; ?>'" class="<?php echo $row_class; ?>" title="Type: <?php echo $type; ?>">
                                    <td><?php echo $created_at; ?></td>
                                    <td><?php echo $message; ?></td>
                                </tr>
                            <?php endforeach; ?>
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