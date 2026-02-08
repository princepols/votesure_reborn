<?php

require_once __DIR__ . '/../config.php';
session_start();

// Redirect if already logged in
if (!empty($_SESSION['admin_id'])) { 
    header('Location: dashboard.php'); 
    exit; 
}

$message = '';

// Handle Login Attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Fetch admin by username
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    // Verify password
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_login_welcome'] = 1;
        header('Location: dashboard.php');
        exit;
    } else {
        $message = 'Invalid credentials.';
    }
}

// Helper function (assuming it exists in header.php or config.php, included here for safety)
if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$page_title = 'Admin Login';
include __DIR__ . '/header.php';
?>

<style>
    /* Theme Variables */
    :root {
        --theme-orange: #ff7b00;
        --theme-dark-orange: #cc4400;
        --theme-light-orange: #fff5eb;
    }

    body { background-color: #f8f9fa; }

    /* Login Card Styling */
    .card-login {
        border: none;
        border-top: 6px solid var(--theme-dark-orange); /* Themed Top Border */
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        max-width: 450px; /* Constrain size for focus */
        margin: 50px auto;
    }

    /* Heading Color */
    .login-heading {
        color: var(--theme-dark-orange);
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    /* Input Field Focus */
    .form-control:focus {
        border-color: var(--theme-orange);
        box-shadow: 0 0 0 0.25rem rgba(255, 123, 0, 0.25);
    }
    
    /* Login Button Styling */
    .btn-orange-action {
        background: linear-gradient(135deg, var(--theme-orange), var(--theme-dark-orange));
        color: white;
        border: none;
        padding: 8px 30px;
        font-weight: 600;
        transition: transform 0.2s;
    }
    .btn-orange-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(255, 123, 0, 0.4);
        color: white;
    }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card card-login p-4">
                <h4 class="login-heading text-center">
                    <i class="fas fa-lock me-2"></i> Admin Login
                </h4>
                <p class="small text-center text-muted mb-4">Please enter your credentials to manage the election.</p>
                
                <?php if ($message): ?>
                    <div class="alert alert-danger text-center"><i class="fas fa-exclamation-circle me-1"></i> <?php echo h($message); ?></div>
                <?php endif; ?>
                
                <form method="post" class="row g-3">
                    <div class="col-12">
                        <label for="username" class="form-label visually-hidden">Username</label>
                        <input class="form-control" id="username" name="username" placeholder="Username" required autofocus>
                    </div>
                    <div class="col-12">
                        <label for="password" class="form-label visually-hidden">Password</label>
                        <input class="form-control" id="password" type="password" name="password" placeholder="Password" required>
                    </div>
                    <div class="col-12 text-center mt-4">
                        <button class="btn btn-orange-action w-100" type="submit">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </button>
                    </div>
                </form>
            </div>
            

        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
