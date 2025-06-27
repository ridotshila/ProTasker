<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "unauthorized";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'db.php';

    $id = intval($_POST['id']);
    $is_completed = intval($_POST['is_completed']);

    $stmt = $conn->prepare("UPDATE tasks SET is_completed = ? WHERE id = ?");
    $stmt->bind_param("ii", $is_completed, $id);
    if ($stmt->execute()) {
        echo "ok";
    } else {
        echo "error";
    }
    $stmt->close();
}
?>
