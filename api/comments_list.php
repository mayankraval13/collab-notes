<?php
header("Content-Type: application/json");
require_once("../config/db.php");
require_once("_auth.php");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

if (!isset($_GET['note_id'])) {
    echo json_encode(["error" => "note_id is required"]);
    exit;
}

$noteId = intval($_GET['note_id']);

$stmt = $pdo->prepare("SELECT id, note_id, username, content, created_at FROM comments WHERE note_id = ? ORDER BY created_at DESC");
$stmt->execute([$noteId]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["success" => true, "comments" => $comments]);
?>





