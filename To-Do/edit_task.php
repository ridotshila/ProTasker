<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

include 'db.php';

$username = $_SESSION['user'];
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$userResult = $stmt->get_result();

if ($userResult->num_rows === 0) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$userData = $userResult->fetch_assoc();
$userId = $userData['id'];
$stmt->close();

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$taskId = intval($_GET['id']);
$errors = [];

// Fetch task to edit
$stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $taskId, $userId);
$stmt->execute();
$taskResult = $stmt->get_result();

if ($taskResult->num_rows === 0) {
    $stmt->close();
    header("Location: index.php");
    exit;
}
$task = $taskResult->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];
    $priority = $_POST['priority'];
    $is_completed = isset($_POST['is_completed']) ? 1 : 0;

    if ($title === '') {
        $errors[] = "Title is required";
    }
    if ($due_date === '') {
        $errors[] = "Due date is required";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE tasks SET title=?, description=?, due_date=?, priority=?, is_completed=? WHERE id=? AND user_id=?");
        $stmt->bind_param("ssssiii", $title, $description, $due_date, $priority, $is_completed, $taskId, $userId);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Failed to update task";
        }
        $stmt->close();
    }
} else {
    // Pre-fill form with existing data
    $title = $task['title'];
    $description = $task['description'];
    $due_date = date('Y-m-d\TH:i', strtotime($task['due_date'])); // format for datetime-local
    $priority = $task['priority'];
    $is_completed = $task['is_completed'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Edit Task</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<style>
/* Basic styling */
body { font-family: Arial, sans-serif; margin: 20px; }
form { max-width: 500px; margin: auto; }
label { display: block; margin-top: 10px; }
input[type="text"], textarea, input[type="datetime-local"], select {
    width: 100%; padding: 8px; box-sizing: border-box;
}
input[type="checkbox"] {
    margin-right: 5px;
}
button {
    margin-top: 20px; padding: 10px 15px;
}
.error { color: red; }
</style>
</head>
<body>
<h1>Edit Task</h1>
<?php if (!empty($errors)): ?>
    <div class="error">
        <ul>
        <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err); ?></li>
        <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="">
    <label for="title">Title *</label>
    <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($title); ?>">

    <label for="description">Description</label>
    <textarea id="description" name="description"><?php echo htmlspecialchars($description); ?></textarea>

    <label for="due_date">Due Date & Time *</label>
    <input type="datetime-local" id="due_date" name="due_date" required value="<?php echo htmlspecialchars($due_date); ?>">

    <label for="priority">Priority</label>
    <select id="priority" name="priority">
        <option value="Low" <?php if($priority === 'Low') echo 'selected'; ?>>Low</option>
        <option value="Medium" <?php if($priority === 'Medium') echo 'selected'; ?>>Medium</option>
        <option value="High" <?php if($priority === 'High') echo 'selected'; ?>>High</option>
    </select>

    <label>
        <input type="checkbox" name="is_completed" value="1" <?php if($is_completed) echo 'checked'; ?>>
        Mark task as complete
    </label>

    <button type="submit">Update Task</button>
</form>
<p><a href="index.php">← Back to Dashboard</a></p>

</body>
</html>
