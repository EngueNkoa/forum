<?php
// thread.php — Thread detail: content, replies, reply form, upvotes
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';
$db = get_db();

// ── Validate thread ID ─────────────────────────────────────────
$thread_id = (int)($_GET['id'] ?? 0);
if ($thread_id <= 0) {
    header('Location: index.php');
    exit;
}

// ── Fetch the thread ───────────────────────────────────────────
$stmt = $db->prepare('
    SELECT t.id, t.title, t.content, t.created_at, u.username, t.user_id
    FROM threads t
    JOIN users u ON u.id = t.user_id
    WHERE t.id = ?
');
$stmt->execute([$thread_id]);
$thread = $stmt->fetch();

if (!$thread) {
    echo 'Thread not found.';
    exit;
}

// ── Handle reply submission ────────────────────────────────────
$reply_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_content'])) {
    $content = trim($_POST['reply_content']);
    if ($content === '') {
        $reply_error = 'Reply cannot be empty.';
    } else {
        $stmt = $db->prepare(
            'INSERT INTO replies (thread_id, user_id, content) VALUES (?, ?, ?)'
        );
        $stmt->execute([$thread_id, $_SESSION['user_id'], $content]);
        // Reload page to show the new reply (avoids resubmit on F5)
        header('Location: thread.php?id=' . $thread_id . '#replies');
        exit;
    }
}

