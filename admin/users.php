<?php
require_once __DIR__ . '/../config.php';
session_start();

// 🔐 Require admin login
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Admin Management';
//$message = null;
//$message_type = 'success';
$message = $_SESSION['message'] ?? null;
$message_type = $_SESSION['message_type'] ?? 'success';

unset($_SESSION['message'], $_SESSION['message_type']);


// Get current admin info to check if they're the default admin
$current_admin_stmt = $pdo->prepare("SELECT id, username FROM admins WHERE id = ?");
$current_admin_stmt->execute([$_SESSION['admin_id']]);
$current_admin = $current_admin_stmt->fetch(PDO::FETCH_ASSOC);
$is_default_admin = ($current_admin['username'] === 'admin');

/* ===========================
   ADD ADMIN
=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $message = 'Username and password are required.';
        $message_type = 'error';
    } else {
        // check if username exists
        $check = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $check->execute([$username]);

        if ($check->fetch()) {
            $message = 'Username already exists.';
            $message_type = 'error';
        } else {
            // Store both plain password (for viewing) and hashed password (for login)
            $plain_password = $password; // Store plain password
            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare(
                "INSERT INTO admins (username, password_hash, plain_password, created_at)
                 VALUES (?, ?, ?, NOW())"
            );
            $stmt->execute([$username, $hash, $plain_password]);

            $message = 'Admin account successfully added.';
        }
    }
}

/* ===========================
   DELETE ADMIN
=========================== */
if (isset($_GET['delete'])) {
    $delete_id = (int) $_GET['delete'];
    
    // Get the admin to be deleted
    $target_stmt = $pdo->prepare("SELECT username FROM admins WHERE id = ?");
    $target_stmt->execute([$delete_id]);
    $target_admin = $target_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$target_admin) {
        $message = 'Admin account not found.';
        $message_type = 'error';
    } else if ($delete_id === (int) $_SESSION['admin_id']) {
        $message = 'You cannot delete your own account.';
        $message_type = 'error';
    } else if (!$is_default_admin) {
        // Only default admin can delete accounts
        $message = 'Access denied: Only the default admin can delete admin accounts.';
        $message_type = 'error';
    } else if ($target_admin['username'] === 'admin') {
        // Prevent deletion of default admin account
        $message = 'Cannot delete the default admin account.';
        $message_type = 'error';
    } else {
        // Default admin can delete other admin accounts
        $del = $pdo->prepare("DELETE FROM admins WHERE id = ?");
        $del->execute([$delete_id]);

        $message = 'Admin account deleted successfully.';

        $_SESSION['message_type'] = 'success';

        header("Location: users.php");
        exit;
    }
}

/* ===========================
   FETCH ADMINS
=========================== */
$admins = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Delete confirmation function (only shown for default admin)
function confirmDelete(adminId, username) {
    Swal.fire({
        title: 'Delete Admin Account?',
        html: `<div class="text-center">
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                <h5 class="fw-bold">Are you sure?</h5>
                <p class="text-muted">You are about to delete admin account:</p>
                <div class="alert alert-danger d-inline-block">
                    <strong>ID:</strong> ${adminId}<br>
                    <strong>Username:</strong> ${username}
                </div>
                <p class="text-danger small mt-2">This action cannot be undone!</p>
               </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete It',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#E67E22',
        cancelButtonColor: '#6c757d',
        reverseButtons: true,
        customClass: {
            popup: 'border-top-orange',
            confirmButton: 'btn btn-danger px-4',
            cancelButton: 'btn btn-secondary px-4'
        },
        buttonsStyling: false,
        showCloseButton: true,
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Deleting...',
                html: 'Please wait while we delete the admin account.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            window.location.href = `?delete=${adminId}`;
        }
    });
}

// Show delete permission error for non-default admins
function showDeletePermissionError() {
    Swal.fire({
        title: 'Access Denied',
        html: `<div class="text-center">
                <i class="fas fa-lock fa-3x text-danger mb-3"></i>
                <h5 class="fw-bold">Permission Required</h5>
                <div class="alert alert-warning">
                    <i class="fas fa-shield-alt me-2"></i>
                    <strong>Only the default admin can delete admin accounts.</strong>
                </div>
                <p class="text-muted mt-2">
                    Contact the system administrator if you need to remove an account.
                </p>
               </div>`,
        icon: 'error',
        confirmButtonText: 'OK',
        confirmButtonColor: '#E67E22',
        customClass: {
            popup: 'border-top-orange'
        }
    });
}

// Show password function
function showPassword(adminId, username) {
    Swal.fire({
        title: 'Admin Password',
        html: `<div class="text-center">
                <i class="fas fa-key fa-3x text-primary mb-3"></i>
                <h5 class="fw-bold">${username}</h5>
                <div class="alert alert-light border mt-3">
                    <h6 class="text-muted mb-2">Password:</h6>
                    <div class="password-display p-3 bg-dark text-white rounded">
                        <code id="password-text" class="fs-5">Loading...</code>
                    </div>
                    <button onclick="copyPassword()" class="btn btn-sm btn-outline-secondary mt-3">
                        <i class="fas fa-copy me-1"></i> Copy to Clipboard
                    </button>
                </div>
               </div>`,
        icon: 'info',
        confirmButtonText: 'Close',
        confirmButtonColor: '#E67E22',
        showCloseButton: true,
        customClass: {
            popup: 'border-top-orange'
        },
        didOpen: () => {
            // Fetch password via AJAX
            fetch(`get_password.php?id=${adminId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('password-text').textContent = data.password;
                    } else {
                        document.getElementById('password-text').textContent = 'Error: ' + data.error;
                    }
                })
                .catch(error => {
                    document.getElementById('password-text').textContent = 'Failed to load password';
                });
        }
    });
}

