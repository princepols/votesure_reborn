<?php
require_once __DIR__ . '/../config.php';
session_start();

// require admin login
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php'); exit;
}

// Helper function (assuming it exists in header.php or config.php, included here for safety)
if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Helper function to format date
if (!function_exists('formatDate')) {
    function formatDate($date) {
        // Format date string (e.g., YYYY-MM-DD HH:MM:SS) to a readable format
        return date('M d, Y', strtotime($date));
    }
}


$page_title = 'Admin Dashboard'; // Updated page title for clarity

// Fetch Summary Data
$totalE = $pdo->query('SELECT COUNT(*) FROM elections')->fetchColumn();
// NOTE: totalS (Registered Students) now reflects the dedicated registered_voters table for accuracy
$totalS = $pdo->query('SELECT COUNT(*) FROM registered_voters')->fetchColumn(); 
$totalV = $pdo->query('SELECT COUNT(*) FROM votes')->fetchColumn();

// Fetch Recent Elections - IMPORTANT: Added start_date and end_date to the query
$recentElections = $pdo->query('SELECT id, title, status, start_date, end_date FROM elections ORDER BY id DESC LIMIT 5')->fetchAll();

include __DIR__ . '/header.php';
?>

<style>
    /* 🎨 Theme Variables: Refined Palette */
    :root {
        --theme-primary: #007bff; /* Added a standard primary color for general use */
        --theme-orange: #ff7b00;
        --theme-dark-orange: #d16600; /* Slightly darker for better contrast */
        --theme-light-orange: #ffe0b2; /* Lighter, but with more saturation for the welcome box */
        --text-muted-dark: #495057; /* Darker muted text for better contrast on cards */
        --card-bg: #ffffff;
        --page-bg: #f8f9fa; /* Lighter background for a cleaner look */
    }

    body { background-color: var(--page-bg); }

    /* --- General Typography & Layout --- */
    .fw-bold-extra { font-weight: 700; }
    .text-primary-orange { color: var(--theme-dark-orange) !important; }


    /* 🏆 Welcome Banner: More Prominent */
    .alert-themed-welcome {
        background-color: var(--theme-light-orange);
        color: var(--theme-dark-orange);
        border: none; /* Removed border, relying on background color */
        border-radius: 12px;
        font-weight: 600;
        padding: 1.5rem; /* Increased padding */
        margin-top: 25px;
        box-shadow: 0 4px 15px rgba(255, 123, 0, 0.2); /* Soft, themed shadow */
        font-size: 1.3rem;
    }

    /* 📊 Main Card Styling */
    .card-main-dashboard {
        border: none;
        border-radius: 15px; /* Softer, larger radius */
        background-color: var(--card-bg);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07); /* Deeper, softer shadow */
        padding: 30px; /* Increased internal padding */
        margin-top: 30px;
    }
    .dashboard-header h4 {
        border-bottom: 2px solid #e9ecef; /* Subtle visual separation */
        padding-bottom: 15px;
        margin-bottom: 25px !important;
    }

    /* 🔢 Summary Boxes: Clean and Interactive */
    .summary-box {
        border: 1px solid #e9ecef; /* Very light border */
        border-left: 5px solid var(--theme-orange); /* Highlight with primary color */
        border-radius: 10px;
        padding: 20px;
        background-color: var(--card-bg);
        transition: all 0.2s;
        height: 100%; /* Ensure boxes in a row are same height */
    }
    .summary-box:hover {
        transform: translateY(-5px); /* More pronounced lift */
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    .summary-box h6 {
        color: var(--text-muted-dark);
        font-weight: 600;
        margin-bottom: 10px;
    }
    .summary-box .num-display {
        font-size: 2.5rem; /* Larger number */
        font-weight: 800;
        color: var(--theme-dark-orange);
        line-height: 1; /* Tighter line height */
    }
    
    /* ⚡ Quick Action Button Styling: Consistent and Clear */
    .btn-action-primary {
        background-color: var(--theme-orange);
        color: white;
        border: none;
        border-radius: 10px;
        padding: 15px 20px;
        font-weight: 600;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center; /* Center content */
        text-align: center;
    }
    .btn-action-primary:hover {
        background-color: var(--theme-dark-orange);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        color: white; /* Important for accessibility */
    }
    .btn-action-outline { /* New style for secondary actions */
        border: 2px solid #ced4da;
        color: var(--text-muted-dark);
        background-color: transparent;
        border-radius: 10px;
        padding: 15px 20px;
        font-weight: 600;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
    }
    .btn-action-outline:hover {
        background-color: #e9ecef;
        border-color: #adb5bd;
        color: var(--text-muted-dark);
        transform: translateY(-1px);
    }
    .btn-action-primary i, .btn-action-outline i {
        font-size: 1.1rem;
        margin-right: 10px;
    }


    /* 📃 Recent Elections List: ENHANCED STYLING */
    .list-group-flush .list-group-item {
        border-right: none;
        border-left: none;
        padding: 15px 0;
        cursor: pointer; /* Suggests clickability */
        transition: background-color 0.15s;
    }
    .list-group-flush .list-group-item:hover {
        background-color: #fcfcfc;
    }
    .list-group-flush .list-group-item:last-child {
        border-bottom: none;
    }

    /* Status Badges */
    .status-badge {
        font-weight: 600; 
        padding: .4em .7em; 
        border-radius: 8px;
        min-width: 90px;
        text-align: center;
    }
    .status-running { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; } /* Light Green */
    .status-ended { background-color: #e9ecef; color: #6c757d; border: 1px solid #dee2e6; } /* Light Gray/Muted */
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <div class="alert alert-themed-welcome text-center">
                👋 Welcome, <b class="text-orange">Administrator!</b>
            </div>

            <div class="card card-main-dashboard">
                <div class="dashboard-header d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0 fw-bold-extra text-primary-orange">
                        <i class="fas fa-chart-bar me-2"></i> System Overview
                    </h4>
                </div>
                
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="summary-box">
                            <h6><i class="fas fa-calendar-alt me-1"></i> Total Elections</h6>
                            <div class="num-display"><?php echo $totalE; ?></div>
                            <small class="text-muted">Currently in the system</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-box">
                            <h6><i class="fas fa-user-friends me-1"></i> Registered Voters</h6>
                            <div class="num-display"><?php echo $totalS; ?></div>
                            <small class="text-muted">Total eligible participants</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-box">
                            <h6><i class="fas fa-check-circle me-1"></i> Total Votes Cast</h6>
                            <div class="num-display"><?php echo $totalV; ?></div>
                            <small class="text-muted">Across all active/past elections</small>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card p-4 border-0 shadow-sm h-100">
                            <h5 class="fw-bold text-secondary mb-4"><i class="fas fa-history me-2"></i> Recent Election Activity</h5>
                            <ul class="list-group list-group-flush">
                                <?php if (empty($recentElections)): ?>
                                    <li class="list-group-item text-center text-muted">
                                        <i class="fas fa-box-open me-1"></i> No elections created yet. Start by managing elections!
                                    </li>
                                <?php else: ?>
                                    <?php foreach($recentElections as $e): ?>
                                    <a href="elections.php?id=<?php echo h($e['id']); ?>" class="list-group-item d-flex justify-content-between align-items-center text-decoration-none text-dark">
                                        
                                        <div class="d-flex flex-column flex-grow-1">
                                            <span class="fw-semibold text-dark mb-1"><?php echo h($e['title']); ?></span>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i> 
                                                <?php echo formatDate($e['start_date']); ?> &mdash; <?php echo formatDate($e['end_date']); ?>
                                            </small>
                                        </div>
                                        
                                        <span class="status-badge <?php echo ($e['status'] === 'running' ? 'status-running' : 'status-ended'); ?>">
                                            <?php echo ucwords(h($e['status'])); ?>
                                        </span>
                                    </a>
                                    <?php endforeach; ?>
                                    <li class="list-group-item text-center">
                                        <a href="elections.php" class="small text-decoration-none text-primary-orange fw-bold">View All Elections <i class="fas fa-arrow-right ms-1"></i></a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="card p-4 border-0 shadow-sm h-100">
                            <h5 class="fw-bold text-secondary mb-4"><i class="fas fa-bolt me-2"></i> Quick Actions</h5>
                            <div class="d-grid gap-3">
                                <a class="btn btn-action-primary" href="elections.php">
                                    <i class="fas fa-plus-circle me-2"></i> Create & Manage Elections
                                </a>
                                <a class="btn btn-action-outline" href="results.php">
                                    <i class="fas fa-user-plus me-2"></i> View Voting Results
                                </a>
                                <a class="btn btn-action-outline" href="partylists.php">
                                    <i class="fas fa-list-alt me-2"></i> Manage Party Lists
                                </a>
                                <a class="btn btn-action-outline" href="reports.php">
                                    <i class="fas fa-file-export me-2"></i> Generate Voting Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>