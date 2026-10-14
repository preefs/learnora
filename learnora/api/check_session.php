<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION["user_id"])) {
    echo json_encode([
        "status" => "logged_in",
        "fullname" => $_SESSION["fullname"]
    ]);
} else {
    echo json_encode([
        "status" => "logged_out"
    ]);
}
?>