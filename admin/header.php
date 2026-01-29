<?php
if (session_status() == PHP_SESSION_NONE) session_start();
ob_start();

require_once __DIR__ . '/../config.php';
$page_title = isset($page_title) ? $page_title : 'Admin Panel';
include __DIR__ . '/../includes/header.php';




// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Get admin info for sidebar
$admin_name = $_SESSION['admin_name'] ?? 'Administrator';
$admin_role = $_SESSION['admin_role'] ?? 'Manage Everything.';
?>

<style>
/* ===== MODERN SIDEBAR DESIGN ===== */
.sidebar {
    background: linear-gradient(180deg, #E67E22 0%, #D35400 100%);
    min-height: 100vh;
    padding: 0;
    box-shadow: 4px 0 20px rgba(0,0,0,0.15);
    position: relative;
    overflow: hidden;
    border: none !important;
}

.sidebar::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 100%;
    background: linear-gradient(45deg, rgba(255,255,255,0.05) 0%, transparent 50%);
    pointer-events: none;
}

/* Logo Section */
.logo-container {
    padding: 25px 20px 15px;
    border-bottom: 1px solid rgba(255,255,255,0.15);
    background: rgba(0,0,0,0.08);
    text-align: center;
}

.logo-container img {
    filter: brightness(0) invert(1);
    transition: all 0.3s ease;
    height: 45px;
}

.logo-container:hover img {
    transform: scale(1.05);
    filter: brightness(0) invert(1) drop-shadow(0 2px 8px rgba(255,255,255,0.3));
}

/* Admin Info Section */
.admin-info-section {
    padding: 20px 15px;
    border-bottom: 1px solid rgba(255,255,255,0.15);
    background: rgba(0,0,0,0.08);
}

.admin-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(255,255,255,0.2), rgba(255,255,255,0.1));
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    border: 2px solid rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
}

.admin-avatar i {
    font-size: 1.3rem;
    color: rgba(255,255,255,0.9);
}

.admin-details {
    text-align: center;
}

.admin-name {
    color: #fff;
    font-weight: 600;
    font-size: 1rem;
    margin-bottom: 3px;
}

.admin-role {
    color: rgba(255,255,255,0.8);
    font-size: 0.8rem;
    font-weight: 500;
}

/* Navigation Styles */
.sidebar-nav {
    padding: 15px 0;
    flex: 1;
}

.nav-item {
    margin: 2px 12px;
}

.nav-link {
    color: rgba(255,255,255,0.9) !important;
    padding: 12px 16px !important;
    border-radius: 10px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-weight: 500;
    position: relative;
    overflow: hidden;
    border: none;
    background: transparent !important;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
}

.nav-link::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 3px;
    background: rgba(255,255,255,0.9);
    transform: scaleY(0);
    transition: transform 0.3s ease;
    border-radius: 0 4px 4px 0;
}

.nav-link::after {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, rgba(255,255,255,0.12), transparent);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.nav-link:hover {
    color: #fff !important;
    background: rgba(255,255,255,0.15) !important;
    transform: translateX(6px);
    box-shadow: 0 3px 12px rgba(0,0,0,0.2);
}

.nav-link:hover::before {
    transform: scaleY(1);
}

.nav-link:hover::after {
    opacity: 1;
}

.nav-link.active {
    color: #fff !important;
    background: linear-gradient(135deg, rgba(255,255,255,0.18), rgba(255,255,255,0.1)) !important;
    transform: translateX(6px);
    box-shadow: 0 3px 12px rgba(0,0,0,0.25);
}

.nav-link.active::before {
    transform: scaleY(1);
    background: #fff;
}

.nav-link i {
    width: 18px;
    text-align: center;
    font-size: 1rem;
    transition: all 0.3s ease;
    position: relative;
    z-index: 2;
}

.nav-link:hover i {
    transform: scale(1.1);
}

.nav-link.active i {
    transform: scale(1.1);
    color: #fff;
}

.nav-link .nav-text {
    position: relative;
    z-index: 2;
    flex: 1;
}

/* Logout Button Special Styling */
.nav-link.text-danger {
    background: rgba(220,53,69,0.12) !important;
    border: 1px solid rgba(220,53,69,0.25) !important;
    margin-top: 8px;
}

.nav-link.text-danger:hover {
    background: rgba(220,53,69,0.2) !important;
    color: #fff !important;
    transform: translateX(6px);
    border-color: rgba(220,53,69,0.4) !important;
}

