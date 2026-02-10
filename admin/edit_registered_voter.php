<?php
require_once __DIR__ . '/../config.php';
session_start();

// require admin login
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Edit Registered Voter';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM registered_voters WHERE id = ?");
$stmt->execute([$id]);
$voter = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$voter) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Registered voter not found.'];
    header('Location: register_voters.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $student_name = trim($_POST['student_name'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year_level = trim($_POST['year_level'] ?? '');

    if ($student_id === '') {
        $error = 'Student ID is required.';
    } elseif ($student_name === '') {
        $error = 'Name is required.';
    } else {
        $update = $pdo->prepare("UPDATE registered_voters SET student_id = ?, student_name = ?, course = ?, year_level = ? WHERE id = ?");
        $update->execute([$student_id, $student_name, $course, $year_level, $id]);

        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Registered voter updated successfully.'];
        header('Location: register_voters.php');
        exit;
    }
}

include __DIR__ . '/header.php';
?>

<div class="card card-modern p-4">
  <h4>Edit Registered Voter</h4>

  <?php if (!empty($error)): ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: <?php echo json_encode($error); ?>,
            confirmButtonColor: '#E67E22'
        });
    });
    </script>
  <?php endif; ?>

  <form method="post" action="">
    <div class="mb-3">
      <label for="student_id" class="form-label">Student ID</label>
      <input type="text" class="form-control" id="student_id" name="student_id" required value="<?php echo htmlspecialchars($voter['student_id']); ?>">
    </div>

    <div class="mb-3">
      <label for="student_name" class="form-label">Name</label>
      <input type="text" class="form-control" id="student_name" name="student_name" required value="<?php echo htmlspecialchars($voter['student_name']); ?>">
    </div>

    <div class="mb-3">
      <label for="course" class="form-label">Strand/Course</label>
      <input type="text" class="form-control" id="course" name="course" value="<?php echo htmlspecialchars($voter['course']); ?>">
    </div>

    <div class="mb-3">
      <label for="year_level" class="form-label">Year</label>
      <input type="text" class="form-control" id="year_level" name="year_level" value="<?php echo htmlspecialchars($voter['year_level']); ?>">
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="register_voters.php" class="btn btn-secondary ms-2">Cancel</a>
  </form>
</div>

<?php include __DIR__ . '/footer.php'; ?>
