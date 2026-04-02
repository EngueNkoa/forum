<?php
// login.php — User login
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require 'db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Both fields are required.';
    } else {
        $db   = get_db();
        $stmt = $db->prepare('SELECT id, username, password FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Correct credentials — start session
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Incorrect username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log In — Forum</title>
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
    <h1>Welcome back</h1>
    <p class="sub">Log in to join the conversation.</p>
    <div class="card">
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <label for="username">Username</label>
            <input type="text" name="username" id="username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   placeholder="Your username" required>

            <label for="password">Password</label>
            <input type="password" name="password" id="password"
                   placeholder="Your password" required>

            <button type="submit">Log In</button>
        </form>
    </div>
    <p class="link">No account? <a href="register.php">Register here</a></p>
</div>
</body>
</html>
