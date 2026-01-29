<?php
// admin/results_download.php
require_once __DIR__ . '/../config.php';
session_start();

if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$eid = isset($_GET['election_id']) ? intval($_GET['election_id']) : 0;
if ($eid <= 0) {
    http_response_code(400);
    exit('Invalid election id');
}

// Query: get partylist, candidate, position, votes (robust counting)
$sql = "
SELECT
  p.name AS partylist,
  c.id AS candidate_id,
  c.name AS candidate_name,
  c.position,
  (
    SELECT COUNT(*) FROM votes v
    WHERE v.election_id = ?
      AND (
        JSON_CONTAINS(v.choices, JSON_ARRAY(c.id))
        OR JSON_CONTAINS(v.choices, JSON_ARRAY(CAST(c.id AS CHAR)))
        OR JSON_SEARCH(v.choices, 'one', CAST(c.id AS CHAR)) IS NOT NULL
      )
  ) AS votes
FROM candidates c
LEFT JOIN partylists p ON c.partylist_id = p.id
WHERE p.election_id = ?
ORDER BY p.id, c.id
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$eid, $eid]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="election_results_' . $eid . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Partylist', 'Candidate ID', 'Candidate Name', 'Position', 'Votes']);

foreach ($rows as $r) {
    fputcsv($out, [
        $r['partylist'],
        $r['candidate_id'],
        $r['candidate_name'],
        $r['position'],
        $r['votes']
    ]);
}
fclose($out);
exit;
