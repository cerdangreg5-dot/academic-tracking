<?php
session_start();
require __DIR__ . '/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? ''; // don't trim: must match exactly what was hashed

    if ($username !== '' && $password !== '') {
        try {
            $stmt = $pdo->prepare("SELECT * FROM system_users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            $ok = false;
            if ($user) {
                $db_hash = trim($user['password_hash'] ?? '');

                if (password_verify($password, $db_hash)) {
                    $ok = true;
                } elseif (hash_equals($db_hash, $password)) {
                    // Legacy row: password was stored as plain text. Accept it once, then upgrade to a real hash.
                    $ok = true;
                    $upd = $pdo->prepare("UPDATE system_users SET password_hash = ? WHERE username = ?");
                    $upd->execute([password_hash($password, PASSWORD_DEFAULT), $user['username']]);
                }
            }

            if ($ok) {
                session_regenerate_id(true);
                $_SESSION['user'] = $user['username'];
                header("Location: index.php");
                exit();
            } else {
                $error = "Incorrect Username or Password!";
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Academic Tracking System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="login-card">
        <h2>System Login</h2>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required placeholder="Enter username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter password">
            </div>

            <button type="submit" class="btn btn-login">Sign In</button>
        </form>

        <p style="text-align:center;margin-top:15px;">
            No account yet? <a href="register.php">Create one</a>
        </p>
    </div>
</body>
</html>