<?php
require_once __DIR__ . '/../config.php';
session_start();

// require admin login
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php'); 
    exit;
}

$page_title = 'Registered Voters';

/* ===========================
   DELETE REGISTERED VOTER
   =========================== */
if (isset($_GET['delete'])) {
    $delete_id = (int) $_GET['delete'];

    $target_stmt = $pdo->prepare("SELECT id, student_id, student_name FROM registered_voters WHERE id = ?");
    $target_stmt->execute([$delete_id]);
    $target = $target_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$target) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Registered voter not found.'];
    } else {
        try {
            $pdo->exec('CREATE TABLE IF NOT EXISTS available_voter_ids (id INT PRIMARY KEY)');
            $pdo->beginTransaction();
            $del1 = $pdo->prepare("DELETE FROM registered_voters WHERE id = ?");
            $del1->execute([$delete_id]);
            $del2 = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
            $del2->execute([$target['student_id']]);
            $insGap = $pdo->prepare("INSERT IGNORE INTO available_voter_ids (id) VALUES (?)");
            $insGap->execute([$delete_id]);
            $pdo->commit();

            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Registered voter deleted successfully.'];
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Error deleting registered voter.'];
        }
    }

    header('Location: register_voters.php');
    exit;
}

// Fetch voters for listing
$rows = $pdo->query('SELECT * FROM registered_voters ORDER BY created_at DESC')->fetchAll();

include __DIR__ . '/header.php';
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11  "></script>
<script>
function confirmDelete(id, name) {
    Swal.fire({
        title: 'Delete Registered Voter?',
        html: `<div class="text-center">
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                <h5 class="fw-bold">Are you sure?</h5>
                <p class="text-muted">You are about to delete registered voter:</p>
                <div class="alert alert-danger d-inline-block">
                    <strong>ID:</strong> ${id}<br>
                    <strong>Name:</strong> ${name}
                </div>
                <p class="text-danger small mt-2">This action cannot be undone!</p>
               </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete It',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#E67E22',
        cancelButtonColor: '#6c757d',
        reverseButtons: true,
        customClass: {
            popup: 'border-top-orange',
            confirmButton: 'btn btn-danger px-4',
            cancelButton: 'btn btn-secondary px-4'
        },
        buttonsStyling: false,
        showCloseButton: true,
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Deleting...',
                html: 'Please wait while we delete the registered voter.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            window.location.href = `?delete=${id}`;
        }
    });
}
</script>

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
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" class="text-center text-muted">No registered voters yet.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><?php echo htmlspecialchars($r['id']); ?></td>
          <td><?php echo htmlspecialchars($r['student_id']); ?></td>
          <td><?php echo htmlspecialchars($r['student_name']); ?></td>
          <td><?php echo htmlspecialchars($r['course']); ?></td>
          <td><?php echo htmlspecialchars($r['year_level']); ?></td>
          <td><?php echo htmlspecialchars($r['created_at']); ?></td>
          <td>
            <a href="edit_registered_voter.php?id=<?php echo (int)$r['id']; ?>" class="btn btn-sm btn-primary me-1"><i class="fas fa-edit"></i> Edit</a>
            <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo (int)$r['id']; ?>, '<?php echo htmlspecialchars(addslashes($r['student_name'])); ?>')"><i class="fas fa-trash"></i> Delete</button>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
