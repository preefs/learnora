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

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT module_number, status FROM user_modules WHERE user_id = ?");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    if (empty($rows)) {
        $insert = $pdo->prepare("INSERT INTO user_modules (user_id, module_number, status) VALUES (?, 1, 'in_progress')");
        $insert->execute([$userId]);
        $rows = [["module_number" => 1, "status" => "in_progress"]];
    }

    $statusPriority = ["locked" => 0, "in_progress" => 1, "completed" => 2];
    $moduleStatuses = [];
    foreach ($rows as $row) {
        $moduleNumber = (int) $row["module_number"];
        $status = $row["status"];
        if ($moduleNumber < 1 || $moduleNumber > 5 || !isset($statusPriority[$status])) {
            continue;
        }
        if (!isset($moduleStatuses[$moduleNumber])
            || $statusPriority[$status] > $statusPriority[$moduleStatuses[$moduleNumber]]) {
            $moduleStatuses[$moduleNumber] = $status;
        }
    }

    $findProgress = $pdo->prepare("SELECT id FROM progress WHERE user_id = ? AND module_id = ? LIMIT 1");
    foreach ($moduleStatuses as $moduleNumber => $status) {
        $updateModules = $pdo->prepare("UPDATE user_modules SET status = ? WHERE user_id = ? AND module_number = ?");
        $updateModules->execute([$status, $userId, $moduleNumber]);

        $findProgress->execute([$userId, $moduleNumber]);
        if ($findProgress->fetchColumn()) {
            if ($status === "completed") {
                $saveProgress = $pdo->prepare("UPDATE progress SET score = 100, status = 'completed' WHERE user_id = ? AND module_id = ?");
                $saveProgress->execute([$userId, $moduleNumber]);
            } else {
                $saveProgress = $pdo->prepare("UPDATE progress SET status = ? WHERE user_id = ? AND module_id = ? AND status <> 'completed'");
                $saveProgress->execute([$status, $userId, $moduleNumber]);
            }
        } else {
            $score = $status === "completed" ? 100 : 0;
            $saveProgress = $pdo->prepare("INSERT INTO progress (user_id, module_id, score, status) VALUES (?, ?, ?, ?)");
            $saveProgress->execute([$userId, $moduleNumber, $score, $status]);
        }
    }

    $pdo->commit();
    $modules = [];
    foreach ($moduleStatuses as $moduleNumber => $status) {
        $modules[] = ["module_number" => $moduleNumber, "status" => $status];
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Module progress load failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Unable to load module progress"]);
    exit;
}

echo json_encode([
    "status" => "success",
    "modules" => $modules
]);
?>