<?php
require_once __DIR__ . '/../config.php';
session_start();

$page_title = 'Voter Station';
$message = '';

// Show candidate availability error via GET (e.g., redirected from vote.php)
if (isset($_GET['no_candidates'])) {
    $message = 'There are no candidates available.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');

    if ($student_id === '') {
        $message = 'Please enter or scan your Student ID.';
    } elseif (!preg_match('/^\d{9}$/', $student_id)) {
        $message = 'Student ID must be exactly 9 digits (numbers only).';
    } else {
        // Normalize student id
        $student_id_trim = $student_id;

        // CHECK IF AN ELECTION IS RUNNING - AUTHORITATIVE CHECK
        $chkElection = $pdo->query(
            "SELECT id, title FROM elections WHERE status='running' LIMIT 1"
        )->fetch();

        if (!$chkElection) {
            // CRITICAL FIX: Don't modify ANY database tables when no election is running
            $message = 'No election is currently running.';
        } else {
            $election_id = $chkElection['id'];
            
            // Block if there are no candidates for the running election
            $candCountStmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM candidates c 
                JOIN partylists p ON p.id = c.partylist_id 
                WHERE p.election_id = ?
            ");
            $candCountStmt->execute([$election_id]);
            $candCount = (int)$candCountStmt->fetchColumn();
            if ($candCount === 0) {
                $message = 'There are no candidates available.';
            } else {
                // AUTO-REGISTER: Only register when election is actually running and candidates exist
                $stmt = $pdo->prepare('SELECT * FROM registered_voters WHERE student_id = ?');
                $stmt->execute([$student_id_trim]);
                $reg = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$reg) {
                    try {
                        $pdo->exec('CREATE TABLE IF NOT EXISTS available_voter_ids (id INT PRIMARY KEY)');
                        $pdo->beginTransaction();
                        $chkEmpty = $pdo->prepare('SELECT COUNT(*) AS c FROM registered_voters');
                        $chkEmpty->execute();
                        $countRow = $chkEmpty->fetch(PDO::FETCH_ASSOC);
                        $isEmpty = isset($countRow['c']) && ((int)$countRow['c'] === 0);
                        if ($isEmpty) {
                            $assignId = 1;
                            $pdo->exec('DELETE FROM available_voter_ids');
                        } else {
                            $selGap = $pdo->prepare('SELECT id FROM available_voter_ids ORDER BY id ASC LIMIT 1 FOR UPDATE');
                            $selGap->execute();
                            $gap = $selGap->fetch(PDO::FETCH_ASSOC);
                            if ($gap) {
                                $assignId = (int)$gap['id'];
                                $delGap = $pdo->prepare('DELETE FROM available_voter_ids WHERE id = ?');
                                $delGap->execute([$assignId]);
                            } else {
                                $selMax = $pdo->prepare('SELECT id FROM registered_voters ORDER BY id DESC LIMIT 1 FOR UPDATE');
                                $selMax->execute();
                                $maxRow = $selMax->fetch(PDO::FETCH_ASSOC);
                                $assignId = $maxRow ? ((int)$maxRow['id'] + 1) : 1;
                            }
                        }
                        $ins = $pdo->prepare('INSERT INTO registered_voters (id, student_id, student_name, course, year_level) VALUES (?, ?, NULL, NULL, NULL)');
                        $ins->execute([$assignId, $student_id_trim]);
                        $pdo->commit();
                    } catch (Exception $e) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                    }
                }

                // Ensure students table has a row for this ID - ONLY when election is running AND candidates exist
                $stmt2 = $pdo->prepare('SELECT * FROM students WHERE student_id = ?');
                $stmt2->execute([$student_id_trim]);
                $student = $stmt2->fetch(PDO::FETCH_ASSOC);

                if (!$student) {
                    $ins2 = $pdo->prepare('INSERT INTO students (student_id, name, voted) VALUES (?, NULL, 0)');
                    try {
                        $ins2->execute([$student_id_trim]);
                    } catch (Exception $e) {
                        // ignore duplicate race condition
                    }
                }

                // CHECK votes table for this running election
                $voteChk = $pdo->prepare("SELECT id, created_at FROM votes WHERE student_id = ? AND election_id = ?");
                $voteChk->execute([$student_id_trim, $election_id]);
                $existingVote = $voteChk->fetch();

                if ($existingVote) {
                    $message = 'This student ID has already voted in the current election "' . $chkElection['title'] . '" on ' . $existingVote['created_at'] . '.';
                } else {
                    // All good → proceed
                    $_SESSION['voter_student_id'] = $student_id_trim;
                    header('Location: vote.php');
                    exit;
                }
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<!-- ✅ SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if ($message): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Access Denied',
    text: <?= json_encode($message) ?>,
    confirmButtonColor: '#FF8C00'
});
</script>
<?php endif; ?>

<style>
:root {
    --theme-orange: #FF8C00;
    --theme-dark-orange: #CC5500;
}

body { background-color: #ee7c12ff !important; }

.card-login {
    border: none;
    border-top: 6px solid var(--theme-dark-orange);
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.btn-orange-action {
    background: var(--theme-orange) !important;
    color: white;
    border: none;
    padding: 12px 35px;
    border-radius: 50px;
    font-weight: 600;
}

.btn-orange-action:hover {
    background: var(--theme-dark-orange) !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(255, 140, 0, 0.35);
    color: white;
}

.small-muted { color: #6c757d; }
</style>

<div class="row justify-content-center pt-5">
    <div class="col-md-6">
        <div class="card card-login p-4 shadow-lg mb-4">
            <h4 class="mb-1 text-center fw-bold" style="color: var(--theme-dark-orange);">
                <i class="fas fa-fingerprint me-2"></i> Voter Authentication
            </h4>

            <p class="small-muted text-center mb-3">
                Please enter your Student ID to start voting.<br>
                <span class="text-danger fw-semibold">
                    Student IDs are auto-registered when first used.
                </span>
            </p>

            <form method="post" class="row g-3" id="idForm">
                <div class="col-12">
                    <label class="form-label fw-bold">Student ID</label>
                    <input class="form-control form-control-lg"
                           id="student_id"
                           name="student_id"
                           inputmode="numeric"
                           pattern="\d{9}"
                           maxlength="9"
                           title="Student ID must be exactly 9 digits"
                           autofocus
                           required
                           placeholder="Scan or type your Student ID here...">
                </div>

                <div class="col-12 text-center mt-4">
                    <button class="btn btn-orange-action btn-lg px-5" type="submit">
                        <i class="fas fa-vote-yea me-2"></i> Start Voting
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('student_id').addEventListener('keyup', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('idForm').submit();
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
