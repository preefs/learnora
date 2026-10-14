<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$moduleNumber = isset($data['module_number']) ? filter_var($data['module_number'], FILTER_VALIDATE_INT) : false;

if ($moduleNumber === false || $moduleNumber < 1 || $moduleNumber > 5) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid module number"]);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id FROM progress WHERE user_id = ? AND module_id = ? LIMIT 1");
    $stmt->execute([$userId, $moduleNumber]);
    $progressId = $stmt->fetchColumn();
    if ($progressId) {
        $stmt = $pdo->prepare("UPDATE progress SET score = 100, status = 'completed' WHERE user_id = ? AND module_id = ?");
        $stmt->execute([$userId, $moduleNumber]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO progress (user_id, module_id, score, status) VALUES (?, ?, 100, 'completed')");
        $stmt->execute([$userId, $moduleNumber]);
    }

    $stmt = $pdo->prepare("SELECT id FROM user_modules WHERE user_id = ? AND module_number = ? LIMIT 1");
    $stmt->execute([$userId, $moduleNumber]);
    if ($stmt->fetchColumn()) {
        $stmt = $pdo->prepare("UPDATE user_modules SET status = 'completed' WHERE user_id = ? AND module_number = ?");
        $stmt->execute([$userId, $moduleNumber]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO user_modules (user_id, module_number, status) VALUES (?, ?, 'completed')");
        $stmt->execute([$userId, $moduleNumber]);
    }

    $nextModule = $moduleNumber + 1;
    if ($nextModule <= 5) {
        $stmt = $pdo->prepare("SELECT id FROM progress WHERE user_id = ? AND module_id = ? LIMIT 1");
        $stmt->execute([$userId, $nextModule]);
        if ($stmt->fetchColumn()) {
            $stmt = $pdo->prepare("UPDATE progress SET status = 'in_progress' WHERE user_id = ? AND module_id = ? AND status <> 'completed'");
            $stmt->execute([$userId, $nextModule]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO progress (user_id, module_id, score, status) VALUES (?, ?, 0, 'in_progress')");
            $stmt->execute([$userId, $nextModule]);
        }

        $stmt = $pdo->prepare("SELECT id FROM user_modules WHERE user_id = ? AND module_number = ? LIMIT 1");
        $stmt->execute([$userId, $nextModule]);
        if ($stmt->fetchColumn()) {
            $stmt = $pdo->prepare("UPDATE user_modules SET status = 'in_progress' WHERE user_id = ? AND module_number = ? AND status <> 'completed'");
            $stmt->execute([$userId, $nextModule]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO user_modules (user_id, module_number, status) VALUES (?, ?, 'in_progress')");
            $stmt->execute([$userId, $nextModule]);
        }
    }

    $pdo->commit();
    echo json_encode(["status" => "success", "message" => "Progress updated successfully"]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Quiz progress save failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Unable to save quiz progress"]);
}
?>