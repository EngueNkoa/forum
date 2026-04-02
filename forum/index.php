<?php
// index.php — Home page: list of all threads
session_start();

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';
$db = get_db();

// Fetch all threads with author name, reply count, and upvote count
// Newest thread appears first
$stmt = $db->query('
    SELECT
        t.id,
        t.title,
        t.created_at,
        u.username,
        (SELECT COUNT(*) FROM replies  r WHERE r.thread_id = t.id)         AS reply_count,
        (SELECT COUNT(*) FROM upvotes  v WHERE v.thread_id = t.id AND v.reply_id IS NULL) AS vote_count
    FROM threads t
    JOIN users u ON u.id = t.user_id
    ORDER BY t.created_at DESC
');
$threads = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forum — Home</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Georgia, serif; background: #f4f4f4; color: #222; }

        /* ── Nav ── */
        nav { background: #fff; border-bottom: 1px solid #ddd; padding: 12px 20px;
              display: flex; align-items: center; justify-content: space-between;
              flex-wrap: wrap; gap: 8px; }
        nav .brand { font-size: 1.2rem; font-weight: bold; color: #222;
                     text-decoration: none; }
        nav .links a { margin-left: 16px; color: #4a90d9; text-decoration: none;
                       font-size: 0.9rem; }
        nav .links a:hover { text-decoration: underline; }

        /* ── Page layout ── */
        .wrap { max-width: 780px; margin: 32px auto; padding: 0 16px; }
        .page-header { display: flex; justify-content: space-between;
                       align-items: center; margin-bottom: 20px; }
        h1 { font-size: 1.5rem; }
        .btn { display: inline-block; padding: 9px 18px; background: #4a90d9;
               color: #fff; text-decoration: none; border-radius: 6px;
               font-size: 0.9rem; }
        .btn:hover { background: #357abd; }

        /* ── Thread cards ── */
        .thread-list { display: flex; flex-direction: column; gap: 12px; }
        .thread-card { background: #fff; border: 1px solid #ddd; border-radius: 8px;
                       padding: 18px 20px; box-shadow: 0 1px 4px rgba(0,0,0,.05);
                       display: flex; justify-content: space-between;
                       align-items: flex-start; gap: 12px; }
        .thread-card:hover { border-color: #bbb; }
        .thread-info { flex: 1; min-width: 0; }
        .thread-title { font-size: 1.1rem; margin-bottom: 5px; }
        .thread-title a { color: #222; text-decoration: none; }
        .thread-title a:hover { color: #4a90d9; }
        .thread-meta { font-size: 0.8rem; color: #888; }
        .thread-meta a { color: #4a90d9; text-decoration: none; }
        .thread-meta a:hover { text-decoration: underline; }
        .thread-stats { text-align: right; font-size: 0.8rem; color: #666;
                        white-space: nowrap; }
        .thread-stats span { display: block; }

        /* ── Empty state ── */
        .empty { text-align: center; padding: 48px 0; color: #888; font-size: 1rem; }

        @media (max-width: 500px) {
            .thread-card { flex-direction: column; }
            .thread-stats { text-align: left; }
        }
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
    <div class="page-header">
        <h1>All Threads</h1>
        <a class="btn" href="new_thread.php">+ New Thread</a>
    </div>

    <?php if (empty($threads)): ?>
        <div class="empty">
            No threads yet. <a href="new_thread.php">Start the first one!</a>
        </div>
    <?php else: ?>
    <div class="thread-list">
        <?php foreach ($threads as $t): ?>
        <div class="thread-card">
            <div class="thread-info">
                <div class="thread-title">
                    <a href="thread.php?id=<?= $t['id'] ?>">
                        <?= htmlspecialchars($t['title']) ?>
                    </a>
                </div>
                <div class="thread-meta">
                    by <a href="profile.php?user=<?= urlencode($t['username']) ?>">
                        <?= htmlspecialchars($t['username']) ?>
                    </a>
                    &nbsp;·&nbsp;
                    <?= date('M j, Y', strtotime($t['created_at'])) ?>
                </div>
            </div>
            <div class="thread-stats">
                <span>💬 <?= $t['reply_count'] ?> repl<?= $t['reply_count'] == 1 ? 'y' : 'ies' ?></span>
                <span>▲ <?= $t['vote_count'] ?> vote<?= $t['vote_count'] == 1 ? '' : 's' ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
