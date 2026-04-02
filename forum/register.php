<?php
// register.php — New user registration
session_start();

// Already logged in? Go home.
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require 'db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Basic validation
    if ($username === '' || $password === '') {
        $error = 'Both fields are required.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Username must be between 3 and 50 characters.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $db = get_db();

        // Check if username is already taken
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'That username is already taken.';
        } else {
            // Hash password and insert user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
            $stmt->execute([$username, $hash]);

            // Log them in right away
            $_SESSION['user_id']  = $db->lastInsertId();
            $_SESSION['username'] = $username;
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register — Forum</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Georgia, serif; background: #f4f4f4; color: #222; }
        .wrap { max-width: 400px; margin: 80px auto; padding: 0 16px; }
        h1 { font-size: 1.6rem; margin-bottom: 6px; }
        p.sub { color: #666; margin-bottom: 24px; font-size: 0.9rem; }
        .card { background: #fff; border: 1px solid #ddd; border-radius: 8px;
                padding: 28px; box-shadow: 0 2px 6px rgba(0,0,0,.06); }
        label { display: block; font-size: 0.85rem; font-weight: bold;
                margin-bottom: 4px; color: #444; }
        input { width: 100%; padding: 10px 12px; margin-bottom: 16px;
                border: 1px solid #ccc; border-radius: 6px; font-size: 1rem; }
        input:focus { outline: none; border-color: #4a90d9; }
        button { width: 100%; padding: 11px; background: #4a90d9; color: #fff;
                 border: none; border-radius: 6px; font-size: 1rem;
                 cursor: pointer; font-family: Georgia, serif; }
        button:hover { background: #357abd; }
        .error { background: #fee; border: 1px solid #fcc; color: #900;
                 padding: 10px 14px; border-radius: 6px; margin-bottom: 16px;
                 font-size: 0.9rem; }
        .link { text-align: center; margin-top: 16px; font-size: 0.9rem; color: #666; }
        .link a { color: #4a90d9; text-decoration: none; }
        .link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Create an account</h1>
    <p class="sub">Join the community and start discussing.</p>
    <div class="card">
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <label for="username">Username</label>
            <input type="text" name="username" id="username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   placeholder="Pick a username" required>

            <label for="password">Password</label>
            <input type="password" name="password" id="password"
                   placeholder="At least 6 characters" required>

            <button type="submit">Register</button>
        </form>
    </div>
    <p class="link">Already have an account? <a href="login.php">Log in</a></p>
</div>
</body>
</html>
