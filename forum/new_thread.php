<?php
// new_thread.php — Form to create a new thread
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title']   ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '' || $content === '') {
        $error = 'Both title and content are required.';
    } elseif (strlen($title) > 200) {
        $error = 'Title is too long (max 200 characters).';
    } else {
        $db   = get_db();
        $stmt = $db->prepare(
            'INSERT INTO threads (user_id, title, content) VALUES (?, ?, ?)'
        );
        $stmt->execute([$_SESSION['user_id'], $title, $content]);
        $new_id = $db->lastInsertId();

        // Go straight to the new thread
        header('Location: thread.php?id=' . $new_id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Thread — Forum</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Georgia, serif; background: #f4f4f4; color: #222; }
        nav { background: #fff; border-bottom: 1px solid #ddd; padding: 12px 20px;
              display: flex; align-items: center; justify-content: space-between; }
        nav .brand { font-size: 1.2rem; font-weight: bold; color: #222; text-decoration: none; }
        nav .links a { margin-left: 16px; color: #4a90d9; text-decoration: none; font-size: 0.9rem; }
        nav .links a:hover { text-decoration: underline; }
        .wrap { max-width: 680px; margin: 32px auto; padding: 0 16px; }
        h1 { font-size: 1.5rem; margin-bottom: 20px; }
        .card { background: #fff; border: 1px solid #ddd; border-radius: 8px;
                padding: 28px; box-shadow: 0 2px 6px rgba(0,0,0,.06); }
        label { display: block; font-size: 0.85rem; font-weight: bold;
                margin-bottom: 5px; color: #444; }
        input[type=text], textarea {
            width: 100%; padding: 10px 12px; border: 1px solid #ccc;
            border-radius: 6px; font-size: 1rem; font-family: Georgia, serif;
            margin-bottom: 18px;
        }
        input:focus, textarea:focus { outline: none; border-color: #4a90d9; }
        textarea { height: 160px; resize: vertical; }
        .actions { display: flex; gap: 10px; align-items: center; }
        button { padding: 10px 22px; background: #4a90d9; color: #fff;
                 border: none; border-radius: 6px; font-size: 1rem;
                 cursor: pointer; font-family: Georgia, serif; }
        button:hover { background: #357abd; }
        a.cancel { color: #888; text-decoration: none; font-size: 0.9rem; }
        a.cancel:hover { text-decoration: underline; }
        .error { background: #fee; border: 1px solid #fcc; color: #900;
                 padding: 10px 14px; border-radius: 6px; margin-bottom: 18px;
                 font-size: 0.9rem; }
    </style>
</head>
<body>

<nav>
    <a class="brand" href="index.php">💬 Forum</a>
    <div class="links">
        <a href="index.php">Home</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="wrap">
    <h1>Start a New Thread</h1>
    <div class="card">
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" maxlength="200"
                   placeholder="What is your thread about?"
                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>

            <label for="content">Content</label>
            <textarea name="content" id="content"
                      placeholder="Share your thoughts..." required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>

            <div class="actions">
                <button type="submit">Post Thread</button>
                <a class="cancel" href="index.php">Cancel</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
