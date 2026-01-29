<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

/* ================= AUTH ================= */
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php'); 
    exit;
}

/* ================= HANDLE ACTION ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['election_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($id > 0) {
        if ($action === 'start') {
            // Only ONE election can run
            $pdo->query("UPDATE elections SET status='draft'");
            $stmt = $pdo->prepare("UPDATE elections SET status='running' WHERE id=?");
            $stmt->execute([$id]);
        }
        elseif ($action === 'close') {
            $stmt = $pdo->prepare("UPDATE elections SET status='closed' WHERE id=?");
            $stmt->execute([$id]);
        }
    }
    header('Location: start_election.php');
    exit;
}

/* ================= FETCH DATA ================= */
$elections = $pdo->query("SELECT * FROM elections ORDER BY id DESC")->fetchAll();

$page_title = 'Start / Stop Election';
include __DIR__ . '/header.php';
?>

<style>
:root {
    --theme-orange:#ff7b00;
    --theme-dark-orange:#cc4400;
}
.text-orange{color:var(--theme-dark-orange)!important;}

.badge-running{
    background:#d4edda;color:#155724;border:1px solid #c3e6cb;
    animation:pulse-green 2s infinite;
}
.badge-closed{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;}
.badge-draft{background:#e2e3e5;color:#383d41;border:1px solid #d6d8db;}

.btn-orange{
    background:linear-gradient(135deg,var(--theme-orange),var(--theme-dark-orange));
    color:white;border:none;
}
.btn-orange:hover{
    background:linear-gradient(135deg,var(--theme-dark-orange),var(--theme-orange));
    color:white;
}

.election-item{
    border-left:4px solid transparent;
    transition:all .2s;
}
.election-item:hover{
    transform:translateX(5px);
    background:#fffaf5;
}
.status-running{border-left-color:#28a745;background:#f0fff4;}
.status-closed{border-left-color:#dc3545;}
.status-draft{border-left-color:var(--theme-orange);}

@keyframes pulse-green{
    0%{box-shadow:0 0 0 0 rgba(40,167,69,.4)}
    70%{box-shadow:0 0 0 6px rgba(40,167,69,0)}
    100%{box-shadow:0 0 0 0 rgba(40,167,69,0)}
}
</style>

<div class="container py-4">

    <div class="mb-4">
        <h2 class="fw-bold text-orange mb-1">
            <i class="fas fa-power-off me-2"></i>Election Control
        </h2>
        <p class="text-muted mb-0">Manage which election is currently active.</p>
    </div>

    <div class="alert alert-warning shadow-sm d-flex align-items-center">
        <i class="fas fa-exclamation-circle fa-2x me-3 text-orange"></i>
        <div>
            <strong>Important:</strong> Only one election can be <b>Running</b> at a time.
            Starting another election will automatically stop the current one.
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0 text-secondary">Available Elections</h5>
        </div>

        <div class="list-group list-group-flush">
        <?php if (empty($elections)): ?>
            <div class="list-group-item text-center p-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                <p>No elections found.</p>
            </div>
        <?php else: ?>
            <?php foreach ($elections as $e):
                $rowClass = 'status-draft';
                $badge = 'badge-draft';
                if ($e['status']==='running'){ $rowClass='status-running'; $badge='badge-running'; }
                if ($e['status']==='closed'){ $rowClass='status-closed'; $badge='badge-closed'; }
            ?>
            <div class="list-group-item election-item <?= $rowClass ?> p-3">
                <div class="row align-items-center">

                    <div class="col-md-6">
                        <div class="fw-bold h5 mb-1"><?= h($e['title']) ?></div>
                        <div class="small text-muted">
                            Status:
                            <span class="badge <?= $badge ?> text-uppercase"><?= h($e['status']) ?></span>
                            &nbsp;•&nbsp; ID #<?= h($e['id']) ?>
                        </div>
                    </div>

                    <div class="col-md-3 small text-muted">
                        <?= $e['description'] ? h(substr($e['description'],0,50)).'...' : 'No description' ?>
                    </div>

                    <div class="col-md-3 text-md-end mt-2 mt-md-0">
                        <?php if ($e['status']==='running'): ?>
                            <button type="button"
                                    class="btn btn-outline-danger btn-sm fw-bold action-btn"
                                    data-action="close"
                                    data-id="<?= $e['id'] ?>"
                                    data-title="<?= h($e['title']) ?>">
                                <i class="fas fa-stop-circle me-1"></i> Close Voting
                            </button>

                        <?php elseif ($e['status']==='closed'): ?>
                            <button type="button"
                                    class="btn btn-secondary btn-sm action-btn"
                                    data-action="start"
                                    data-id="<?= $e['id'] ?>"
                                    data-title="<?= h($e['title']) ?>">
                                <i class="fas fa-redo me-1"></i> Re-Open
                            </button>

                        <?php else: ?>
                            <button type="button"
                                    class="btn btn-orange btn-sm fw-bold action-btn"
                                    data-action="start"
                                    data-id="<?= $e['id'] ?>"
                                    data-title="<?= h($e['title']) ?>">
                                <i class="fas fa-play-circle me-1"></i> Start Election
                            </button>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.action-btn').forEach(btn=>{
    btn.addEventListener('click',()=>{
        const action = btn.dataset.action;
        const id = btn.dataset.id;
        const title = btn.dataset.title;

        let msg = '';
        if(action==='start'){
            msg = `<strong>${title}</strong><br><br>
                   Starting this election will stop any currently running elections.<br><br>
                   Do you want to continue?`;
        }else{
            msg = `<strong>${title}</strong><br><br>
                   This will immediately stop voting for this election.<br><br>
                   Are you sure?`;
        }

        document.getElementById('confirmMessage').innerHTML = msg;
        document.getElementById('confirmElectionId').value = id;
        document.getElementById('confirmAction').value = action;

        new bootstrap.Modal(document.getElementById('actionConfirmModal')).show();
    });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
