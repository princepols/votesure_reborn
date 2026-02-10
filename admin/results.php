<?php
require_once __DIR__ . '/../config.php';
session_start();

/* ================= AUTH ================= */
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

/* ================= HELPER ================= */
if (!function_exists('h')) {
    function h($s) {
        return htmlspecialchars($s ?? '', ENT_QUOTES);
    }
}

/* ================= FIXED POSITIONS ================= */
$POSITIONS = [
    'President',
    'Vice President for Internal Affairs',
    'Vice President for External Affairs',
    'Secretary',
    'Assistant Secretary',
    'Treasurer',
    'Assistant Treasurer',
    'Auditor',
    'Public Relations Officer',
    'Procurement Officer'
];


/* ================= ELECTION ================= */
$eid = $_GET['election_id'] ?? null;
if (!$eid) {
    $e = $pdo->query("SELECT * FROM elections ORDER BY id DESC LIMIT 1")->fetch();
    $eid = $e['id'] ?? null;
}

// SWEETALERT REDIRECT IF NO ELECTION FOUND
if (!$eid) {
    include __DIR__ . '/header.php';
    echo '
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.onload = function() {
            Swal.fire({
                icon: "warning",
                title: "No Election Found",
                text: "No any active or past elections to display.",
                confirmButtonColor: "#e7800a",
                confirmButtonText: "Back to Dashboard"
            }).then((result) => {
                window.location.href = "dashboard.php";
            });
        };
    </script>';
    include __DIR__ . '/footer.php';
    exit;
}

$election_stmt = $pdo->prepare("SELECT * FROM elections WHERE id=?");
$election_stmt->execute([$eid]);
$election = $election_stmt->fetch();

/* ================= FETCH CANDIDATES ================= */
$stmt = $pdo->prepare("
    SELECT c.id, c.name, c.position, c.photo, p.name AS party
    FROM candidates c
    JOIN partylists p ON p.id = c.partylist_id
    WHERE p.election_id = ?
");
$stmt->execute([$eid]);
$candidates_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= STRUCTURE ================= */
$candidates = [];
$candidate_map = [];
foreach ($candidates_raw as $c) {
    $pos = $c['position'];
    $candidates[$pos][$c['id']] = [
        'name' => $c['name'],
        'party' => $c['party'],
        'photo' => $c['photo'],
        'votes' => 0
    ];
    $candidate_map[$c['id']] = [
        'name' => $c['name'],
        'position' => $c['position'],
        'party' => $c['party']
    ];
}

/* ================= TALLY VOTES ================= */
$votes_stmt = $pdo->prepare("SELECT * FROM votes WHERE election_id=?");
$votes_stmt->execute([$eid]);
$votes = $votes_stmt->fetchAll();

$votes_by_student = [];
foreach ($votes as $v) {
    $choices = json_decode($v['choices'], true);
    if (!is_array($choices)) continue;

    $is_associative = false;
    foreach (array_keys($choices) as $key) {
        if (!is_numeric($key)) {
            $is_associative = true;
            break;
        }
    }

    if ($is_associative) {
        foreach ($choices as $position => $candidate_id) {
            if (is_string($position) && isset($candidates[$position][$candidate_id])) {
                $candidates[$position][$candidate_id]['votes']++;
            }
        }
    } else {
        foreach ($choices as $candidate_id) {
            if (isset($candidate_map[$candidate_id])) {
                $position = $candidate_map[$candidate_id]['position'];
                if (isset($candidates[$position][$candidate_id])) {
                    $candidates[$position][$candidate_id]['votes']++;
                }
            }
        }
    }

    $votes_by_student[$v['student_id']] = [
        'choices' => $choices,
        'time' => $v['created_at']
    ];
}

/* ================= SUMMARY ================= */
$total_candidates = count($candidate_map);
$total_voters = count($votes);

$page_title = 'Election Results';
include __DIR__ . '/header.php';
?>

<div class="results-wrapper" style="max-width:1000px; margin:auto; padding:20px;">
<h2 class="text-center mb-4"><?= h($election['title'] ?? 'Results') ?> — Results</h2>

<div class="summary-box" style="display:flex; gap:15px; margin-bottom:25px;">
    <div class="summary-item" style="flex:1; text-align:center; border:1px solid #ddd; padding:15px; border-radius:10px; background:#fff;">
        <div class="num" style="font-size:1.6rem; font-weight:bold; color:#800000;"><?= count($POSITIONS) ?></div>
        <div>Positions</div>
    </div>
    <div class="summary-item" style="flex:1; text-align:center; border:1px solid #ddd; padding:15px; border-radius:10px; background:#fff;">
        <div class="num" style="font-size:1.6rem; font-weight:bold; color:#800000;"><?= $total_candidates ?></div>
        <div>Candidates</div>
    </div>
    <div class="summary-item" style="flex:1; text-align:center; border:1px solid #ddd; padding:15px; border-radius:10px; background:#fff;">
        <div class="num" style="font-size:1.6rem; font-weight:bold; color:#800000;"><?= $total_voters ?></div>
        <div>Total Voters</div>
    </div>
</div>

<?php foreach ($POSITIONS as $pos): ?>
    <?php if (empty($candidates[$pos])) continue; ?>
    <?php $winner_votes = max(array_column($candidates[$pos], 'votes')); ?>

    <div class="card" style="border-radius:10px; margin-bottom:20px;">
        <div class="card-header" style="background:#FF9500; color:#fff; font-weight:600; padding:14px 20px; border-radius:10px 10px 0 0;"><?= h($pos) ?></div>
        <div class="card-body p-0">
            <?php foreach ($candidates[$pos] as $c): 
                $is_winner = ($c['votes'] === $winner_votes && $winner_votes > 0);
            ?>
            <div class="candidate-item" style="display:flex; align-items:center; padding:14px 20px; border-bottom:1px solid #eee; <?= $is_winner ? 'background:#f6fff7; border-left:4px solid #0cae25;' : '' ?>">
                <div class="candidate-info" style="flex:1;">
                    <h6 style="margin:0; font-weight:600;"><?= h($c['name']) ?></h6>
                    <small style="color:#777;"><?= h($c['party']) ?></small>
                    <?php if ($is_winner): ?><div><span class="badge bg-success">Winner</span></div><?php endif; ?>
                </div>
                <div class="vote-box" style="min-width:90px; text-align:center;">
                    <div class="num" style="font-size:1.4rem; font-weight:700; color:#800000;"><?= $c['votes'] ?></div>
                    <small>votes</small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>

<div class="card mt-4">
    <div class="card-header" style="background:#FF9500; color:#fff; font-weight:600;">Summary Report</div>
    <div class="card-body d-flex gap-2">
        <button onclick="window.print()" class="btn btn-dark">
            <i class="fas fa-print me-1"></i> Print Results
        </button>
        <a href="results_download.php?election_id=<?= h($eid) ?>" class="btn btn-success">
            <i class="fas fa-file-csv me-1"></i> Download CSV
        </a>
    </div>
</div>

</div>

<?php include __DIR__ . '/footer.php'; ?>
