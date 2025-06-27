<?php
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include 'db.php';

$username = $_SESSION['user'];
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$userResult = $stmt->get_result();
if ($userResult->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$user = $userResult->fetch_assoc();
$userId = $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $task_id = intval($_GET['task_id'] ?? 0);
    if (!$task_id) {
        echo json_encode([]);
        exit;
    }
    // Check ownership of the task
    $stmt = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $task_id, $userId);
    $stmt->execute();
    $taskCheck = $stmt->get_result();
    if ($taskCheck->num_rows === 0) {
        echo json_encode([]);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, title, is_completed FROM subtasks WHERE task_id = ? ORDER BY id ASC");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $subtasks = [];
    while ($row = $result->fetch_assoc()) {
        $subtasks[] = $row;
    }
    echo json_encode($subtasks);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read input JSON or form-data
    $task_id = intval($_POST['task_id'] ?? 0);
    $subtask_id = intval($_POST['subtask_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $is_completed = isset($_POST['is_completed']) ? intval($_POST['is_completed']) : null;

    // Validate task ownership
    $stmt = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $task_id, $userId);
    $stmt->execute();
    $taskCheck = $stmt->get_result();
    if ($taskCheck->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid task']);
        exit;
    }

    if ($title !== '') {
        // Add new subtask
        $stmt = $conn->prepare("INSERT INTO subtasks (task_id, title) VALUES (?, ?)");
        $stmt->bind_param("is", $task_id, $title);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    } elseif ($subtask_id && $is_completed !== null) {
        // Update subtask completion
        $stmt = $conn->prepare("UPDATE subtasks SET is_completed = ? WHERE id = ? AND task_id = ?");
        $stmt->bind_param("iii", $is_completed, $subtask_id, $task_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }

    echo json_encode(['success' => false]);
}
?>
