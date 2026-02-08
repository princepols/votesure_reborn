<?php


$DB_HOST = '127.0.0.1';
$DB_NAME = 'voting_system1';
$DB_USER = 'root';
$DB_PASS = '';
$options = [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, ];
try { $pdo = new PDO("mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4", $DB_USER, $DB_PASS, $options); } catch (Exception $e) { die('Database connection error: ' . $e->getMessage()); }



function h($v){ return htmlspecialchars($v, ENT_QUOTES); }

function app_base_url() {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir1 = rtrim(dirname($script), '/');
    $dir2 = rtrim(dirname($dir1), '/');
    return $dir2 === '' ? '/' : $dir2;
}
?>
