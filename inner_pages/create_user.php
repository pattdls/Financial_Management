<?php
include('../dbcon.php');
include('../layout/session_check.php');

// Restrict access if the user's role is 'finance'
if ($_SESSION['auth_user']['role'] === 'finance') {
    header('Location: ../unauthorized.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Manage Users"; ?>
    <?php include('../layout/head.php'); ?>
    <link rel="stylesheet" href="../resources/css/create_user.css">
</head>

<body>
    <div class="d-flex">
        <!-- Side Nav Container -->
        <?php include('../layout/sidenav.php'); ?>

        <!-- Main Content -->
        <div class="main-content container-fluid">

            <!-- Top Nav -->
            <?php
            $page_title = 'Manage Users';
            include '../layout/topnav.php';
            ?>

            <div class="container-fluid px-5">

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

                <?php
                // Add User Button (Fixed to use #insertdata)
                $addUserButton = '';
                if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'finance') {
                    $addUserButton = '
                                    <button type="button" class="btn btn-md px-3 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal"
                                        style="background-color: #e39219; color: white; border: none; margin-left:5px;">
                                        <i class="bi bi-plus-circle me-2"></i> Add User
                                    </button>';
                }
                ?>

                <?php
                $clients = [];
                $query = "SELECT client_id, client_name FROM clients";
                $result = mysqli_query($conn, $query);

                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $clients[] = $row;
                    }
                }
                ?>
                <!-- Add User Modal -->
                <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="../forms_logic/create_user_logic.php" method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addUserModalLabel">Create New User</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text"
                                            id="name"
                                            name="name"
                                            class="form-control"
                                            placeholder="Enter your full name"
                                            required
                                            pattern="[A-Za-z\s]+"
                                            title="Numbers are not allowed">
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email"
                                            id="email"
                                            name="email"
                                            class="form-control"
                                            placeholder="Enter your email address"
                                            required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="role" class="form-label">Role</label>
                                        <select id="role" name="role" class="form-select" required>
                                            <option value="">Select a role</option>
                                            <option value="Finance">Finance</option>
                                            <!--<option value="Admin">Admin</option>-->
                                            <option value="User">User</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 d-none" id="clientSelectWrapper">
                                        <label for="client_id" class="form-label">Select Client</label>
                                        <select id="client_id" name="client_id" class="form-select">
                                            <option value="">-- Select a client --</option>
                                            <?php foreach ($clients as $client): ?>
                                                <option value="<?= $client['client_id']; ?>"><?= htmlspecialchars($client['client_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="create_user_btn" class="btn btn-success">
                                        Create Account
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <script>
                    // Show/Hide Client Select based on Role
                    document.addEventListener("DOMContentLoaded", function() {
                        const roleSelect = document.getElementById("role");
                        const clientSelectWrapper = document.getElementById("clientSelectWrapper");

                        roleSelect.addEventListener("change", function() {
                            if (this.value === "User") {
                                clientSelectWrapper.classList.remove("d-none");
                                document.getElementById("client_id").setAttribute("required", "required");
                            } else {
                                clientSelectWrapper.classList.add("d-none");
                                document.getElementById("client_id").removeAttribute("required");
                            }
                        });
                    });
                </script>

                <!-- User List Table -->
                <div class="table-responsive ">
                    <script>
                        $(document).ready(function() {
                            $('#usersTable').DataTable({
                                responsive: true,
                                pageLength: 10,
                                language: {
                                    search: '',
                                    searchPlaceholder: "Search users...",
                                    paginate: {
                                        previous: '<i class="bi bi-chevron-bar-left"></i>', // icon only
                                        next: '<i class="bi bi-chevron-bar-right"></i>' // icon only
                                    },
                                    lengthMenu: "Show _MENU_ entries",
                                    info: "Showing _START_ to _END_ of _TOTAL_ users"
                                },
                                initComplete: function() {
                                    // Wrap the search input
                                    let $filter = $('div.dataTables_filter');
                                    let $input = $filter.find('input');
                                    $input.wrap('<div class="input-group"></div>');
                                    $input.addClass('form-control');

                                    // Append the Add User button (from PHP variable)
                                    <?php if ($addUserButton): ?>
                                        $filter.append(`<?php echo $addUserButton; ?>`);
                                    <?php endif; ?>
                                }
                            });
                        });
                    </script>
                
                    <table id="usersTable" class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th style="text-transform: none;">User ID</th>
                                <th style="text-transform: none;">Name</th>
                                <th style="text-transform: none;">Email</th>
                                <th style="text-transform: none;">Role</th>
                                <th style="text-transform: none;">Status</th>
                                <th class="text-center" style="text-transform: none;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch users from the database
                            $query = "SELECT u.*, la.locked_until 
          FROM users u
          LEFT JOIN login_attempts la ON la.identifier = u.email
          WHERE u.email NOT IN ('schoolcamposanto05@gmail.com', 'camillepunongbayanramos13@gmail.com', 'jeuscedricjmanalili@gmail.com')";

                            $result = mysqli_query($conn, $query);

                            if (mysqli_num_rows($result) > 0):
                                while ($row = mysqli_fetch_assoc($result)):
                            ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['user_id']) ?></td>
                                        <td><?= htmlspecialchars($row['name']) ?></td>
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td><?= strtoupper(htmlspecialchars($row['role'])) ?></td>
                                        <td>
                                            <?php if ($row['verify_status'] == 0): ?>
                                                <span class="badge bg-danger">Disabled</span>
                                            <?php elseif (!empty($row['locked_until']) && strtotime($row['locked_until']) > time()): ?>
                                                <span class="badge bg-warning text-dark">Locked</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <!-- View Details Button -->
                                                <button class="btn btn-view btn-sm" data-bs-toggle="modal" data-bs-target="#viewUserModal<?php echo $row['id']; ?>" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </button>

                                                <!-- Edit Button -->
                                                <button class="btn btn-edit btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $row['id']; ?>" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>

                                                <!-- Enable/Disable Button -->
                                                <?php if ($row['verify_status'] == 1): ?>
                                                    <button class="btn btn-disable btn-sm" onclick="toggleUserStatus(<?php echo $row['id']; ?>, 0)" title="Disable">
                                                        <i class="bi bi-person-x"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-confirm btn-sm" onclick="toggleUserStatus(<?php echo $row['id']; ?>, 1)" title="Enable">
                                                        <i class="bi bi-person-check"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <!-- Unlock Button (only if locked) -->
                                                <?php if ($row['locked_until'] !== null && strtotime($row['locked_until']) > time()): ?>
                                                    <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#unlockConfirmModal<?php echo $row['id']; ?>" title="Unlock">
                                                        <i class="bi bi-unlock"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php
                                endwhile;
                            else:
                                ?>
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div class="container d-flex justify-content-center">
                                            <div class="text-center my-5">
                                                <img src="../resources/svg/undraw_add-user_rbko.svg" alt="User Management"
                                                    class="img-fluid mt-5 mb-5" style="max-width: 430px;">
                                                <h3 class="fw-bold mb-3">Add Your First User</h3>
                                                <div class="mx-auto mb-4" style="max-width: 500px;">
                                                    <p class="text-muted fs-6 mb-3">
                                                        Get started by creating your first user account.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- View User Modal -->
            <?php
            $result = mysqli_query(
                $conn,
                "SELECT u.*, c.client_name, la.attempts, la.locked_until 
        FROM users u
        LEFT JOIN clients c ON u.client_id = c.client_id
        LEFT JOIN login_attempts la ON u.email = la.identifier"
            );

            while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="modal fade" id="viewUserModal<?php echo $row['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">User Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p><strong>ID:</strong> <?= $row['id'] ?></p>
                                <p><strong>User ID:</strong> <?= htmlspecialchars($row['user_id']) ?></p>
                                <p><strong>Name of User:</strong> <?= htmlspecialchars($row['name']) ?></p>

                                <?php if (strtolower($row['role']) === 'user'): ?>
                                    <p><strong>Client:</strong> <?= htmlspecialchars($row['client_name']) ?></p>
                                <?php endif; ?>

                                <p><strong>Email:</strong> <?= htmlspecialchars($row['email']) ?></p>
                                <p><strong>Role:</strong> <?= htmlspecialchars($row['role']) ?></p>
                                <p><strong>Status:</strong>
                                    <?php if (!empty($row['locked_until']) && strtotime($row['locked_until']) > time()): ?>
                                        Locked
                                    <?php else: ?>
                                        <?= $row['verify_status'] ? 'Active' : 'Disabled' ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unlock Confirm Modal -->
                <div class="modal fade" id="unlockConfirmModal<?= $row['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirm Unlock</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                Are you sure you want to unlock <strong><?= htmlspecialchars($row['name']) ?></strong>?
                            </div>
                            <div class="modal-footer">
                                <form method="POST" action="../forms_logic/unlock_user.php">
                                    <input type="hidden" name="email" value="<?= htmlspecialchars($row['email']) ?>">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-success">Yes, Unlock</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit User Modal -->
                <div class="modal fade" id="editUserModal<?php echo $row['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="../forms_logic/edit_user_logic.php" method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit User</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="user_id" value="<?= $row['id'] ?>">

                                    <div class="mb-3">
                                        <label for="edit_name<?php echo $row['id']; ?>" class="form-label">Full Name</label>
                                        <input type="text"
                                            id="edit_name<?php echo $row['id']; ?>"
                                            name="name"
                                            class="form-control"
                                            value="<?= htmlspecialchars($row['name']) ?>"
                                            required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="edit_email<?php echo $row['id']; ?>" class="form-label">Email Address</label>
                                        <input type="email"
                                            id="edit_email<?php echo $row['id']; ?>"
                                            name="email"
                                            class="form-control"
                                            value="<?= htmlspecialchars($row['email']) ?>"
                                            required>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="reset_password" id="resetPassword<?php echo $row['id']; ?>">
                                            <small class="form-check-label text-muted" for="resetPassword<?php echo $row['id']; ?>">
                                                Reset Password (New password will be sent to user's email)
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="edit_user_btn" class="btn btn-success">
                                        Update User
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <!-- Confirm Toggle Modal -->
            <div class="modal fade" id="confirmToggleModal" tabindex="-1" aria-labelledby="confirmToggleLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmToggleLabel">Confirm Action</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="confirmToggleMessage">
                            <!-- Dynamic message goes here -->
                        </div>
                        <div class="modal-footer">
                            <form id="toggleUserForm" method="POST" action="../forms_logic/toggle_user_status_logic.php">
                                <input type="hidden" name="user_id" id="modalUserId">
                                <input type="hidden" name="status" id="modalUserStatus">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="confirmToggleBtn">Yes, Proceed</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">Confirm Deletion</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete user <strong id="deleteUserName"></strong>?</p>
                            <p class="text-danger"><strong>Warning:</strong> This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete User</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading Modal -->
            <div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-sm">
                        <div class="modal-body text-center py-4">
                            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <h5 class="mt-3 mb-1">Creating User Account...</h5>
                            <p class="text-muted">
                                The system is currently setting up the new user profile and generating login credentials.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <script src="./resources/js/auto_logout.js"></script>

            <script>
                // Show loading modal on form submission
                document.addEventListener("DOMContentLoaded", function() {
                    // Get the forms
                    const addUserForm = document.querySelector("#addUserModal form");
                    const editUserForm = document.querySelectorAll("#editUserModal form");

                    // Function to show loading modal
                    function showLoadingModal() {
                        var loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'), {
                            keyboard: false
                        });
                        loadingModal.show();
                        // Close the Add User Modal first
                        var addUserModal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
                        if (addUserModal) {
                            addUserModal.hide();
                        }
                        var editUserModals = document.querySelectorAll('.editUserModal');
                        editUserModals.forEach(function(modal) {
                            var bsModal = bootstrap.Modal.getInstance(modal);
                            if (bsModal) {
                                bsModal.hide();
                            }
                        });
                    }
                    // Function to hide loading modal
                    function hideLoadingModal() {
                        var loadingModal = bootstrap.Modal.getInstance(document.getElementById('loadingModal'));
                        if (loadingModal) {
                            loadingModal.hide();
                        }
                    }
                    // Add submit event listener to add user form
                    if (addUserForm) {
                        addUserForm.addEventListener("submit", function(event) {
                            if (this.checkValidity()) {
                                showLoadingModal(); // Show loading modal on valid form submission
                            }
                        });
                    }
                    // Add submit event listeners to edit user forms
                    if (editUserForms) {
                        editUserForms.addEventListener("edit_usr_btn", function(event) {
                            if (this.checkValidity()) {
                                showLoadingModal(); // Show loading modal on valid form submission
                            }
                        });
                    }
                });
                <?php if (isset($_GET['showAddModal']) && $_GET['showAddModal'] == 1): ?>

                    document.addEventListener('DOMContentLoaded', function() {
                        var addModal = new bootstrap.Modal(document.getElementById('addUserModal'));
                        addModal.show();
                    });
                     document.addEventListener('DOMContentLoaded', function() {
                        var addModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                        addModal.show();
                    });

                <?php endif; ?>

                // ----------------------------------------------------------------------------------------------------->

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

                // Confirm to disable user modal
                function toggleUserStatus(userId, newStatus) {
                    const action = newStatus === 1 ? 'enable' : 'disable';

                    // Update modal content dynamically
                    document.getElementById("confirmToggleMessage").textContent =
                        `Are you sure you want to ${action} this user?`;
                    document.getElementById("modalUserId").value = userId;
                    document.getElementById("modalUserStatus").value = newStatus;

                    // Show the modal
                    const confirmModal = new bootstrap.Modal(document.getElementById('confirmToggleModal'));
                    confirmModal.show();
                }
            </script>
</body>

</html>