<?php
require_once __DIR__ . '/../config.php';
session_start();

// login
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$page_title = 'Partylist';

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = ['type'=>'danger','message'=>'Invalid CSRF token.'];
        header('Location: partylists.php'); exit;
    }
    $pid = intval($_POST['id'] ?? 0);
    try {
        $pdo->prepare('DELETE FROM partylists WHERE id = ?')->execute([$pid]);
        $_SESSION['flash_message'] = ['type'=>'success','message'=>'Partylist deleted.'];
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type'=>'danger','message'=>'Error deleting partylist: ' . $e->getMessage()];
    }
    header('Location: partylists.php'); exit;
}

// Handle creation
$elections = $pdo->query('SELECT * FROM elections ORDER BY id DESC')->fetchAll();
$selected = $_GET['election_id'] ?? ($elections[0]['id'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create']) && isset($_POST['name'])) {
    $eid = intval($_POST['election_id']);
    $name = trim($_POST['name']);
    if ($name==='') {
        $_SESSION['flash_message'] = ['type'=>'warning','message'=>'Partylist name required.'];
        header('Location: partylists.php?election_id=' . $eid); exit;
    }
    $pdo->prepare('INSERT INTO partylists (election_id, name, abbreviation) VALUES (?, ?, "")')->execute([$eid, $name]);
    $_SESSION['flash_message'] = ['type'=>'success','message'=>'Partylist added.'];
    header('Location: partylists.php?election_id=' . $eid); exit;
}

// Fetch lists
$lists = [];
if ($selected) {
    $stmt = $pdo->prepare('SELECT * FROM partylists WHERE election_id = ?'); $stmt->execute([$selected]); $lists = $stmt->fetchAll();
}

include __DIR__ . '/header.php';
?>

<style>
:root {
    --theme-orange: #ff7b00;
    --theme-dark-orange: #cc4400;
    --theme-light-orange: #fff5eb;
}
.text-orange { color: var(--theme-dark-orange) !important; }

.btn-orange {
    background-color: var(--theme-orange);
    border-radius: 8px;
    padding: 15px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    border: none;
    transition: all 0.2s;
}
.btn-orange i { font-size: 1.2rem; margin-right: 10px; }
.btn-orange:hover {
    background-color: var(--theme-dark-orange);
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(255,123,0,0.4);
}

.card-header-orange {
    background-color: var(--theme-light-orange);
    border-bottom: 2px solid var(--theme-orange);
    color: var(--theme-dark-orange);
}

.party-card {
    border-left: 4px solid var(--theme-orange);
    background: white;
    transition: transform 0.2s;
}
.party-card:hover {
    transform: translateX(5px);
    border-left-color: var(--theme-dark-orange);
    background-color: #fffaf5;
}

.form-select-lg-custom {
    border: 2px solid #eee;
    font-weight: 600;
    color: var(--theme-dark-orange);
}
.form-select-lg-custom:focus {
    border-color: var(--theme-orange);
    box-shadow: none;
}

/* SweetAlert Custom Styles */
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

<!-- Include SweetAlert library -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function confirmDeletePartylist(partylistId, partylistName) {
    Swal.fire({
        title: 'Delete Partylist?',
        html: '</strong><br>Delete "<strong>' + partylistName + '</strong>" and its candidates?</div>',
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
            const form = document.querySelector('form[data-partylist-id="' + partylistId + '"]');
            if (form) {
                form.submit();
            }
        }
    });
}
</script>

<div class="container py-4">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold text-orange mb-0"><i class="fas fa-users me-2"></i>Partylist</h2>
            <p class="text-muted mb-0">Manage political parties for specific elections</p>
        </div>

        <form method="get" class="flex-grow-1 flex-md-grow-0" style="min-width: 300px;">
            <div class="input-group">
                <span class="input-group-text bg-white text-orange border-end-0"><i class="fas fa-filter"></i></span>
                <select class="form-select form-select-lg-custom border-start-0" name="election_id" onchange="this.form.submit()">
                    <?php foreach ($elections as $el): ?>
                    <option value="<?php echo $el['id']; ?>"<?php if($selected==$el['id']) echo ' selected'; ?>>
                        <?php echo htmlspecialchars($el['title']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header card-header-orange py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-plus me-2"></i>Add Party</h5>
                </div>
                <div class="card-body p-4">
                    <?php if($selected): ?>
                        <form method="post" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="election_id" value="<?php echo htmlspecialchars($selected); ?>">
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="partyName" name="name" placeholder="Party Name" required>
                                    <label for="partyName">Party Name</label>
                                </div>
                            </div>
                            <div class="col-12 d-grid pt-2">
                                <button class="btn btn-orange py-2 fw-bold" name="create">
                                    Add to List <i class="fas fa-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">Please create an election first.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <?php if (empty($lists)): ?>
                <div class="card border-dashed shadow-sm p-5 text-center text-muted h-100 d-flex align-items-center justify-content-center">
                    <div>
                        <i class="fas fa-users-slash fa-3x mb-3 text-orange" style="opacity: 0.5;"></i>
                        <h5>No Party Lists Found</h5>
                        <p>Use the form on the left to add parties to the selected election.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($lists as $l): ?>
                    <div class="card party-card shadow-sm">
                        <div class="card-body d-flex justify-content-between align-items-center p-3">
                            <div>
                                <h5 class="mb-0 fw-bold text-dark"><?php echo htmlspecialchars($l['name']); ?></h5>
                            </div>

                            <div class="d-flex gap-2 align-items-center">
                                <a class="btn btn-sm btn-outline-dark" href="candidates.php?partylist_id=<?php echo $l['id']; ?>">
                                    <i class="fas fa-user-tie me-1"></i> Manage Partylist
                                </a>
                                
                                <form method="post" data-partylist-id="<?php echo $l['id']; ?>" class="m-0">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="id" value="<?php echo $l['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button class="btn btn-sm btn-outline-danger" type="button" onclick="confirmDeletePartylist(<?php echo $l['id']; ?>, '<?php echo addslashes(htmlspecialchars($l['name'])); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>