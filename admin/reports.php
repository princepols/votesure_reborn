<?php
require_once __DIR__ . '/../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();

// --- Export Logic ---
if (isset($_GET['export']) && $_GET['export'] == 'votes') {
    $eid = $_GET['election_id'] ?? null; 
    if (!$eid) die('No election id');
    
    // Ensure the user is logged in before exporting sensitive data (Security check)
    if (empty($_SESSION['admin_id'])) {
        die('Unauthorized export access.');
    }
    
    header('Content-Type: text/csv'); 
    header('Content-Disposition: attachment; filename="votes_election_' . $eid . '.csv"');
    
    $out = fopen('php://output', 'w'); 
    fputcsv($out, ['vote_id', 'election_id', 'student_id', 'choices', 'created_at']);
    
    $stmt = $pdo->prepare('SELECT * FROM votes WHERE election_id = ?'); 
    $stmt->execute([$eid]); 
    
    while ($r = $stmt->fetch()) {
        fputcsv($out, [$r['id'], $r['election_id'], $r['student_id'], $r['choices'], $r['created_at']]);
    }
    exit;
}
// --------------------

// require admin login
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php'); 
    exit;
}

// Helper function (assuming it's not globally available in this scope)
if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}


$elections = $pdo->query('SELECT * FROM elections ORDER BY id DESC')->fetchAll();
$page_title = 'Reports';
include __DIR__ . '/header.php';
?>

<style>
    :root {
        --theme-orange: #ff7b00;
        --theme-dark-orange: #cc4400;
        --theme-light-orange: #fff5eb;
    }
    body { background-color: #fafafa; }
    
    .card-themed {
        border: none;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border-top: 5px solid var(--theme-orange); /* Orange accent border */
    }

    .btn-orange-action {
        background: linear-gradient(135deg, var(--theme-orange), var(--theme-dark-orange));
        color: white; 
        border: none; 
        padding: 8px 20px; 
        border-radius: 6px;
        font-weight: 600; 
        transition: all 0.2s;
        box-shadow: 0 3px 6px rgba(255, 123, 0, 0.3);
    }
    .btn-orange-action:hover {
        transform: translateY(-1px); 
        color: white;
        box-shadow: 0 4px 10px rgba(255, 123, 0, 0.4);
    }

    .form-select:focus {
        border-color: var(--theme-orange);
        box-shadow: 0 0 0 0.25rem rgba(255, 123, 0, 0.25);
    }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-themed p-4">
                <h4 class="fw-bold text-dark mb-4">
                    <i class="fas fa-file-csv me-2" style="color: var(--theme-orange);"></i>
                    Generate Reports
                </h4>
                
                <p class="small text-muted border-bottom pb-3 mb-3">
                    Select an election below to download the raw voting data for auditing purposes.
                </p>

                <?php if (empty($elections)): ?>
                    <div class="alert alert-warning text-center">
                        No elections found to generate reports from.
                    </div>
                <?php else: ?>
                    <form class="row g-3 align-items-end">
                        <div class="col-md-8">
                            <label for="electionSelect" class="form-label fw-semibold">Select Election Data to Export:</label>
                            <select class="form-select" name="election_id" id="electionSelect">
                                <?php foreach ($elections as $e): ?>
                                    <option value="<?php echo $e['id']; ?>"><?php echo h($e['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-orange-action w-100" name="export" value="votes">
                                <i class="fas fa-download me-1"></i> Export Votes (CSV)
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>