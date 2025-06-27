<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';

$username = $_SESSION['user'];
$stmt = $conn->prepare("SELECT id, email FROM users WHERE username = ?");
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

$search = trim($_GET['search'] ?? '');
if ($search) {
    $sql = "SELECT * FROM tasks WHERE user_id = ? AND (title LIKE ? OR description LIKE ?) ORDER BY due_date ASC, priority DESC";
    $stmt = $conn->prepare($sql);
    $like = "%$search%";
    $stmt->bind_param("iss", $userId, $like, $like);
} else {
    $sql = "SELECT * FROM tasks WHERE user_id = ? ORDER BY due_date ASC, priority DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
}
$stmt->execute();
$result = $stmt->get_result();

$tasksByMonthYear = [];
$notifications = [];
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$now = date('Y-m-d H:i:s');

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $taskTime = strtotime($row['due_date']);
        $monthYear = date('F Y', $taskTime);
        $tasksByMonthYear[$monthYear][] = $row;

        $dueDate = date('Y-m-d', $taskTime);
        $dueTime = date('h:i A', $taskTime);

        if (date('Y-m-d H:i:s', $taskTime) < $now && !$row['is_completed']) {
            $notifications[] = "⚠️ <strong>" . htmlspecialchars($row['title']) . "</strong> was due <strong>" . date('M d \a\t h:i A', $taskTime) . "</strong> (Overdue)";
        } elseif ($dueDate == $today && !$row['is_completed']) {
            $notifications[] = "⏰ You have <strong>" . htmlspecialchars($row['title']) . "</strong> Today at <strong>$dueTime</strong>";
        } elseif ($dueDate == $tomorrow && !$row['is_completed']) {
            $notifications[] = "📅 <strong>" . htmlspecialchars($row['title']) . "</strong> is due Tomorrow at <strong>$dueTime</strong>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>ProTasker Dashboard</title>
  <link rel="stylesheet" href="calendar/fullcalendar.min.css" />
  <link rel="stylesheet" href="assets/style.css" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        background: #f5f7fa;
        transition: background 0.3s, color 0.3s;
        color: #333;
    }
    header,
    .task-list,
    .reminder-box,
    #calendar {
        max-width: 900px;
        margin: auto;
    }
    header {
        padding: 20px;
        text-align: center;
        background: #3498db;
        color: white;
        position: relative;
    }
    .top-actions {
        text-align: center;
        margin: 20px 0;
    }
    .btn {
        text-decoration: none;
        background: #3498db;
        color: white;
        padding: 8px 14px;
        border-radius: 6px;
        margin: 5px;
        display: inline-block;
        transition: background 0.3s;
        cursor: pointer;
        border: none;
        font-size: 14px;
    }
    .btn:hover {
        background: #2980b9;
    }
    .btn-danger {
        background: #e74c3c;
    }
    .btn-danger:hover {
        background: #c0392b;
    }
    .task-list .month-year-group {
        background: white;
        margin: 20px 0;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    .task {
        border-bottom: 1px solid #eee;
        padding: 10px 0;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        align-items: center;
    }
    .task:last-child {
        border-bottom: none;
    }
    .priority-high {
        color: #e74c3c;
        font-weight: bold;
    }
    .priority-medium {
        color: #f39c12;
        font-weight: bold;
    }
    .priority-low {
        color: #27ae60;
        font-weight: bold;
    }
    .reminder-box {
        background: #fff8e1;
        border-left: 6px solid #fbc02d;
        padding: 15px;
        margin: 20px auto;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }
    .reminder-box h3 {
        margin-top: 0;
    }
    #calendar {
        margin: 40px auto;
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    .completed-task {
        opacity: 0.6;
        text-decoration: line-through;
    }
    /* Mobile Responsive */
    @media (max-width: 600px) {
        .task {
            flex-direction: column;
            align-items: flex-start;
        }
        .btn {
            margin-bottom: 10px;
        }
    }
    /* Dark Mode */
    body.dark {
        background: #121212;
        color: #f0f0f0;
    }
    body.dark .month-year-group,
    body.dark #calendar,
    body.dark .reminder-box {
        background: #1e1e1e;
        color: #f0f0f0;
        box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
    }
    body.dark .btn {
        background: #444;
        color: #fff;
    }
    body.dark .btn:hover {
        background: #666;
    }
    .dark-toggle {
        position: absolute;
        top: 20px;
        right: 20px;
        background: #333;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 5px;
        cursor: pointer;
        transition: background 0.3s;
    }
    .dark-toggle:hover {
        background: #555;
    }
    .filter-tasks {
        text-align: center;
        margin-bottom: 20px;
    }
    .filter-tasks .btn {
        cursor: pointer;
    }
    .completed-task {
        opacity: 0.6;
        text-decoration: line-through;
    }
    <?php include 'assets/dashboard_style.css'; ?>
  </style>
</head>
<body>
<header>
    <h1>Welcome, <?php echo htmlspecialchars($username); ?> 👋</h1>
    <form method="GET" style="margin-top: 10px;">
        <input type="text" name="search" placeholder="Search tasks..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="padding:6px 10px; border-radius:4px; border:1px solid #ccc; width:60%;" />
        <button type="submit" class="btn">Search</button>
    </form>
</header>

<div class="top-actions">
    <a href="add_task.php" class="btn">+ Add New Task</a>
    <a href="logout.php" class="btn btn-danger">Logout</a>
</div>

<?php if (!empty($notifications)): ?>
<div class="reminder-box" role="alert" aria-live="assertive">
    <h3>🛎️ Task Reminders</h3>
    <ul>
        <?php foreach ($notifications as $note): ?>
            <li><?php echo $note; ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="filter-tasks">
  <button onclick="filterTasks('all')" class="btn">All Tasks</button>
  <button onclick="filterTasks('pending')" class="btn">Pending Tasks</button>
  <button onclick="filterTasks('completed')" class="btn">Completed Tasks</button>
</div>

<div class="task-list">
<?php if (!empty($tasksByMonthYear)): ?>
    <?php foreach ($tasksByMonthYear as $monthYear => $tasks): ?>
        <div class="month-year-group">
            <h2><?php echo htmlspecialchars($monthYear); ?></h2>
            <?php foreach ($tasks as $task): ?>
                <div class="task <?php echo $task['is_completed'] ? 'completed-task' : ''; ?>" data-completed="<?php echo $task['is_completed'] ? '1' : '0'; ?>">
                    <div>
                        <strong><?php echo htmlspecialchars($task['title']); ?></strong><br />
                        <?php if (!empty($task['description'])): ?>
                            <em><?php echo htmlspecialchars($task['description']); ?></em><br />
                        <?php endif; ?>
                        Due: <?php echo date('M d, Y \a\t h:i A', strtotime($task['due_date'])); ?><br />
                        <span class="priority-<?php echo strtolower($task['priority']); ?>">
                            Priority: <?php echo htmlspecialchars($task['priority']); ?>
                        </span><br />
                        <small>Status: <?php echo $task['is_completed'] ? '<strong>Completed</strong>' : '<strong>Pending</strong>'; ?></small>
                        <div class="subtasks-container" id="subtasks-<?php echo (int)$task['id']; ?>">
                          <h4>Subtasks</h4>
                          <ul class="subtasks-list"></ul>
                          <input type="text" placeholder="New subtask title" class="new-subtask-input" />
                          <button class="btn btn-sm add-subtask-btn" data-taskid="<?php echo (int)$task['id']; ?>">Add Subtask</button>
                        </div>
                    </div>
                    <div>
                        <a href="edit_task.php?id=<?php echo (int)$task['id']; ?>" class="btn">Edit</a>
                        <a href="delete_task.php?id=<?php echo (int)$task['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this task?');">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p style="text-align:center;">No tasks found. Start by adding one!</p>
<?php endif; ?>
</div>

<div id="calendar"></div>

<script src="calendar/fullcalendar.min.js"></script>
<script>
// Escape HTML
function escapeHtml(text) {
  return text.replace(/[&<>"']/g, function (m) {
    return {
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[m];
  });
}

document.addEventListener('DOMContentLoaded', function () {
    var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: [
            <?php
            $stmt2 = $conn->prepare("SELECT title, due_date, priority FROM tasks WHERE user_id = ? ORDER BY due_date ASC");
            $stmt2->bind_param("i", $userId);
            $stmt2->execute();
            $calendarResult = $stmt2->get_result();
            while ($task = $calendarResult->fetch_assoc()) {
                $title = addslashes($task['title']);
                $due_date = date('c', strtotime($task['due_date']));
                $color = match(strtolower($task['priority'])) {
                    'high' => '#e74c3c',
                    'medium' => '#f39c12',
                    default => '#27ae60',
                };
                echo "{ title: '$title', start: '$due_date', color: '$color' },";
            }
            $stmt2->close();
            ?>
        ]
    });
    calendar.render();
    filterTasks('all');
    document.querySelectorAll('.subtasks-container').forEach(container => {
        const taskId = container.id.split('-')[1];
        loadSubtasks(taskId);
    });
});

