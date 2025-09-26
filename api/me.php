<?php
header("Content-Type: application/json");
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["authenticated" => false]);
    exit;
}

echo json_encode([
    "authenticated" => true,
    "user" => [
        "id" => $_SESSION['user_id'],
        "name" => $_SESSION['user_name'],
        "email" => $_SESSION['user_email']
    ]
]);
?>





