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

$errors = [];
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
        $stmt = $conn->prepare("INSERT INTO tasks (user_id, title, description, due_date, priority, is_completed) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssi", $userId, $title, $description, $due_date, $priority, $is_completed);
        if ($stmt->execute()) {
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Failed to add task";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Add New Task</title>
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
<h1>Add New Task</h1>
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
    <input type="text" id="title" name="title" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">

    <label for="description">Description</label>
    <textarea id="description" name="description"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>

    <label for="due_date">Due Date & Time *</label>
    <input type="datetime-local" id="due_date" name="due_date" required value="<?php echo isset($_POST['due_date']) ? htmlspecialchars($_POST['due_date']) : ''; ?>">

    <label for="priority">Priority</label>
    <select id="priority" name="priority">
        <option value="Low" <?php if(isset($_POST['priority']) && $_POST['priority'] === 'Low') echo 'selected'; ?>>Low</option>
        <option value="Medium" <?php if(isset($_POST['priority']) && $_POST['priority'] === 'Medium') echo 'selected'; ?>>Medium</option>
        <option value="High" <?php if(isset($_POST['priority']) && $_POST['priority'] === 'High') echo 'selected'; ?>>High</option>
    </select>

    <label>
        <input type="checkbox" name="is_completed" value="1" <?php if(isset($_POST['is_completed'])) echo 'checked'; ?>>
        Mark task as complete
    </label>

    <button type="submit">Add Task</button>
</form>
<p><a href="index.php">← Back to Dashboard</a></p>
</body>
</html>
