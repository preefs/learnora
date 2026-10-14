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
    $stmt = $pdo->prepare("SELECT certificate_number, issue_date FROM certificates WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$userId]);
    $certificate = $stmt->fetch();

    if (!$certificate) {
        $certificateNumber = "LN-" . strtoupper(bin2hex(random_bytes(5)));
        $stmt = $pdo->prepare("INSERT INTO certificates (user_id, certificate_number, issue_date) VALUES (?, ?, CURRENT_DATE)");
        $stmt->execute([$userId, $certificateNumber]);

        $certificate = [
            "certificate_number" => $certificateNumber,
            "issue_date" => date("Y-m-d")
        ];
    }

    echo json_encode([
        "status" => "success",
        "certificate_number" => $certificate["certificate_number"],
        "issue_date" => $certificate["issue_date"]
    ]);
} catch (Exception $e) {
    error_log("Final assessment save failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Unable to save final assessment"]);
}
?>
