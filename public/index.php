<?php
require_once __DIR__ . '/../config.php';
session_start();

$page_title = 'Voter Station';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');

    if ($student_id === '') {
        $message = 'Please enter or scan your Student ID.';
    } else {
        // Normalize student id
        $student_id_trim = $student_id; // Keep as-is for exact matching

        // AUTO-REGISTER: ensure registered_voters has this student_id
        $stmt = $pdo->prepare('SELECT * FROM registered_voters WHERE student_id = ?');
        $stmt->execute([$student_id_trim]);
        $reg = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reg) {
            // Insert minimal record (name/course/year left null for user to complete)
            $ins = $pdo->prepare('INSERT INTO registered_voters (student_id, student_name, course, year_level) VALUES (?, NULL, NULL, NULL)');
            try {
                $ins->execute([$student_id_trim]);
            } catch (Exception $e) {
                // ignore duplicate race condition
            }
        }

        // Ensure students table has a row for this ID
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
            $student = ['student_id' => $student_id_trim, 'voted' => 0];
        }

        // Refresh student row in case it was created by another process
        $stmt2->execute([$student_id_trim]);
        $student = $stmt2->fetch(PDO::FETCH_ASSOC);

        // CHECK IF AN ELECTION IS RUNNING (use authoritative check first)
        $chkElection = $pdo->query(
            "SELECT id, title FROM elections WHERE status='running' LIMIT 1"
        )->fetch();

        if (!$chkElection) {
            $message = 'No election is currently running.';
        } else {
            // CHECK votes table for this running election (authoritative)
            $election_id = $chkElection['id'];
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
