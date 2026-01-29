<?php
require_once __DIR__ . '/../config.php';
session_start();

if (empty($_SESSION['voter_student_id'])) {
    header('Location: index.php'); exit;
}

$student_id = $_SESSION['voter_student_id'];

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

// Active election
$stmt = $pdo->prepare("SELECT * FROM elections WHERE status='running' LIMIT 1");
$stmt->execute();
$election = $stmt->fetch();
if (!$election) die('No active election');

// Already voted check
$chk = $pdo->prepare("SELECT id FROM votes WHERE student_id=? AND election_id=?");
$chk->execute([$student_id, $election['id']]);
if ($chk->fetch()) {
    header('Location: confirm.php?status=already_voted'); exit;
}

// Fetch candidates
$stmt = $pdo->prepare("
    SELECT c.id, c.name, c.photo, c.position, p.name AS party
    FROM candidates c
    JOIN partylists p ON c.partylist_id = p.id
    WHERE p.election_id = ?
ORDER BY FIELD(c.position,
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
)
");
$stmt->execute([$election['id']]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by position
$byPosition = [];
foreach ($rows as $r) {
    $byPosition[$r['position']][] = $r;
}

$page_title = 'Cast Your Vote';
include __DIR__ . '/../includes/header.php';

// --- PROFILE COMPLETION FLOW ---
// Fetch registered_voters row
$reg_stmt = $pdo->prepare('SELECT * FROM registered_voters WHERE student_id = ?');
$reg_stmt->execute([$student_id]);
$reg = $reg_stmt->fetch(PDO::FETCH_ASSOC);

// Normalize stored course into main + substrand (for TVL)
$reg_course_main = $reg_substrand = '';
if (!empty($reg['course'])) {
    if (stripos($reg['course'], 'TVL-') === 0) {
        $reg_course_main = 'TVL';
        $reg_substrand = substr($reg['course'], 4);
    } else {
        $reg_course_main = $reg['course'];
    }
}

// If profile form submitted, save details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $substrand = trim($_POST['substrand'] ?? '');
    $year_level = trim($_POST['year_level'] ?? '');

    // Basic validation
    if ($full_name === '' || $course === '' || $year_level === '') {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Please complete all profile fields.'];
        header('Location: vote.php');
        exit;
    }

    // If TVL, combine with substrand into course field
    if (strcasecmp($course, 'TVL') === 0 && $substrand !== '') {
        $course_db = 'TVL-' . $substrand;
    } else {
        $course_db = $course;
    }

    // Update registered_voters
    $up = $pdo->prepare('UPDATE registered_voters SET student_name = ?, course = ?, year_level = ? WHERE student_id = ?');
    $up->execute([$full_name, $course_db, $year_level, $student_id]);

    // Update students table name if exists
    $up2 = $pdo->prepare('UPDATE students SET name = ? WHERE student_id = ?');
    $up2->execute([$full_name, $student_id]);

    // Redirect to refresh page and show voting UI
    header('Location: vote.php');
    exit;
}

// If profile incomplete, show profile form first
$profile_missing = true;
if ($reg && !empty(trim($reg['student_name'])) && !empty(trim($reg['course'])) && !empty(trim($reg['year_level']))) {
    $profile_missing = false;
}

if ($profile_missing) {
    // show profile form and exit
    $page_title = 'Complete Your Profile';
    ?>
    <style>
        .profile-card { max-width:700px; margin:auto; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.08); padding:24px; background:#fff; }
        .btn-orange { background:#FF7F00; color:#fff; border:none; padding:12px 24px; border-radius:8px; font-weight:600; }
    </style>

    <div class="container py-5">
        <div class="profile-card">
            <h4 class="mb-3 text-center">Confirm Your Details</h4>
            <p class="text-muted text-center mb-4">Please provide your full name, strand, and year level before proceeding to vote.</p>
            <?php if (!empty($_SESSION['flash_message'])): $fm = $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
                <div class="alert alert-<?php echo htmlspecialchars($fm['type']); ?>"><?php echo htmlspecialchars($fm['message']); ?></div>
            <?php endif; ?>

            <form method="post" class="row g-3">
                <input type="hidden" name="save_profile" value="1">
                <div class="col-12">
                    <label class="form-label fw-bold">Full Name</label>
                    <input type="text" name="full_name" class="form-control" placeholder="Full Name" required value="<?php echo htmlspecialchars($reg['student_name'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Strand</label>
                    <select name="course" id="strand-select" class="form-select" required>
                        <option value="">Select Strand</option>
                        <option value="TVL" <?php if ($reg_course_main === 'TVL') echo 'selected'; ?>>TVL</option>
                        <option value="AD" <?php if ($reg_course_main === 'AD') echo 'selected'; ?>>AD</option>
                        <option value="ABM" <?php if ($reg_course_main === 'ABM') echo 'selected'; ?>>ABM</option>
                        <option value="HUMSS" <?php if ($reg_course_main === 'HUMSS') echo 'selected'; ?>>HUMSS</option>
                        <option value="STEM" <?php if ($reg_course_main === 'STEM') echo 'selected'; ?>>STEM</option>
                    </select>
                </div>
                <!-- TVL Sub-Strand Dropdown (hidden initially) -->
                <div class="col-md-6" id="tvl-substrand-div" <?php echo ($reg_course_main === 'TVL') ? '' : 'style="display: none;"'; ?>>
                  <label class="form-label fw-bold">TVL Specialization</label>
                  <select class="form-select" id="tvl-substrand" name="substrand" <?php echo ($reg_course_main === 'TVL') ? 'required' : ''; ?>>
                    <option value="">Select TVL Strand</option>
                    <option value="Home Economics" <?php if ($reg_substrand === 'Home Economics') echo 'selected'; ?>>Home Economics</option>
                    <option value="Travel Services" <?php if ($reg_substrand === 'Travel Services') echo 'selected'; ?>>Travel Services</option>
                    <option value="Fashion Design" <?php if ($reg_substrand === 'Fashion Design') echo 'selected'; ?>>Fashion Design</option>
                    <option value="ICT" <?php if ($reg_substrand === 'ICT' || $reg_substrand === 'Information and Communications Technology (ICT)') echo 'selected'; ?>>Information and Communications Technology (ICT)</option>
                  </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Year Level</label>
                    <select name="year_level" class="form-select" required>
                        <option value="">Select Year Level</option>
                        <option value="Grade 11" <?php if (($reg['year_level'] ?? '') === 'Grade 11') echo 'selected'; ?>>Grade 11</option>
                        <option value="Grade 12" <?php if (($reg['year_level'] ?? '') === 'Grade 12') echo 'selected'; ?>>Grade 12</option>
                    </select>
                </div>
                <div class="col-12 text-center mt-3">
                    <button class="btn btn-orange" type="submit">Proceed to Vote</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        const strand = document.getElementById('strand-select');
        const tvlDiv = document.getElementById('tvl-substrand-div');
        const tvlSelect = document.getElementById('tvl-substrand');

        if (!strand) return;

        strand.addEventListener('change', function(){
            if (this.value === 'TVL') {
                tvlDiv.style.display = 'block';
                if (tvlSelect) tvlSelect.required = true;
            } else {
                tvlDiv.style.display = 'none';
                if (tvlSelect) { 
                    tvlSelect.required = false; 
                    tvlSelect.value = ''; 
                }
            }
        });
    });
    </script>
    
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
} // <-- This closing brace was missing

