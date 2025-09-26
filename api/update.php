<?php
header("Content-Type: application/json");
require_once("../config/db.php");
require_once("_auth.php");

// Validate input
if (!isset($_POST['note_id']) || !isset($_FILES['pdf'])) {
    echo json_encode(["error" => "Missing note_id or file"]);
    exit;
}

$noteId = intval($_POST['note_id']);
$file   = $_FILES['pdf'];
$userId = $_SESSION['user_id'];

// Validate file type
if ($file['type'] !== "application/pdf") {
    echo json_encode(["error" => "Only PDF files allowed"]);
    exit;
}

// Check if note exists and belongs to current user
$stmt = $pdo->prepare("SELECT uploader, filename, user_id FROM notes WHERE id = ?");
$stmt->execute([$noteId]);
$note = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$note) {
    echo json_encode(["error" => "Note not found"]);
    exit;
}

// Check if user_id column exists, if not use uploader name matching as fallback
$hasUserIdColumn = false;
try {
    $pdo->query("SELECT user_id FROM notes LIMIT 1");
    $hasUserIdColumn = true;
} catch (Exception $e) {
    // Column doesn't exist
}

if ($hasUserIdColumn) {
    // Use user_id for authentication
    if ($note['user_id'] != $userId) {
        echo json_encode(["error" => "Only the original uploader can update this file"]);
        exit;
    }
} else {
    // Fallback to uploader name matching
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || $note['uploader'] !== $user['name']) {
        echo json_encode(["error" => "Only the original uploader can update this file"]);
        exit;
    }
}

// Save new file
$targetDir = "../uploads/";
$newName   = time() . "_" . basename($file["name"]);
$targetPath = $targetDir . $newName;

if (move_uploaded_file($file["tmp_name"], $targetPath)) {
    // Optionally delete old file
    $oldPath = $targetDir . $note['filename'];
    if (file_exists($oldPath)) {
        unlink($oldPath);
    }

    // Update DB
    $stmt = $pdo->prepare("UPDATE notes SET filename = ? WHERE id = ?");
    $stmt->execute([$newName, $noteId]);

    echo json_encode(["success" => true, "message" => "File updated successfully"]);
} else {
    echo json_encode(["error" => "File update failed"]);
}
?>
