<?php
// profile.php — User profile: shows info and their threads
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';
$db = get_db();

// If ?user=username is in URL, show that user's profile
// Otherwise show the logged-in user's own profile
if (isset($_GET['user']) && $_GET['user'] !== '') {
    $stmt = $db->prepare('SELECT id, username, created_at FROM users WHERE username = ?');
    $stmt->execute([trim($_GET['user'])]);
    $profile_user = $stmt->fetch();

    if (!$profile_user) {
        echo 'User not found.';
        exit;
    }
} else {
    $stmt = $db->prepare('SELECT id, username, created_at FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $profile_user = $stmt->fetch();
}

$is_own_profile = ($profile_user['id'] == $_SESSION['user_id']);

// Fetch threads created by this user
$stmt = $db->prepare('
    SELECT t.id, t.title, t.created_at,
           (SELECT COUNT(*) FROM replies r WHERE r.thread_id = t.id) AS reply_count
    FROM threads t
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC
');
$stmt->execute([$profile_user['id']]);
$user_threads = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($profile_user['username']) ?>'s Profile — Forum</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Georgia, serif; background: #f4f4f4; color: #222; }
        nav { background: #fff; border-bottom: 1px solid #ddd; padding: 12px 20px;
              display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
        nav .brand { font-size: 1.2rem; font-weight: bold; color: #222; text-decoration: none; }
        nav .links a { margin-left: 16px; color: #4a90d9; text-decoration: none; font-size: 0.9rem; }
        nav .links a:hover { text-decoration: underline; }
        .wrap { max-width: 680px; margin: 28px auto; padding: 0 16px; }
        .back { font-size: 0.85rem; margin-bottom: 16px; display: inline-block; }
        .back a { color: #4a90d9; text-decoration: none; }
        .back a:hover { text-decoration: underline; }

        /* ── Profile card ── */
        .profile-card { background: #fff; border: 1px solid #ddd; border-radius: 8px;
                        padding: 24px; box-shadow: 0 1px 4px rgba(0,0,0,.05); margin-bottom: 24px;
                        display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
        .avatar { width: 56px; height: 56px; border-radius: 50%; background: #4a90d9;
                  color: #fff; font-size: 1.5rem; display: flex; align-items: center;
                  justify-content: center; font-weight: bold; flex-shrink: 0; }
        .profile-info h1 { font-size: 1.4rem; margin-bottom: 4px; }
        .profile-info p { font-size: 0.85rem; color: #888; }

        /* ── Threads list ── */
        h2 { font-size: 1rem; color: #555; margin-bottom: 12px; }
        .thread-list { display: flex; flex-direction: column; gap: 10px; }
        .thread-row { background: #fff; border: 1px solid #ddd; border-radius: 8px;
                      padding: 14px 18px; display: flex; justify-content: space-between;
                      align-items: center; gap: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
        .thread-row:hover { border-color: #bbb; }
        .thread-row a { color: #222; text-decoration: none; font-size: 0.97rem; }
        .thread-row a:hover { color: #4a90d9; }
        .thread-date { font-size: 0.8rem; color: #999; white-space: nowrap; }
        .empty { color: #888; font-size: 0.9rem; padding: 12px 0; }

        /* ── Logout link ── */
        .logout-link { display: inline-block; margin-top: 20px; color: #999;
                       text-decoration: none; font-size: 0.85rem; }
        .logout-link:hover { color: #c00; text-decoration: underline; }
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
    <p class="back"><a href="index.php">← Back to threads</a></p>

    <!-- ── Profile info ── -->
    <div class="profile-card">
        <!-- Simple letter avatar -->
        <div class="avatar"><?= strtoupper($profile_user['username'][0]) ?></div>
        <div class="profile-info">
            <h1><?= htmlspecialchars($profile_user['username']) ?></h1>
            <p>Member since <?= date('F j, Y', strtotime($profile_user['created_at'])) ?></p>
            <p><?= count($user_threads) ?> thread<?= count($user_threads) == 1 ? '' : 's' ?> posted</p>
        </div>
    </div>

    <!-- ── User's threads ── -->
    <h2>Threads by <?= htmlspecialchars($profile_user['username']) ?></h2>

    <?php if (empty($user_threads)): ?>
        <p class="empty">No threads yet.</p>
    <?php else: ?>
    <div class="thread-list">
        <?php foreach ($user_threads as $t): ?>
        <div class="thread-row">
            <a href="thread.php?id=<?= $t['id'] ?>">
                <?= htmlspecialchars($t['title']) ?>
                <span style="color:#888;font-size:0.85rem">
                    (<?= $t['reply_count'] ?> repl<?= $t['reply_count'] == 1 ? 'y' : 'ies' ?>)
                </span>
            </a>
            <span class="thread-date"><?= date('M j, Y', strtotime($t['created_at'])) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($is_own_profile): ?>
        <a class="logout-link" href="logout.php">Log out</a>
    <?php endif; ?>
</div>

</body>
</html>
