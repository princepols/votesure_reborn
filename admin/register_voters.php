<?php
require_once __DIR__ . '/../config.php';
session_start();

// require admin login
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php'); 
    exit;
}

$page_title = 'Registered Voters';

// Fetch voters for readonly listing
$rows = $pdo->query('SELECT * FROM registered_voters ORDER BY created_at DESC')->fetchAll();

include __DIR__ . '/header.php';
?>

<div class="card card-modern p-4">
  <h4>Registered Voters</h4>

  <?php if (!empty($_SESSION['flash_message'])): 
    $f = $_SESSION['flash_message'];
    echo '<div class="alert alert-' . htmlspecialchars($f['type']) . '">' . htmlspecialchars($f['message']) . '</div>';
    unset($_SESSION['flash_message']);
  endif; ?>

  <div class="table-responsive mt-3">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>ID</th>
          <th>Student ID</th>
          <th>Name</th>
          <th>Strand</th>
          <th>Year</th>
          <th>Added</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="6" class="text-center text-muted">No registered voters yet.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><?php echo htmlspecialchars($r['id']); ?></td>
          <td><?php echo htmlspecialchars($r['student_id']); ?></td>
          <td><?php echo htmlspecialchars($r['student_name']); ?></td>
          <td><?php echo htmlspecialchars($r['course']); ?></td>
          <td><?php echo htmlspecialchars($r['year_level']); ?></td>
          <td><?php echo htmlspecialchars($r['created_at']); ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>