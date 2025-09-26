<?php
header("Content-Type: application/json");
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Expect JSON body: { email, password }
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode(["error" => "Email and password are required"]);
    exit;
}

$email = trim(strtolower($data['email']));
$password = $data['password'];

// Domain validation: only allow emails from iite.indusuni.ac.in
$allowedDomain = 'iite.indusuni.ac.in';
$atPos = strrpos($email, '@');
if ($atPos === false || substr($email, $atPos + 1) !== $allowedDomain) {
    http_response_code(403);
    echo json_encode(["error" => "Access restricted. Use your iite.indusuni.ac.in email."]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name, email, password_hash FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Auto-register new user, then ask them to login again
    $localPart = explode('@', $email)[0];
    // Trim at first dot and format: ucfirst(lowercase)
    $dotPos = strpos($localPart, '.');
    if ($dotPos !== false) {
        $localPart = substr($localPart, 0, $dotPos);
    }
    $readableName = ucfirst(strtolower($localPart));
    if (!$readableName) { $readableName = 'Student'; }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
    try {
        $ins->execute([$readableName, $email, $hash]);
    } catch (Exception $e) {
        // If unique constraint or any DB error
        echo json_encode(["error" => "Could not register. Try again."]);
        exit;
    }
    echo json_encode(["error" => "Information stored. Please login again."]);
    exit;
}

if (!password_verify($password, $user['password_hash'])) {
    echo json_encode(["error" => "Invalid credentials"]);
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];

echo json_encode([
    "success" => true,
    "user" => [
        "id" => $user['id'],
        "name" => $user['name'],
        "email" => $user['email']
    ]
]);
?>


