<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch user progress from database (or return default zeros if none exists yet)
$stmt = $pdo->prepare("SELECT * FROM progress WHERE user_id = ?");
$stmt->execute([$userId]);
$progress = $stmt->fetch();

if (!$progress) {
    // Default values for new users
    $progress = [
        "course_progress" => 0,
        "learning_time" => "0h 0m",
        "completed_modules" => 0
    ];
}

echo json_encode([
    "status" => "success",
    "progress" => $progress
]);
?>