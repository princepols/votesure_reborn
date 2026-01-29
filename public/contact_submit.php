<?php
require_once __DIR__ . '/../config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}

$student_id = trim($_POST['student_id'] ?? '');
$name = trim($_POST['name'] ?? '');
$course = trim($_POST['course'] ?? '');
$year_level = trim($_POST['year_level'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($message === '') {
    $_SESSION['flash_message'] = ['type'=>'warning','message'=>'Please enter a message.'];
    header('Location: index.php#contact'); exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO contacts (student_id, name, course, year_level, message) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$student_id ?: null, $name ?: null, $course ?: null, $year_level ?: null, $message]);
    $_SESSION['flash_message'] = ['type'=>'success','message'=>'Your message was sent to the admin.'];
} catch (Exception $e) {
    $_SESSION['flash_message'] = ['type'=>'danger','message'=>'Error sending message: ' . $e->getMessage()];
}

header('Location: index.php#contact');
exit;