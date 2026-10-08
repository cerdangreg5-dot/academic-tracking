<?php
session_start();
if (!isset($_SESSION['user'])) { 
    header("Location: login.php"); 
    exit(); 
}
require __DIR__ . '/database.php';

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM track_enrollments WHERE enrollment_id = ?");
    $stmt->execute([$id]);
}

header("Location: index.php");
exit();