<?php
require_once __DIR__ . '/../config.php';
session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php'); exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

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

$upload_dir = __DIR__ . '/../uploads/';
$upload_url = app_base_url() . '/uploads/';

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$partylist_id = intval($_GET['partylist_id'] ?? 0);
if (!$partylist_id) {
    header('Location: partylists.php'); exit;
}

// Fetch partylist
$stmt = $pdo->prepare("SELECT * FROM partylists WHERE id=?");
$stmt->execute([$partylist_id]);
$party = $stmt->fetch();
if (!$party) die('Partylist not found');

// SAVE CANDIDATES WITH PHOTOS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_candidates'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF');
    }

    foreach ($POSITIONS as $pos) {
        $name = trim($_POST['candidate'][$pos] ?? '');
        if ($name === '') continue;

        $photo_name = null;

        if (!empty($_FILES['photo']['name'][$pos])) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'][$pos], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];

            if (in_array($ext, $allowed)) {
                $photo_name = uniqid('c_') . '.' . $ext;
                move_uploaded_file(
                    $_FILES['photo']['tmp_name'][$pos],
                    $upload_dir . $photo_name
                );
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO candidates (partylist_id, name, position, photo)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                name = VALUES(name),
                photo = COALESCE(VALUES(photo), photo)
        ");
        $stmt->execute([$partylist_id, $name, $pos, $photo_name]);
    }

    $_SESSION['flash_message'] = ['type'=>'success','message'=>'Candidates saved'];
    header("Location: partylists.php");
    exit;
}

// Fetch existing candidates
$existing = [];
$stmt = $pdo->prepare("SELECT * FROM candidates WHERE partylist_id=?");
$stmt->execute([$partylist_id]);
foreach ($stmt->fetchAll() as $c) {
    $existing[$c['position']] = $c;
}

include __DIR__ . '/header.php';
?>

<div class="container py-4">
    <h2 class="fw-bold text-orange mb-3"><?= h($party['name']) ?></h2>

    <form method="post" enctype="multipart/form-data" class="card shadow-sm p-4">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <?php foreach ($POSITIONS as $pos): 
            $c = $existing[$pos] ?? null;
        ?>
        <div class="row mb-4 align-items-center">
            <div class="col-md-3 fw-bold"><?= $pos ?></div>

            <div class="col-md-4">
                <input type="text"
                       class="form-control"
                       name="candidate[<?= $pos ?>]"
                       value="<?= h($c['name'] ?? '') ?>"
                       placeholder="Candidate name">
            </div>

            <div class="col-md-3">
                <input type="file"
                       class="form-control"
                       name="photo[<?= $pos ?>]"
                       accept="image/*">
            </div>

            <div class="col-md-2 text-center">
                <?php if (!empty($c['photo']) && file_exists($upload_dir.$c['photo'])): ?>
                    <img src="<?= $upload_url.$c['photo'] ?>"
                         class="rounded"
                         style="height:60px;width:60px;object-fit:cover;">
                <?php else: ?>
                    <i class="fas fa-user-circle fa-3x text-muted"></i>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <button class="btn btn-orange w-100 mt-3" name="save_candidates">
            <i class="fas fa-save"></i> Save Candidates
        </button>
    </form>
</div>

<?php include __DIR__ . '/footer.php'; ?>