.nav-link.text-danger.active {
    background: rgba(220,53,69,0.25) !important;
}

/* Bottom Section */
.sidebar-bottom {
    padding: 15px 12px;
    border-top: 1px solid rgba(255,255,255,0.15);
    background: rgba(0,0,0,0.08);
}

.system-status {
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: rgba(255,255,255,0.85);
    font-size: 0.8rem;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 6px;
}

.status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #2ECC71;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.6; }
    100% { opacity: 1; }
}

/* ===== MAIN CONTENT AREA ===== */
.main-content {
    opacity: 0;
    animation: fadeInUp 0.6s ease forwards;
    background: #f8f9fa;
    min-height: 100vh;
    padding: 0;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Content Header */
.content-header {
    background: linear-gradient(135deg, #E67E22, #D35400);
    color: white;
    padding: 20px 25px;
    margin-bottom: 0;
    box-shadow: 0 2px 12px rgba(0,0,0,0.1);
}

.content-title {
    font-size: 1.6rem;
    font-weight: 700;
    margin: 0;
    color: #fff;
}

.content-subtitle {
    color: rgba(255,255,255,0.9);
    font-size: 0.95rem;
    margin: 4px 0 0 0;
}

/* Content Body */
.content-body {
    padding: 25px;
    background: #f8f9fa;
    min-height: calc(100vh - 120px);
}

/* ===== RESPONSIVE DESIGN ===== */
@media (max-width: 768px) {
    .sidebar {
        min-height: auto;
        border-radius: 0 !important;
    }
    
    .main-content {
        padding: 0;
    }
    
    .content-body {
        padding: 20px 15px;
    }
    
    .content-header {
        padding: 15px 20px;
    }
    
    .content-title {
        font-size: 1.4rem;
    }
    
    .nav-link {
        padding: 10px 14px !important;
        font-size: 0.85rem;
    }
}

/* ===== CARD AND TABLE STYLING ===== */
.card, .card-modern {
    border: 0 !important;
    box-shadow: 0 3px 15px rgba(0,0,0,0.08) !important;
    background-clip: padding-box;
    overflow: hidden;
    border-radius: 12px !important;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    margin-bottom: 20px;
}

.card:hover, .card-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.12) !important;
}

.card .card-header, .card-header {
    border: 0 !important;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    padding: 18px 20px;
    font-weight: 600;
    color: #2c3e50;
    font-size: 1.1rem;
}

.table, .table-voters, table {
    border-collapse: separate !important;
    border-spacing: 0;
    background: transparent !important;
    border-radius: 10px;
    overflow: hidden;
    margin: 0;
}

.table td, .table th, .table-voters td, .table-voters th {
    background: transparent !important;
    border: 1px solid rgba(0,0,0,0.08) !important;
    vertical-align: middle;
    padding: 12px 15px;
}

.table-striped tbody tr:nth-of-type(odd) td {
    background-color: rgba(0,0,0,0.03) !important;
}

