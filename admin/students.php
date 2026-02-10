<?php
// 1. Configuration and Session Setup
require_once __DIR__ . '/../config.php';
session_start();

// Check for admin authentication
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// 2. Constants and Input Validation Helpers
define('MAX_STUDENT_ID_LENGTH', 50);
define('MAX_NAME_LENGTH', 100);     // Example max length

/**
 * Basic input sanitization and validation
 * @param string $input
 * @return string|false Sanitized string or false if validation fails
 */
function validate_input($input, $type = 'string') {
    $input = trim($input);

    if (empty($input)) {
        return false;
    }

    // Basic length checks
    if ($type === 'student_id' && strlen($input) > MAX_STUDENT_ID_LENGTH) {
        return false;
    }
    if ($type === 'name' && strlen($input) > MAX_NAME_LENGTH) {
        return false;
    }

    // No specific format required for student_id anymore

    // Sanitize output for use in database (though PDO prepared statements handle escaping)
    return $input;
}

// 3. Handle Add Student Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $student_id = validate_input($_POST['student_id'] ?? '', 'student_id');
    $name = validate_input($_POST['name'] ?? '', 'name');

    if (!$student_id) {
        $error = "Student ID is required.";
    } elseif (!$name) {
        $error = "Full name is required.";
    } else {
        // Use a function to check for existing student before inserting (optional but clearer)
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_id = ?");
        $stmt_check->execute([$student_id]);
        if ($stmt_check->fetchColumn() > 0) {
            $error = "Error: This student ID is already registered.";
        } else {
            // Transaction for atomicity is overkill for a single insert, but good practice for multiple operations
            $stmt = $pdo->prepare("INSERT INTO students (student_id, name) VALUES (?, ?)");
            
            try {
                $stmt->execute([$student_id, $name]);
                // Set a session flash message for Post/Redirect/Get (PRG) pattern
                $_SESSION['success_message'] = "Student ID " . $student_id . " registered successfully!";
                
                // Redirect to avoid form resubmission on refresh
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            } catch (PDOException $e) {
                // Log the actual error but show a friendly message
                error_log("Database Error on student insert: " . $e->getMessage());
                $error = "A database error occurred. Could not register student.";
            }
        }
    }
}

// 4. Handle Flash Messages (for PRG pattern)
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// 5. Fetch all students
try {
    // Use prepared statement even for simple selects if filtering is needed later, but query is fine here
    $stmt = $pdo->query("SELECT * FROM students ORDER BY id DESC");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error on student fetch: " . $e->getMessage());
    $error = "Could not load students data.";
    $students = []; // Ensure $students is defined even on error
}

$page_title = 'Registered Students';
include __DIR__ . '/header.php';
?>

<div class="row">
    <div class="col-md-10 offset-md-1">
        
        <?php if ($success || $error): ?>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                const msg = <?php echo json_encode($error ?: $success); ?>;
                const isError = <?php echo json_encode((bool)$error); ?>;
                Swal.fire({
                    icon: isError ? 'error' : 'success',
                    title: isError ? 'Error' : 'Success',
                    text: msg,
                    confirmButtonColor: '#E67E22'
                });
            });
            </script>
        <?php endif; ?>

        <div class="card card-modern p-4 mb-4">
            <h5 class="mb-3">➕ Add New Student</h5>
            <form method="POST" action="">
                <div class="form-group mb-3">
                    <label for="student_id">Student ID:</label>
                    <input type="text" class="form-control" id="student_id" name="student_id" required maxlength="<?= MAX_STUDENT_ID_LENGTH ?>">
                </div>
                <div class="form-group mb-3">
                    <label for="name">Full Name:</label>
                    <input type="text" class="form-control" id="name" name="name" required maxlength="<?= MAX_NAME_LENGTH ?>">
                </div>
                <button type="submit" class="btn btn-primary">Register Student</button>
            </form>
        </div>
        
        <div class="card card-modern p-4">
            <h5 class="mb-3">Registered Students (<?= count($students) ?>)</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Voted</th>
                            <th>Registered At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No students registered yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td><?= (int)$s['id'] ?></td>
                                    <td><?= htmlspecialchars($s['student_id']) ?></td>
                                    <td><?= htmlspecialchars($s['name']) ?></td>
                                    <td><?= $s['voted'] ? '✅ Yes' : '❌ No' ?></td>
                                    <td><?= htmlspecialchars($s['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