?>

<style>
    /* 🔶 Modern Orange Button */
.btn-modern-orange {
    background: linear-gradient(135deg, #ff7b00, #ff9f1a);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    letter-spacing: 0.3px;
    box-shadow: 0 6px 18px rgba(255, 123, 0, 0.35);
    transition: all 0.25s ease;
}

.btn-modern-orange:hover {
    background: linear-gradient(135deg, #ff8c1a, #ffb347);
    transform: translateY(-1px);
    box-shadow: 0 10px 24px rgba(255, 123, 0, 0.45);
    color: #fff;
}

.btn-modern-orange:active {
    transform: translateY(0);
    box-shadow: 0 4px 12px rgba(255, 123, 0, 0.35);
}

.btn-modern-orange:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(255, 123, 0, 0.35);
}

.slider-wrap{overflow:hidden;border-radius:14px}
.slides{display:flex;transition:transform .4s ease}
.slide{min-width:100%;padding:28px}
.candidate-card{border:2px solid #eee;border-radius:12px;cursor:pointer;transition:.2s;height:100%;display:flex;flex-direction:column;}
.candidate-card:hover{border-color:#ffb066}
.candidate-card.selected{border-color:#ff7b00;background:#fff3e6}
.radio-hidden{display:none}
.step{width:32px;height:32px;border-radius:50%;background:#eee;display:flex;align-items:center;justify-content:center;font-weight:700}
.step.active{background:#ff7b00;color:#fff}

/* FIXED: Candidate photo container - NO CUTOFF */
.candidate-photo-container {
    width: 100%;
    height: 220px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 10px;
    position: relative;
    overflow: hidden;
}

.candidate-photo {
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
    display: block;
    padding: 8px;
    box-sizing: border-box;
}

/* Ensure the image fits properly without cutting */
.candidate-photo-wrapper {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px;
}

/* Fallback icon styling */
.candidate-photo-container .fa-user-circle {
    font-size: 80px;
    color: #adb5bd;
}

/* Ensure consistent text spacing */
.candidate-name {
    font-weight: 600;
    font-size: 1.05rem;
    margin-bottom: 4px;
    color: #333;
    line-height: 1.2;
}

.candidate-party {
    font-size: 0.85rem;
    color: #666;
    font-weight: 500;
    line-height: 1.2;
    margin-top: auto;
    padding-top: 8px;
}

/* Ensure card content is properly spaced */
.candidate-card .card-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}
.abstain-card {
    max-width: 220px;
    margin: 0 auto;
    padding: 12px;
    border: 2px dashed #e0a0a0;
    background: #fff7f7;
    color: #8a1f1f;
    transition: all .18s ease;
}
.abstain-card .candidate-name { font-size: 0.95rem; font-weight:700; color:#8a1f1f; }
.abstain-card .candidate-party { color:#b33; font-weight:600; }
.abstain-card .fa-ban { font-size:48px; color:#c94b4b; }
.abstain-card:hover {
    border-color: #c92b2b;
    background: #fff0f0;
    transform: translateY(-4px);
    box-shadow: 0 8px 18px rgba(201,43,43,0.12);
}

/* click animation */
@keyframes pop {
    0% { transform: scale(1); }
    50% { transform: scale(1.08); }
    100% { transform: scale(1); }
}
.abstain-card.clicked {
    animation: pop 360ms cubic-bezier(.2,.8,.2,1);
}
</style>

<div class="container py-4">
<form method="post" id="voteForm" action="vote_submit.php">

<div class="card slider-wrap shadow">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <div class="d-flex gap-2">
            <?php
            // +1 for About page
            for ($i = 0; $i < count($POSITIONS) + 1; $i++): ?>
                <div class="step <?= $i===0?'active':'' ?>"><?= $i+1 ?></div>
            <?php endfor; ?>
        </div>
        <div>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="backBtn" style="display:none">Back</button>
            <button type="button" class="btn btn-modern-orange btn-sm px-4" id="nextBtn">
                <span class="btn-text">Next</span>
                <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </div>
    </div>

    <div class="slides" id="slides">

        <!-- 🔹 ABOUT VOTESURE SLIDE -->
        <div class="slide">
            <h3 class="fw-bold mb-3 text-orange">
                <i class="fas fa-info-circle me-2"></i>About VoteSure
            </h3>

            <p class="text-muted">
                VoteSure is a <b>secure, fast, and transparent</b> electronic voting system
                designed for Senior High School Student Government elections.
            </p>

            <h5 class="fw-bold mt-4">How to Vote</h5>
            <ol class="text-muted">
                <li>Read each position carefully.</li>
                <li>Select <b>only one candidate</b> per position.</li>
                <li>Use the <b>Next</b> and <b>Back</b> buttons to review.</li>
                <li>Once submitted, your vote <b>cannot be changed</b>.</li>
            </ol>

            <div class="alert alert-warning mt-4">
                <i class="fas fa-exclamation-triangle me-1"></i>
                Make sure your selections are final before submitting.
            </div>

            <p class="text-muted mt-3">
                Click <b>Next</b> to begin voting.
            </p>
        </div>

        <!-- 🔹 POSITION SLIDES -->
        <?php foreach ($POSITIONS as $pos): ?>
        <div class="slide" data-pos="<?= h($pos) ?>">
            <h4 class="fw-bold mb-3"><?= h($pos) ?></h4>

            <div class="row g-3">
            <?php if (empty($byPosition[$pos])): ?>
                <div class="col-12 text-muted">No candidates available.</div>
            <?php else: ?>
            <?php foreach ($byPosition[$pos] as $c): ?>
                <div class="col-md-4 mb-3">
                    <label class="w-100 h-100">
                        <input class="radio-hidden"
                               type="radio"
                               name="choice[<?= h($pos) ?>]"
                               value="<?= $c['id'] ?>">
                        <div class="candidate-card p-3 text-center h-100">
                            <div class="candidate-photo-container">
                                <div class="candidate-photo-wrapper">
                                    <?php if ($c['photo'] && file_exists(__DIR__.'/../uploads/'.$c['photo'])): ?>
                                        <img class="candidate-photo" 
                                             src="/votesure_reborn/uploads/<?= h($c['photo']) ?>" 
                                             alt="<?= h($c['name']) ?>"
                                             onerror="this.onerror=null;this.src='data:image/svg+xml;utf8,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20width%3D%22100%22%20height%3D%22100%22%20viewBox%3D%220%200%20100%20100%22%3E%3Crect%20width%3D%22100%22%20height%3D%22100%22%20fill%3D%22%23f8f9fa%22/%3E%3Ctext%20x%3D%2250%22%20y%3D%2250%22%20font-family%3D%22Arial%22%20font-size%3D%2214%22%20fill%3D%22%236c757d%22%20text-anchor%3D%22middle%22%20dy%3D%22.3em%22%3EImage%3C/text%3E%3C/svg%3E';">
                                    <?php else: ?>
                                        <i class="fas fa-user-circle candidate-photo"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-content">
                                <div class="candidate-name"><?= h($c['name']) ?></div>
                                <div class="candidate-party"><?= h($c['party']) ?></div>
                            </div>
                        </div>
                    </label>
                </div>
            <?php endforeach; ?>
                <!-- Small abstain card placed below candidates -->
                <div class="col-12">
                    <div class="d-flex justify-content-center">
                        <label class="w-auto">
                            <input class="radio-hidden"
                                   type="radio"
                                   name="choice[<?= h($pos) ?>]"
                                   value="0" checked>
                            <div class="candidate-card abstain-card p-2 text-center selected">
                                <div class="candidate-photo-wrapper">
                                    <i class="fas fa-ban" title="No selection"></i>
                                </div>
                                <div class="card-content">
                                    <div class="candidate-name">No selection</div>
                                    <div class="candidate-party">Abstain / Skip</div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</div>

</form>
</div>

<!-- SweetAlert2 for nicer alerts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (!empty($_SESSION['flash_message'])): $fm = $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    try {
        const fm = <?php echo json_encode($fm); ?>;
        const icon = fm.type === 'danger' ? 'error' : (fm.type === 'success' ? 'success' : 'info');
        Swal.fire({ icon, title: fm.type === 'danger' ? 'Error' : (fm.type === 'success' ? 'Success' : ''), text: fm.message });
    } catch (e) {
        console.error('Flash display failed', e);
    }
});
</script>
<?php endif; ?>

<script>
(() => {
    const slides = document.querySelectorAll('.slide');
    const steps = document.querySelectorAll('.step');
    const slidesWrap = document.getElementById('slides');
    const nextBtn = document.getElementById('nextBtn');
    const backBtn = document.getElementById('backBtn');
    let index = 0;

    function update() {
        slidesWrap.style.transform = `translateX(-${index*100}%)`;
        steps.forEach((s,i)=>s.classList.toggle('active',i===index));
        backBtn.style.display = index===0?'none':'inline-block';
        
        const nextBtnText = nextBtn.querySelector('.btn-text');
        nextBtnText.textContent = index===slides.length-1?'Submit Vote':'Next';
        
        // Update icon for submit
        const icon = nextBtn.querySelector('i');
        if (index === slides.length - 1) {
            icon.className = 'fas fa-paper-plane ms-2';
        } else {
            icon.className = 'fas fa-arrow-right ms-2';
        }
    }

    document.querySelectorAll('.radio-hidden').forEach(r=>{
        r.addEventListener('change',()=>{
            const slide = r.closest('.slide');
            slide.querySelectorAll('.candidate-card')
                 .forEach(c=>c.classList.remove('selected'));
            try {
                const card = r.nextElementSibling;
                if (card) {
                    card.classList.add('selected');
                    // If this is the abstain-card, trigger a click animation
                    if (card.classList.contains('abstain-card')) {
                        card.classList.remove('clicked');
                        // trigger reflow to restart animation
                        void card.offsetWidth;
                        card.classList.add('clicked');
                        card.addEventListener('animationend', () => card.classList.remove('clicked'), { once: true });
                    }
                }
            } catch (e) {
                console.error('Selection handling failed', e);
            }
        });
    });
    // Mark any pre-checked radios as selected on load (e.g., the default "No selection" cards)
    document.querySelectorAll('.radio-hidden:checked').forEach(r => {
        try { r.nextElementSibling.classList.add('selected'); } catch(e){/* ignore */ }
    });

    nextBtn.onclick=()=>{
        try {
            // Skip validation for About page
            if (index > 0) {
                const radios = slides[index].querySelectorAll('.radio-hidden');
                if (radios.length && !slides[index].querySelector('.radio-hidden:checked')) {
                    Swal.fire({ icon: 'warning', title: 'Please select a candidate before proceeding' });
                    return;
                }
            }

            if (index === slides.length - 1) {
                // Final validation - check all positions have selections
                let allSelected = true;
                const missingPositions = [];
                
                slides.forEach((slide, i) => {
                    if (i > 0) { // Skip about page
                        const radios = slide.querySelectorAll('.radio-hidden');
                        if (radios.length && !slide.querySelector('.radio-hidden:checked')) {
                            allSelected = false;
                            const pos = slide.getAttribute('data-pos');
                            missingPositions.push(pos);
                        }
                    }
                });
                
                if (!allSelected) {
                    const html = '<p>Please select candidates for all positions before submitting:</p><ul style="text-align:left;">' + missingPositions.map(p=>'<li>'+p+'</li>').join('') + '</ul>';
                    Swal.fire({ icon: 'warning', title: 'Incomplete selections', html, width: 600 });
                    return;
                }
                
                Swal.fire({
                    title: 'Are you sure you want to submit your vote?',
                    text: 'this action cannot be undone.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Submit Vote',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('voteForm').submit();
                    }
                });
            } else {
                index++;
                update();
            }
        } catch (err) {
            console.error(err);
            Swal.fire({ icon: 'error', title: 'Unexpected error', text: 'An unexpected error occurred. Please try again.' });
        }
    };

    backBtn.onclick=()=>{ if(index>0){ index--; update(); } };
    update();
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>