<?php
require_once __DIR__ . '/../config.php';
session_start(); // Assuming admin login check happens here or in header.php

// Helper function to safely output HTML
if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$id = $_GET['id'] ?? null;

// Require Election ID to proceed
if (!$id) { 
    header('Location: elections.php'); 
    exit; 
}

// 1. Handle Form Submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation/sanitization (assuming h() is available)
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'draft';

    $stmt = $pdo->prepare('UPDATE elections SET title=?, description=?, status=? WHERE id=?');
    $stmt->execute([$title, $description, $status, $id]);
    
    // Redirect after successful update
    header('Location: elections.php?status=updated'); 
    exit;
}


// 2. Fetch Existing Election Data
$stmt = $pdo->prepare('SELECT * FROM elections WHERE id=?'); 
$stmt->execute([$id]); 
$e = $stmt->fetch();

// Redirect if election not found
if (!$e) {
    header('Location: elections.php?status=notfound'); 
    exit;
}

$page_title = 'Edit Election: ' . $e['title'];
include __DIR__ . '/header.php';
?>

<style>
    /* Theme Variables */
    :root {
        --theme-orange: #ff7b00;
        --theme-dark-orange: #cc4400;
        --theme-light-orange: #fff5eb;
    }

    /* Card Styling */
    .card-edit {
        border: none;
        border-top: 6px solid var(--theme-dark-orange);
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }
    
    /* Input/Select Focus */
    .form-control:focus, .form-select:focus {
        border-color: var(--theme-orange);
        box-shadow: 0 0 0 0.25rem rgba(255, 123, 0, 0.25);
    }

    /* Save Button Styling */
    .btn-orange-save {
        background: linear-gradient(135deg, var(--theme-orange), var(--theme-dark-orange));
        color: white;
        border: none;
        font-weight: 600;
        padding: 8px 30px;
        transition: transform 0.2s;
    }
    .btn-orange-save:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(255, 123, 0, 0.4);
        color: white;
    }

    /* Back Button Style */
    .btn-outline-back {
        color: #6c757d;
        border-color: #ced4da;
        font-weight: 500;
    }
    .btn-outline-back:hover {
        background-color: #f8f9fa;
        color: #495057;
    }

    .form-label-custom {
        font-weight: 600;
        color: #495057;
    }

    /* 1. Base Select Element Styling (The visible box) */
    .form-select {
        border: 1px solid #ced4da;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        /* Ensure the current value text is black/dark */
        color: #212529; 
    }
    
    /* 2. Focus State (Outline and Shadow of the visible box) */
    .form-select:focus {
        border-color: var(--theme-orange); /* Orange Border */
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(255, 123, 0, 0.4); /* Darker Orange Glow */
    }

    /* 3. The Options (The actual dropdown list content) */
    /* NOTE: Styling the individual *options* in the dropped-down list (hover/background) is severely limited with native HTML <select>.
       The following CSS targets the *selected* item's appearance where supported (mainly Firefox). */

    /* Apply custom theme colors to the selected option for browsers that allow it */
    .form-select option:checked {
        background-color: var(--theme-dark-orange); /* Dark Orange background */
        color: white; /* White text */
        font-weight: 600;
    }
    
    /* Optional: Style options based on their status value for better admin view */
    .form-select option[value="draft"] { color: #6c757d; }
    .form-select option[value="running"] { 
        color: #198754; /* Success green for running */
        font-weight: 600;
    }
    .form-select option[value="closed"] { color: #dc3545; } /* Danger red for closed */
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h3 class="mb-4" style="color: var(--theme-dark-orange);"><i class="fas fa-edit me-2"></i> Edit Election</h3>
            
            <div class="card card-edit p-4">
                <h5 class="mb-3 text-secondary">Editing: <?= h($e['title']) ?></h5>
                
                <form method="post" class="row g-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label form-label-custom">Election Title</label>
                        <input class="form-control" id="title" name="title" value="<?php echo h($e['title']); ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="description" class="form-label form-label-custom">Description</label>
                        <input class="form-control" id="description" name="description" value="<?php echo h($e['description']); ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label for="status" class="form-label form-label-custom">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="draft"<?php if($e['status']=='draft') echo ' selected'; ?>>Draft</option>
                            <option value="running"<?php if($e['status']=='running') echo ' selected'; ?>>Running</option>
                            <option value="closed"<?php if($e['status']=='closed') echo ' selected'; ?>>Closed</option>
                        </select>
                    </div>

                    <div class="col-12 d-flex justify-content-between pt-4">
                        <a href="elections.php" class="btn btn-outline-back">
                            <i class="fas fa-arrow-left me-1"></i> Back to Elections
                        </a>
                        <button class="btn btn-orange-save" type="submit">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>