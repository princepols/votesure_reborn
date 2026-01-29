<?php
require_once __DIR__ . '/../config.php';
session_start();

// require admin login
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php'); exit;
}

// ensure CSRF token
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = ['type'=>'danger','message'=>'Invalid CSRF token.'];
        header('Location: contacts.php'); exit;
    }
    $cid = intval($_POST['id'] ?? 0);
    try {
        $stmt = $pdo->prepare('DELETE FROM contacts WHERE id = ?');
        $stmt->execute([$cid]);
        $_SESSION['flash_message'] = ['type'=>'success','message'=>'Contact message deleted.'];
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type'=>'danger','message'=>'Error deleting message: ' . $e->getMessage()];
    }
    header('Location: contacts.php'); exit;
}

// Fetch contact messages
$rows = $pdo->query('SELECT * FROM contacts ORDER BY created_at DESC')->fetchAll();

$page_title = 'Contacts';
include __DIR__ . '/header.php';
?>
<div class="card card-modern p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Contact Messages</h4>
    <div class="small-muted">Messages sent by voters</div>
  </div>

  <?php if (!empty($_SESSION['flash_message'])):
    $fm = $_SESSION['flash_message'];
    echo '<div class="alert alert-' . htmlspecialchars($fm['type']) . '">' . htmlspecialchars($fm['message']) . '</div>';
    unset($_SESSION['flash_message']);
  endif; ?>

  <?php if (empty($rows)): ?>
    <div class="alert alert-info">No contact messages yet.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Student ID</th>
            <th>Name</th>
            <th>Strand</th>
            <th>Year</th>
            <th>Message</th>
            <th>Added</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['id']); ?></td>
            <td><?php echo htmlspecialchars($r['student_id']); ?></td>
            <td><?php echo htmlspecialchars($r['name']); ?></td>
            <td><?php echo htmlspecialchars($r['course']); ?></td>
            <td><?php echo htmlspecialchars($r['year_level']); ?></td>
            <td style="max-width:400px; white-space:pre-wrap;"><?php echo htmlspecialchars($r['message']); ?></td>
            <td><?php echo htmlspecialchars($r['created_at']); ?></td>
            <td>
              <form method="post" onsubmit="return confirm('Delete this message?');">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>