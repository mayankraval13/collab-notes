<?php
require_once("../config/db.php");
require_once("_auth.php");

if (!isset($_GET['id'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["error" => "Missing id"]);
    exit;
}

$noteId = intval($_GET['id']);

$stmt = $pdo->prepare("SELECT filename FROM notes WHERE id = ?");
$stmt->execute([$noteId]);
$note = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$note) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(["error" => "Note not found"]);
    exit;
}

$filePath = "../uploads/" . $note['filename'];
if (!is_file($filePath)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(["error" => "File missing on server"]);
    exit;
}

// Increment downloads counter
$upd = $pdo->prepare("UPDATE notes SET downloads = downloads + 1 WHERE id = ?");
$upd->execute([$noteId]);

$basename = basename($filePath);
header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $basename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
?>




