<?php
require_once __DIR__ . '/../config.php';
session_start();

/* AUTH  */
if (empty($_SESSION['voter_student_id'])) {
    header('Location: index.php');
    exit;
}

$student_id = $_SESSION['voter_student_id'];

/* ================= ACTIVE ELECTION ================= */
$stmt = $pdo->prepare("SELECT * FROM elections WHERE status='running' LIMIT 1");
$stmt->execute();
$election = $stmt->fetch();
if (!$election) {
    die('No active election');
}

/* ================= PREVENT DOUBLE VOTE ================= */
$chk = $pdo->prepare("SELECT id FROM votes WHERE student_id=? AND election_id=?");
$chk->execute([$student_id, $election['id']]);
if ($chk->fetch()) {
    header('Location: confirm.php?status=already_voted');
    exit;
}

/* ================= NEW OFFICIAL POSITIONS ================= */
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

/* ================= GET CHOICES ================= */
$choices = $_POST['choice'] ?? [];

if (!is_array($choices)) {
    die('Invalid vote data');
}

/* ================= VALIDATION ================= */
foreach ($POSITIONS as $pos) {
    // Accept explicit "0" (abstain) as a valid choice — don't treat string "0" as empty.
    if (!array_key_exists($pos, $choices)) {
        die("Missing vote for: $pos");
    }
}

/* ================= SAVE VOTE ================= */
$stmt = $pdo->prepare("
    INSERT INTO votes (student_id, election_id, choices, created_at)
    VALUES (?, ?, ?, NOW())
");

$stmt->execute([
    $student_id,
    $election['id'],
    json_encode($choices)
]);

/* ================= DONE ================= */
// Mark student as having voted (for quick checks)
$upd = $pdo->prepare('UPDATE students SET voted = 1 WHERE student_id = ?');
try {
    $upd->execute([$student_id]);
} catch (Exception $e) {
    // Non-fatal: still proceed even if update fails
}
header('Location: confirm.php?status=success');
exit;
