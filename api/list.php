<?php
header("Content-Type: application/json");
require_once("../config/db.php");
require_once("_auth.php");

// Ensure downloads column exists (runtime migration for existing DBs)
try {
    $pdo->query("ALTER TABLE notes ADD COLUMN downloads INT DEFAULT 0");
} catch (Exception $e) {
    // column may already exist; ignore
}

// Fetch all notes
$stmt = $pdo->query("SELECT id, filename, uploader, votes, downloads FROM notes ORDER BY created_at DESC");
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add file path for preview
foreach ($notes as &$note) {
    $note['filepath'] = "../uploads/" . $note['filename'];
}

echo json_encode($notes);
?>