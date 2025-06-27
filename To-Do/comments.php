<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$username = $_SESSION['user'];

// Get user ID
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'User not found']);
    exit;
}
$user = $result->fetch_assoc();
$userId = $user['id'];
$stmt->close();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Fetch comments for a task
    $taskId = intval($_GET['task_id'] ?? 0);
    if ($taskId <= 0) {
        echo json_encode([]);
        exit;
    }

    $stmt = $conn->prepare("SELECT c.id, c.comment_text, c.created_at, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.task_id = ? ORDER BY c.created_at ASC");
    $stmt->bind_param("i", $taskId);
    $stmt->execute();
    $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode($comments);
    exit;

} elseif ($method === 'POST') {
    // Add new comment
    $taskId = intval($_POST['task_id'] ?? 0);
    $commentText = trim($_POST['comment_text'] ?? '');

    if ($taskId <= 0 || $commentText === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        exit;
    }

    // Insert comment
    $stmt = $conn->prepare("INSERT INTO comments (task_id, user_id, comment_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $taskId, $userId, $commentText);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'comment_id' => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add comment']);
    }
    $stmt->close();
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