function filterTasks(status) {
    document.querySelectorAll('.task').forEach(task => {
        const isCompleted = task.getAttribute('data-completed') === '1';
        task.style.display = (status === 'all' || (status === 'pending' && !isCompleted) || (status === 'completed' && isCompleted)) ? 'flex' : 'none';
    });
}

document.addEventListener('click', function(e) {
  if (e.target.classList.contains('add-subtask-btn')) {
    const taskId = e.target.dataset.taskid;
    const input = document.querySelector(`#subtasks-${taskId} .new-subtask-input`);
    const title = input.value.trim();
    if (!title) return alert('Subtask title cannot be empty.');

    const formData = new FormData();
    formData.append('task_id', taskId);
    formData.append('title', title);

    fetch('subtasks.php', {
      method: 'POST',
      body: formData
    }).then(res => res.json()).then(data => {
      if (data.success) {
        input.value = '';
        loadSubtasks(taskId);
      } else {
        alert('Failed to add subtask.');
      }
    });
  }
});

function loadSubtasks(taskId) {
  const container = document.querySelector(`#subtasks-${taskId} .subtasks-list`);
  fetch(`subtasks.php?task_id=${taskId}`)
  .then(res => res.json())
  .then(subtasks => {
    if (!Array.isArray(subtasks)) {
      container.innerHTML = '<li><em>Error loading subtasks.</em></li>';
      return;
    }
    if (subtasks.length === 0) {
      container.innerHTML = '<li><em>No subtasks yet.</em></li>';
      return;
    }
    container.innerHTML = subtasks.map(st =>
      `<li><label><input type="checkbox" ${st.is_completed ? 'checked' : ''} data-subtaskid="${st.id}" data-taskid="${taskId}"> ${escapeHtml(st.title)}</label></li>`
    ).join('');
    container.querySelectorAll('input[type="checkbox"]').forEach(cb => {
      cb.addEventListener('change', () => {
        updateSubtaskStatus(cb.dataset.subtaskid, cb.dataset.taskid, cb.checked ? 1 : 0);
      });
    });
  });
}

function updateSubtaskStatus(subtaskId, taskId, isCompleted) {
  const formData = new FormData();
  formData.append('subtask_id', subtaskId);
  formData.append('task_id', taskId);
  formData.append('is_completed', isCompleted);

  fetch('subtasks.php', {
    method: 'POST',
    body: formData
  }).then(res => res.json()).then(data => {
    if (!data.success) alert('Failed to update subtask.');
  });
}
</script>
</body>
</html>
