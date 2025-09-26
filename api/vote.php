<?php
header("Content-Type: application/json");
require_once("../config/db.php");
require_once("_auth.php");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    echo json_encode(["error" => "Note ID is required"]);
    exit;
}

$noteId = intval($data['id']);

// Increment liker
$stmt = $pdo->prepare("UPDATE notes SET votes = votes + 1 WHERE id = ?");
$stmt->execute([$noteId]);

// Get updated liker
$stmt = $pdo->prepare("SELECT votes FROM notes WHERE id = ?");
$stmt->execute([$noteId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(["success" => true, "votes" => $result['votes']]);
?>
