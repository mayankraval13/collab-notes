<?php
header("Content-Type: application/json");
require_once("../config/db.php");
require_once("_auth.php");

// Expect JSON: { note_id, username, content }
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['note_id']) || !isset($data['username']) || !isset($data['content'])) {
    echo json_encode(["error" => "note_id, username and content are required"]);
    exit;
}

$noteId = intval($data['note_id']);
$username = trim($data['username']);
$content = trim($data['content']);

if ($username === '' || $content === '') {
    echo json_encode(["error" => "Username and content cannot be empty"]);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO comments (note_id, username, content) VALUES (?, ?, ?)");
$stmt->execute([$noteId, $username, $content]);

echo json_encode(["success" => true]);
?>