function copyPassword() {
    const passwordText = document.getElementById('password-text').textContent;
    navigator.clipboard.writeText(passwordText).then(() => {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
        
        Toast.fire({
            icon: 'success',
            title: 'Password copied to clipboard!'
        });
    });
}
</script>

<?php if ($message): ?>
<script>
Swal.fire({
    icon: <?= json_encode($message_type === 'error' ? 'error' : 'success') ?>,
    title: <?= json_encode($message_type === 'error' ? 'Error' : 'Success') ?>,
    text: <?= json_encode($message) ?>,
    confirmButtonColor: '#E67E22',
    customClass: {
        popup: 'border-top-orange'
    }
});
</script>
<?php endif; ?>

<style>
.border-top-orange {
    border-top: 4px solid #E67E22 !important;
}
.btn-orange {
    background-color: #E67E22;
    border-color: #E67E22;
    color: white;
}
.btn-orange:hover {
    background-color: #d35400;
    border-color: #d35400;
}
.password-display {
    font-family: 'Monaco', 'Menlo', 'Consolas', monospace;
    letter-spacing: 1px;
}
.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(230, 126, 34, 0.05);
}
.card-header {
    background: linear-gradient(to right, #f8f9fa, #fff);
    border-bottom: 2px solid #E67E22;
    color: #333;
    font-weight: 600;
}
.badge-you {
    background: linear-gradient(45deg, #E67E22, #e74c3c);
    color: white;
}
.password-cell {
    max-width: 200px;
    word-break: break-all;
}
.action-buttons .btn {
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
}
.eye-icon {
    cursor: pointer;
    color: #E67E22;
    transition: color 0.2s;
}
.eye-icon:hover {
    color: #d35400;
}
.permission-badge {
    background-color: #6f42c1;
    color: white;
}
.admin-role {
    font-size: 0.75rem;
    padding: 2px 8px;
    border-radius: 12px;
    margin-left: 5px;
}
</style>

<div class="card-modern">
    <div class="card-header">
        <i class="fas fa-user-plus me-2"></i> Add New Admin
    </div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <input type="hidden" name="add_admin" value="1">

            <div class="col-md-6">
                <label class="form-label fw-bold">Username</label>
                <input type="text" name="username" class="form-control" required 
                       placeholder="Enter username" autocomplete="off">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="password-field" 
                           class="form-control" required placeholder="Enter password" autocomplete="new-password">
                    <button type="button" class="btn btn-outline-secondary" 
                            onclick="togglePasswordVisibility()">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>


            <div class="col-12 text-end mt-3">
                <button class="btn btn-orange px-4">
                    <i class="fas fa-save me-1"></i> Create Admin
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card-modern mt-4">
    <div class="card-header">
        <i class="fas fa-users me-2"></i> Admin Accounts
        <span class="float-end">
            <span class="badge bg-secondary me-2">Total: <?= count($admins) ?></span>
            <?php if ($is_default_admin): ?>
                <span class="badge bg-success">
                    <i class="fas fa-crown me-1"></i> Default Admin
                </span>
            <?php else: ?>
                <span class="badge bg-info">
                    <i class="fas fa-user me-1"></i> Added Admin
                </span>
            <?php endif; ?>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <!--<th width="50">ID</th>-->
                        <th>Username</th>
                        <th>Password</th>
                        <!--<th>Password Hash</th>-->
                        <th>Created At</th>
                        <th width="180" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $a): ?>
                    <tr>
                        <!--<td><?= h($a['id']) ?></td>-->
                        <td>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-circle me-2 text-muted"></i>
                                <?= h($a['username']) ?>
                                <?php if ($a['id'] == $_SESSION['admin_id']): ?>
                                    <span class="badge badge-you ms-2">You</span>
                                <?php endif; ?>
                                <?php if ($a['username'] === 'admin'): ?>
                                    <span class="badge bg-dark admin-role">Default</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary admin-role">Added</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="password-cell">
                            <?php if ($is_default_admin): ?>
                                <!-- Default admin can see all plain passwords -->
                                <div class="d-flex align-items-center">
                                    <code class="bg-light p-1 rounded"><?= h($a['plain_password'] ?? 'Not set') ?></code>
                                    <i class="fas fa-eye eye-icon ms-2" 
                                       onclick="showPassword(<?= $a['id'] ?>, '<?= h($a['username']) ?>')"
                                       title="View Password"></i>
                                </div>
                            <?php elseif ($a['id'] == $_SESSION['admin_id']): ?>
                                <!-- Users can see their own password -->
                                <div class="d-flex align-items-center">
                                    <code class="bg-light p-1 rounded"><?= h($a['plain_password'] ?? 'Not set') ?></code>
                                    <i class="fas fa-eye eye-icon ms-2" 
                                       onclick="showPassword(<?= $a['id'] ?>, '<?= h($a['username']) ?>')"
                                       title="View Password"></i>
                                </div>
                            <?php else: ?>
                                <!-- Non-default admins cannot see other passwords -->
                                <span class="text-muted">
                                    <i class="fas fa-lock me-1"></i> Hidden
                                </span>
                            <?php endif; ?>
                        </td>

<!--
                        <td class="password-cell">
                            <small class="text-muted">
                                <?= h(substr($a['password_hash'], 0, 30)) ?>...
                            </small>
                        </td>
                            -->

                        <td><?= h($a['created_at']) ?></td>
                        <td class="text-center action-buttons">
                            <?php if ($a['id'] != $_SESSION['admin_id']): ?>
                                <?php if ($is_default_admin && $a['username'] !== 'admin'): ?>
                                    <!-- Only default admin can delete added admin accounts -->
                                    <button onclick="confirmDelete(<?= $a['id'] ?>, '<?= h($a['username']) ?>')"
                                            class="btn btn-sm btn-danger"
                                            title="Delete Admin">
                                        <i class="fas fa-trash me-1"></i> Delete
                                    </button>
                                <?php else: ?>
                                    <!-- Added admins cannot delete any accounts -->
                                    <?php if (!$is_default_admin): ?>
                                        <button onclick="showDeletePermissionError()"
                                                class="btn btn-sm btn-secondary"
                                                title="Delete Admin"
                                                disabled>
                                            <i class="fas fa-trash me-1"></i> Delete
                                        </button>
                                    <?php else: ?>
                                        <!-- Default admin cannot delete default admin account -->
                                        <span class="badge bg-dark px-3 py-2">
                                            <i class="fas fa-shield-alt me-1"></i> Protected
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Current user -->
                                <span class="badge bg-primary px-3 py-2">
                                    <i class="fas fa-user-check me-1"></i> Current
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (empty($admins)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="fas fa-user-slash fa-3x mb-3 d-block opacity-50"></i>
                            No admin accounts found.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!--
<div class="row mt-4">
    <div class="col-md-6">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Security Warning:</strong> Plain passwords are stored in the database. 
            This is not recommended for production systems.
        </div>
    </div>
    <div class="col-md-6">
        <div class="alert alert-info">
            <i class="fas fa-shield-alt me-2"></i>
            <strong>Permission Info:</strong><br>
            • Only <span class="badge bg-dark">Default Admin</span> can delete admin accounts<br>
            • <span class="badge bg-secondary">Added Admins</span> cannot delete any accounts<br>
            • Default admin can view all passwords<br>
            • Added admins can only view their own password
        </div>
    </div>
</div>
                    -->
<script>
// Toggle password visibility in add form
function togglePasswordVisibility() {
    const passwordField = document.getElementById('password-field');
    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordField.setAttribute('type', type);
}

// Auto-hide password field when form is submitted
document.querySelector('form').addEventListener('submit', function() {
    const passwordField = document.getElementById('password-field');
    passwordField.setAttribute('type', 'password');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>