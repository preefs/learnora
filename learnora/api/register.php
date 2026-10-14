<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

$fullname = $data['fullname'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (empty($fullname) || empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Please fill in all fields."]);
    exit;
}

try {
    $pdo->beginTransaction();

    $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $checkEmail->execute([$email]);
    if ($checkEmail->fetch()) {
        $pdo->rollBack();
        echo json_encode(["status" => "error", "message" => "Email is already registered."]);
        exit;
    }

    $insert = $pdo->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
    $insert->execute([$fullname, $email, password_hash($password, PASSWORD_DEFAULT)]);
    $userId = $pdo->lastInsertId();

    $initProgress = $pdo->prepare("INSERT INTO progress (user_id, module_id, score, status) VALUES (?, 1, 0, 'in_progress')");
    $initProgress->execute([$userId]);

    $initModule = $pdo->prepare("INSERT INTO user_modules (user_id, module_number, status) VALUES (?, 1, 'in_progress')");
    $initModule->execute([$userId]);

    $pdo->commit();
    echo json_encode(["status" => "success"]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e->getCode() === "23000") {
        echo json_encode(["status" => "error", "message" => "Email is already registered."]);
    } else {
        error_log("Registration failed: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Unable to complete registration."]);
    }
}
?>