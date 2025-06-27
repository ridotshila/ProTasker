<?php
session_start();
include 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (strlen($username) < 3 || strlen($password) < 5) {
        $error = "Username must be at least 3 characters and password at least 5.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Username already taken.";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param("sss", $username, $email, $hash);
            if ($stmt->execute()) {
                $success = "Account created. You can now log in.";
            } else {
                $error = "Something went wrong. Try again.";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register | ProTasker</title>
    <link rel="stylesheet" href="login_register.css">
</head>
<body>
<div class="container">
    <h2>Register</h2>
    <?php if ($error): ?>
        <p style="color: red; text-align:center;"><?php echo $error; ?></p>
    <?php elseif ($success): ?>
        <p style="color: green; text-align:center;"><?php echo $success; ?></p>
    <?php endif; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email address" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Register</button>
        <a href="login.php">Already have an account? Login</a>
    </form>
</div>
</body>
</html>


