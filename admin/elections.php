<?php
require_once __DIR__ . '/../config.php';
session_start();

// require admin login for safety
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php'); exit;
}

// CSRF token setup
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));

$page_title = 'Manage Elections';

// Handle deletion (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && !empty($_POST['id'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = ['type'=>'danger','message'=>'Invalid request (CSRF).'];
        header('Location: elections.php'); exit;
    }
    $id = intval($_POST['id']);
    try {
        $stmt = $pdo->prepare('DELETE FROM elections WHERE id = ?');
        $stmt->execute([$id]);
        $_SESSION['flash_message'] = ['type'=>'success','message'=>'Election deleted.'];
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type'=>'danger','message'=>'Error deleting election: ' . $e->getMessage()];
    }
    header('Location: elections.php'); exit;
}

// Handle creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create']) && !isset($_POST['action'])) {
    $title = trim($_POST['title'] ?? '');
    $descr = trim($_POST['description'] ?? '');
    if ($title === '') {
        $_SESSION['flash_message'] = ['type'=>'warning','message'=>'Election title required.'];
        header('Location: elections.php'); exit;
    }
    $stmt = $pdo->prepare('INSERT INTO elections (title, description, status) VALUES (?, ?, ?)');
    $stmt->execute([$title, $descr, 'draft']);
    $_SESSION['flash_message'] = ['type'=>'success','message'=>'Election created.'];
    header('Location: elections.php'); exit;
}

// fetch
$rows = $pdo->query('SELECT * FROM elections ORDER BY id DESC')->fetchAll();

include __DIR__ . '/header.php';
?>

<style>
    :root {
        --theme-orange: #ff7b00;
        --theme-dark-orange: #cc4400;
        --theme-light-orange: #fff5eb;
        --theme-text: #2c2c2c;
    }

    .text-orange { color: var(--theme-dark-orange) !important; }

    .btn-orange {
        /* Base Styles */
        background-color: var(--theme-orange);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        
        /* Quick Action Styles for size and layout */
        padding: 15px;
        display: flex;
        align-items: center;
        text-align: left;
        justify-content: center;
        
        /* Animation and Shadow */
        box-shadow: 0 4px 6px rgba(255, 123, 0, 0.2);
        transition: transform 0.2s, box-shadow 0.2s, background-color 0.2s;
    }

    .btn-orange i {
        font-size: 1.2rem;
        margin-right: 10px;
    }
    
    .btn-orange:hover {
        background-color: var(--theme-dark-orange);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(255, 123, 0, 0.4);
    }

    .card-header-orange {
        background-color: var(--theme-light-orange);
        border-bottom: 2px solid var(--theme-orange);
        color: var(--theme-dark-orange);
    }

    .election-card {
        border: 1px solid #eee;
        border-left: 5px solid var(--theme-orange);
        transition: all 0.3s ease;
        background: white;
        border-radius: 8px;
    }

    .election-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        border-left-color: var(--theme-dark-orange);
    }

    .form-control:focus {
        border-color: var(--theme-orange);
        box-shadow: 0 0 0 0.25rem rgba(255, 123, 0, 0.25);
    }
    
    .badge-status {
        background-color: var(--theme-light-orange);
        color: var(--theme-dark-orange);
        border: 1px solid var(--theme-orange);
    }
</style>

<!-- Include SweetAlert library -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function confirmDelete(electionId, electionTitle) {
    Swal.fire({
        title: 'Delete Election?',
        html: 'Delete "<strong>' + electionTitle + '</strong>" and all related party lists, candidates, and votes?</div>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ff7b00',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        customClass: {
            popup: 'swal2-popup-custom',
            confirmButton: 'swal2-confirm-custom',
            cancelButton: 'swal2-cancel-custom'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Find and submit the form
            const form = document.querySelector('form[data-election-id="' + electionId + '"]');
            if (form) {
                form.submit();
            }
        }
    });
}
</script>

<style>
    /* Custom SweetAlert theme */
    .swal2-popup-custom {
        border-radius: 12px;
        border-left: 5px solid var(--theme-orange);
    }
    
    .swal2-confirm-custom {
        background-color: var(--theme-orange) !important;
        border: none !important;
        padding: 10px 24px !important;
        border-radius: 8px !important;
        font-weight: 600 !important;
        transition: background-color 0.2s !important;
    }
    
    .swal2-confirm-custom:hover {
        background-color: var(--theme-dark-orange) !important;
    }
    
    .swal2-cancel-custom {
        border-radius: 8px !important;
        padding: 10px 24px !important;
        font-weight: 600 !important;
    }
    
    .swal2-icon.swal2-warning {
        border-color: var(--theme-orange);
        color: var(--theme-orange);
    }
</style>

<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-orange mb-0"><i class="fas fa-vote-yea me-2"></i>Manage Elections</h2>
            <p class="text-muted mb-0">Create and oversee election events</p>
        </div>
    </div>

    <div class="card shadow-sm mb-5 border-0">
        <div class="card-header card-header-orange py-3">
            <h5 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i>Create New Election</h5>
        </div>
        <div class="card-body p-4">
            <form method="post" class="row g-3 align-items-center">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="col-md-5">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="elecTitle" name="title" placeholder="Election Title" required>
                        <label for="elecTitle">Election Title</label>
                    </div>
                </div>
                
                <div class="col-md-5">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="elecDesc" name="description" placeholder="Description">
                        <label for="elecDesc">Short Description</label>
                    </div>
                </div>
                
                <div class="col-md-2 d-grid">
                    <button class="btn btn-orange py-3" name="create">
                        <i class="fas fa-paper-plane me-1"></i> Create
                    </button>
                </div>
            </form>
        </div>
    </div>

    <h5 class="text-muted mb-3 ps-1">Existing Elections</h5>
    
    <?php if (count($rows) > 0): ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach($rows as $r): ?>
                <div class="election-card p-3 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center">
                        
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-3 mb-1">
                                <h5 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($r['title']); ?></h5>
                                <span class="badge rounded-pill badge-status text-uppercase" style="font-size: 0.7rem;">
                                    <?php echo htmlspecialchars($r['status']); ?>
                                </span>
                            </div>
                            <div class="text-muted small">
                                <?php echo $r['description'] ? htmlspecialchars($r['description']) : 'No description provided'; ?>
                            </div>
                        </div>

                        <div class="d-flex gap-2 align-items-center">
                            <a class="btn btn-sm btn-outline-secondary" href="edit_election.php?id=<?php echo $r['id']; ?>">
                                <i class="fas fa-edit"></i> Edit
                            </a>

                            <form method="post" data-election-id="<?php echo $r['id']; ?>" class="m-0">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="btn btn-sm btn-outline-danger" type="button" onclick="confirmDelete(<?php echo $r['id']; ?>, '<?php echo addslashes(htmlspecialchars($r['title'])); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-inbox fa-3x mb-3 text-orange" style="opacity: 0.5;"></i>
            <p>No elections found. Create one above to get started.</p>
        </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/footer.php'; ?>