.table thead th {
    background: linear-gradient(135deg, #E67E22, #D35400) !important;
    color: white;
    border: none !important;
    font-weight: 600;
    padding: 14px 15px;
    font-size: 0.95rem;
}

/* Button Styling */
.btn-primary {
    background: linear-gradient(135deg, #E67E22, #D35400);
    border: none;
    border-radius: 8px;
    padding: 8px 18px;
    font-weight: 600;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #D35400, #BA4A00);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
}

.btn-outline-primary {
    border: 2px solid #E67E22;
    color: #E67E22;
    background: transparent;
    border-radius: 8px;
    padding: 8px 18px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-outline-primary:hover {
    background: #E67E22;
    border-color: #E67E22;
    color: white;
    transform: translateY(-1px);
}

/* Form Styling */
.form-control:focus, .form-select:focus {
    border-color: #E67E22;
    box-shadow: 0 0 0 0.2rem rgba(230, 126, 34, 0.15);
}

/* Alert Styling */
.alert {
    border: none;
    border-radius: 10px;
    padding: 12px 18px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

/* Badge Styling */
.badge {
    border-radius: 6px;
    padding: 5px 10px;
    font-weight: 500;
    font-size: 0.8rem;
}

.badge.bg-primary {
    background: linear-gradient(135deg, #E67E22, #D35400) !important;
}

/* Progress Bar Styling */
.progress {
    border-radius: 8px;
    height: 8px;
    background: rgba(0,0,0,0.1);
}

.progress-bar {
    background: linear-gradient(135deg, #E67E22, #D35400);
    border-radius: 8px;
}

/* List Group Styling */
.list-group-item {
    border: 1px solid rgba(0,0,0,0.08);
    padding: 12px 16px;
    transition: all 0.3s ease;
}

.list-group-item:hover {
    background: rgba(230, 126, 34, 0.05);
    border-color: rgba(230, 126, 34, 0.2);
}

/* Remove focus outlines but keep accessibility */
.btn:focus, .form-control:focus, .list-group-item:focus {
    outline: none !important;
    box-shadow: 0 0 0 3px rgba(230, 126, 34, 0.15) !important;
}

/* Custom utility classes */
.small-muted {
    color: #6c757d !important;
    font-size: 0.85rem;
}

.text-orange {
    color: #E67E22 !important;
}

.bg-orange {
    background-color: #E67E22 !important;
}

/* Flash message styling */
.alert-success {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
    border-left: 4px solid #28a745;
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    color: #721c24;
    border-left: 4px solid #dc3545;
}

.alert-warning {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    color: #856404;
    border-left: 4px solid #ffc107;
}

.alert-info {
    background: linear-gradient(135deg, #d1ecf1, #bee5eb);
    color: #0c5460;
    border-left: 4px solid #17a2b8;
}
</style>

<div class="row g-0">
    <!-- Sidebar -->
    <div class="col-md-3 col-lg-2">
        <div class="sidebar d-flex flex-column">
            <!-- Logo -->


            <!-- Admin Info -->
            <div class="admin-info-section">
                <div class="admin-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="admin-details">
                    <div class="admin-name"><?php echo htmlspecialchars($admin_name); ?></div>
                    <div class="admin-role"><?php echo htmlspecialchars($admin_role); ?></div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'elections.php' ? 'active' : ''; ?>" href="elections.php">
                            <i class="fas fa-calendar-check"></i>
                            <span class="nav-text">Elections</span>
                        </a>
                    </li>                  
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'partylists.php' ? 'active' : ''; ?>" href="partylists.php">
                            <i class="fas fa-list-alt"></i>
                            <span class="nav-text">Partylist</span>
                        </a>
                    </li>
                    <!--
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'candidates.php' ? 'active' : ''; ?>" href="candidates.php">
                            <i class="fas fa-user-friends"></i>
                            <span class="nav-text">Candidates</span>
                        </a>
                    </li>
-->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'start_election.php' ? 'active' : ''; ?>" href="start_election.php">
                            <i class="fas fa-power-off"></i>
                            <span class="nav-text">Start / Stop</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'results.php' ? 'active' : ''; ?>" href="results.php">
                            <i class="fas fa-chart-bar"></i>
                            <span class="nav-text">Results</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'register_voters.php' ? 'active' : ''; ?>" href="register_voters.php">
                            <i class="fas fa-user-plus"></i>
                            <span class="nav-text">Registered Voters</span>
                        </a>
                    </li>
                    <!--
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                            <i class="fas fa-file-csv"></i>
                            <span class="nav-text">Reports</span>
                        </a>
                    </li>
                    -->
                    <!--
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'contacts.php' ? 'active' : ''; ?>" href="contacts.php">
                            <i class="fas fa-inbox"></i>
                            <span class="nav-text">Contact Logs</span>
                        </a>
                    </li>
-->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>" href="users.php">
                            <i class="fas fa-users-cog"></i>
                            <span class="nav-text">User Management</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Bottom Section -->
            <div class="sidebar-bottom mt-auto">
                <div class="system-status">
                    <div class="status-indicator">
                        <div class="status-dot"></div>
                        <span>System Online</span>
                    </div>
                    <small><?php echo date('M j, Y'); ?></small>
                </div>
                
                <!-- Logout Button -->
                <div class="mt-2">
                    <a class="nav-link text-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="nav-text">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="col-md-9 col-lg-10">
        <div class="main-content">
            <!-- Content Header -->
            <div class="content-header">
                <h1 class="content-title"><?php echo $page_title; ?></h1>
                <p class="content-subtitle">Welcome back, <?php echo htmlspecialchars($admin_name); ?></p>
            </div>

            <!-- Content Body -->
            <div class="content-body">