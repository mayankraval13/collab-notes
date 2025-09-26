<?php
header("Content-Type: application/json");
require_once("../config/db.php");
require_once("_auth.php");

// Check if form data exists
if (!isset($_POST['uploader']) || !isset($_FILES['pdf'])) {
    echo json_encode(["error" => "Missing uploader name or file"]);
    exit;
}

$uploader = htmlspecialchars($_POST['uploader']);
$file= $_FILES['pdf'];

if ($file['type'] !== "application/pdf") {
    echo json_encode(["error" => "Only PDF files allowed"]);
    exit;
}

// Generate unique filename
$targetDir = "../uploads/";
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}
$newName = time() . "_" . basename($file["name"]);
$targetPath = $targetDir . $newName;

if (move_uploaded_file($file["tmp_name"], $targetPath)) {
    // Check if user_id column exists
    $hasUserIdColumn = false;
    try {
        $pdo->query("SELECT user_id FROM notes LIMIT 1");
        $hasUserIdColumn = true;
    } catch (Exception $e) {
        // Column doesn't exist
    }

    if ($hasUserIdColumn) {
        $stmt = $pdo->prepare("INSERT INTO notes (filename, uploader, user_id) VALUES (?, ?, ?)");
        $stmt->execute([$newName, $uploader, $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO notes (filename, uploader) VALUES (?, ?)");
        $stmt->execute([$newName, $uploader]);
    }

    echo json_encode(["success" => true, "message" => "File uploaded successfully"]);
} else {
    echo json_encode(["error" => "File upload failed"]);
}
?>