// ── Fetch all replies ──────────────────────────────────────────
$stmt = $db->prepare('
    SELECT r.id, r.content, r.created_at, u.username,
           (SELECT COUNT(*) FROM upvotes v
            WHERE v.reply_id = r.id AND v.thread_id IS NULL) AS vote_count
    FROM replies r
    JOIN users u ON u.id = r.user_id
    WHERE r.thread_id = ?
    ORDER BY r.created_at ASC
');
$stmt->execute([$thread_id]);
$replies = $stmt->fetchAll();

// ── Thread upvote count ────────────────────────────────────────
$stmt = $db->prepare('
    SELECT COUNT(*) AS cnt FROM upvotes
    WHERE thread_id = ? AND reply_id IS NULL
');
$stmt->execute([$thread_id]);
$thread_votes = $stmt->fetch()['cnt'];

// ── Did the current user already upvote this thread? ──────────
$stmt = $db->prepare('
    SELECT id FROM upvotes
    WHERE user_id = ? AND thread_id = ? AND reply_id IS NULL
');
$stmt->execute([$_SESSION['user_id'], $thread_id]);
$user_voted_thread = $stmt->fetch() ? true : false;

// ── Which replies has this user already upvoted? ───────────────
$stmt = $db->prepare('
    SELECT reply_id FROM upvotes
    WHERE user_id = ? AND thread_id IS NULL AND reply_id IS NOT NULL
');
$stmt->execute([$_SESSION['user_id']]);
$voted_reply_ids = array_column($stmt->fetchAll(), 'reply_id');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($thread['title']) ?> — Forum</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Georgia, serif; background: #f4f4f4; color: #222; line-height: 1.6; }

        /* ── Nav ── */
        nav { background: #fff; border-bottom: 1px solid #ddd; padding: 12px 20px;
              display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
        nav .brand { font-size: 1.2rem; font-weight: bold; color: #222; text-decoration: none; }
        nav .links a { margin-left: 16px; color: #4a90d9; text-decoration: none; font-size: 0.9rem; }
        nav .links a:hover { text-decoration: underline; }

        .wrap { max-width: 740px; margin: 28px auto; padding: 0 16px; }

        /* ── Back link ── */
        .back { font-size: 0.85rem; margin-bottom: 16px; display: inline-block; }
        .back a { color: #4a90d9; text-decoration: none; }
        .back a:hover { text-decoration: underline; }

        /* ── Thread post ── */
        .post-card { background: #fff; border: 1px solid #ddd; border-radius: 8px;
                     padding: 24px; box-shadow: 0 1px 4px rgba(0,0,0,.05); margin-bottom: 24px; }
        .post-title { font-size: 1.5rem; margin-bottom: 8px; }
        .post-meta { font-size: 0.8rem; color: #888; margin-bottom: 16px; }
        .post-meta a { color: #4a90d9; text-decoration: none; }
        .post-meta a:hover { text-decoration: underline; }
        .post-body { font-size: 0.97rem; white-space: pre-wrap; word-break: break-word; }

        /* ── Upvote button ── */
        .upvote-btn {
            display: inline-flex; align-items: center; gap: 5px;
            margin-top: 16px; padding: 7px 14px;
            border: 1px solid #ccc; border-radius: 20px; background: #f9f9f9;
            cursor: pointer; font-size: 0.85rem; color: #444;
            font-family: Georgia, serif; transition: all .15s;
        }
        .upvote-btn:hover  { border-color: #4a90d9; color: #4a90d9; background: #eef3fb; }
        .upvote-btn.active { border-color: #4a90d9; color: #fff; background: #4a90d9; }
        .upvote-btn .count { font-weight: bold; }

        /* ── Replies section ── */
        h2 { font-size: 1.1rem; margin-bottom: 14px; color: #555; }
        .reply-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 28px; }
        .reply-card { background: #fff; border: 1px solid #ddd; border-radius: 8px;
                      padding: 16px 20px; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
        .reply-meta { font-size: 0.8rem; color: #888; margin-bottom: 8px; }
        .reply-meta a { color: #4a90d9; text-decoration: none; }
        .reply-meta a:hover { text-decoration: underline; }
        .reply-body { font-size: 0.95rem; white-space: pre-wrap; word-break: break-word; }

        /* ── Reply form ── */
        .reply-form-card { background: #fff; border: 1px solid #ddd; border-radius: 8px;
                           padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
        .reply-form-card h2 { margin-bottom: 12px; }
        .error { background: #fee; border: 1px solid #fcc; color: #900;
                 padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; font-size: 0.88rem; }
        textarea { width: 100%; padding: 10px 12px; border: 1px solid #ccc;
                   border-radius: 6px; font-size: 0.95rem; font-family: Georgia, serif;
                   height: 110px; resize: vertical; margin-bottom: 10px; }
        textarea:focus { outline: none; border-color: #4a90d9; }
        button[type=submit] { padding: 9px 20px; background: #4a90d9; color: #fff;
                              border: none; border-radius: 6px; font-size: 0.95rem;
                              cursor: pointer; font-family: Georgia, serif; }
        button[type=submit]:hover { background: #357abd; }
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
    <p class="back"><a href="index.php">← Back to all threads</a></p>

    <!-- ── Thread post ── -->
    <div class="post-card">
        <h1 class="post-title"><?= htmlspecialchars($thread['title']) ?></h1>
        <div class="post-meta">
            by <a href="profile.php?user=<?= urlencode($thread['username']) ?>">
                <?= htmlspecialchars($thread['username']) ?>
            </a>
            &nbsp;·&nbsp;
            <?= date('M j, Y \a\t g:i a', strtotime($thread['created_at'])) ?>
        </div>
        <div class="post-body"><?= htmlspecialchars($thread['content']) ?></div>

        <!-- Upvote button for the thread -->
        <button
            class="upvote-btn <?= $user_voted_thread ? 'active' : '' ?>"
            data-type="thread"
            data-id="<?= $thread['id'] ?>"
        >
            ▲ <span class="count"><?= $thread_votes ?></span>
            <?= $user_voted_thread ? 'Voted' : 'Upvote' ?>
        </button>
    </div>

    <!-- ── Replies ── -->
    <a name="replies"></a>
    <h2><?= count($replies) ?> Repl<?= count($replies) == 1 ? 'y' : 'ies' ?></h2>

    <?php if (!empty($replies)): ?>
    <div class="reply-list">
        <?php foreach ($replies as $reply): ?>
        <div class="reply-card">
            <div class="reply-meta">
                <a href="profile.php?user=<?= urlencode($reply['username']) ?>">
                    <?= htmlspecialchars($reply['username']) ?>
                </a>
                &nbsp;·&nbsp;
                <?= date('M j, Y \a\t g:i a', strtotime($reply['created_at'])) ?>
            </div>
            <div class="reply-body"><?= htmlspecialchars($reply['content']) ?></div>

            <!-- Upvote button for this reply -->
            <button
                class="upvote-btn <?= in_array($reply['id'], $voted_reply_ids) ? 'active' : '' ?>"
                data-type="reply"
                data-id="<?= $reply['id'] ?>"
            >
                ▲ <span class="count"><?= $reply['vote_count'] ?></span>
                <?= in_array($reply['id'], $voted_reply_ids) ? 'Voted' : 'Upvote' ?>
            </button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ── Reply form ── -->
    <div class="reply-form-card">
        <h2>Leave a Reply</h2>
        <?php if ($reply_error): ?>
            <div class="error"><?= htmlspecialchars($reply_error) ?></div>
        <?php endif; ?>
        <form method="POST" action="thread.php?id=<?= $thread_id ?>">
            <textarea name="reply_content" placeholder="Write your reply..."></textarea>
            <button type="submit">Post Reply</button>
        </form>
    </div>
</div>

<script>
// ── AJAX upvote — no page reload ────────────────────────────────
document.querySelectorAll('.upvote-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var type = btn.dataset.type;  // 'thread' or 'reply'
        var id   = btn.dataset.id;

        // Build form data to send to upvote.php
        var body = 'type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id);

        fetch('upvote.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                // Update the count shown on the button
                btn.querySelector('.count').textContent = data.new_count;

                if (data.action === 'added') {
                    btn.classList.add('active');
                    btn.lastChild.textContent = ' Voted';
                } else {
                    btn.classList.remove('active');
                    btn.lastChild.textContent = ' Upvote';
                }
            }
        })
        .catch(function() {
            // Network error — do nothing (button stays as-is)
            alert('Could not connect. Please try again.');
        });
    });
});
</script>

</body>
</html>
