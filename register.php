<?php
session_start();
require __DIR__ . '/database.php';

// Create the table automatically if it doesn't exist yet
$pdo->exec("CREATE TABLE IF NOT EXISTS system_users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($username === '' || $password === '' || $confirm === '') {
        $error = "Please fill in all fields.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        try {
            $check = $pdo->prepare("SELECT 1 FROM system_users WHERE username = ? LIMIT 1");
            $check->execute([$username]);

            if ($check->fetch()) {
                $error = "Username already exists.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO system_users (username, password_hash) VALUES (?, ?)");
                $stmt->execute([$username, $hash]);
                $success = "Account created! You can now log in.";
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Academic Tracking System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="login-card">
        <h2>Create System User</h2>

        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert" style="background:#dcfce7;color:#166534;padding:10px;border-radius:6px;margin-bottom:12px;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required placeholder="Choose a username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="At least 6 characters">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Repeat password">
            </div>
            <button type="submit" class="btn btn-login">Create Account</button>
        </form>

        <p style="text-align:center;margin-top:15px;">
            <a href="login.php">Back to Login</a>
        </p>
    </div>
</body>
</html>