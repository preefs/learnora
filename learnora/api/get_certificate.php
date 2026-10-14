<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT users.fullname, certificates.certificate_number, certificates.issue_date
         FROM certificates
         JOIN users ON users.id = certificates.user_id
         WHERE certificates.user_id = ?
         ORDER BY certificates.id DESC
         LIMIT 1"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $certificate = $stmt->fetch();

    if (!$certificate) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "No certificate found"]);
        exit;
    }

    echo json_encode(["status" => "success", "certificate" => $certificate]);
} catch (Exception $e) {
    error_log("Certificate lookup failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Unable to load certificate"]);
}
?>
