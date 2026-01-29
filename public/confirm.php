<?php
$status = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vote Status</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<style>
body {

    background-color: #ee7c12ff;
}
</style>
<body>

<script>
<?php if ($status === 'success'): ?>
Swal.fire({
    icon: 'success',
    title: 'Vote Submitted',
    text: 'Your vote has been recorded successfully!',
    confirmButtonColor: '#ff7b00'
}).then(() => {
    window.location.href = 'index.php';
});
<?php elseif ($status === 'already_voted'): ?>
Swal.fire({
    icon: 'info',
    title: 'Already Voted',
    text: 'You have already submitted your vote. Or you are not authorized to vote.',
    confirmButtonColor: '#ff7b00'
}).then(() => {
    window.location.href = 'index.php';
});
<?php endif; ?>
</script>

</body>
</html>
