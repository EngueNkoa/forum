<?php
// upvote.php — Handles upvote toggle via AJAX
// Returns JSON: { success: true, action: "added"|"removed", new_count: int }
session_start();

header('Content-Type: application/json');

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require 'db.php';
$db = get_db();

$type    = $_POST['type'] ?? '';     // 'thread' or 'reply'
$id      = (int)($_POST['id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!in_array($type, ['thread', 'reply']) || $id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

// ── Build query parameters based on type ──────────────────────
if ($type === 'thread') {
    $thread_id = $id;
    $reply_id  = null;

    // Check item actually exists
    $stmt = $db->prepare('SELECT id FROM threads WHERE id = ?');
    $stmt->execute([$thread_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Thread not found']);
        exit;
    }
} else {
    $thread_id = null;
    $reply_id  = $id;

    // Check item actually exists
    $stmt = $db->prepare('SELECT id FROM replies WHERE id = ?');
    $stmt->execute([$reply_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Reply not found']);
        exit;
    }
}

// ── Check if user already voted ───────────────────────────────
if ($type === 'thread') {
    $stmt = $db->prepare('
        SELECT id FROM upvotes
        WHERE user_id = ? AND thread_id = ? AND reply_id IS NULL
    ');
    $stmt->execute([$user_id, $thread_id]);
} else {
    $stmt = $db->prepare('
        SELECT id FROM upvotes
        WHERE user_id = ? AND reply_id = ? AND thread_id IS NULL
    ');
    $stmt->execute([$user_id, $reply_id]);
}

$existing_vote = $stmt->fetch();

if ($existing_vote) {
    // ── Already voted → remove vote (toggle off) ────────────
    $stmt = $db->prepare('DELETE FROM upvotes WHERE id = ?');
    $stmt->execute([$existing_vote['id']]);
    $action = 'removed';
} else {
    // ── Not voted yet → add vote (toggle on) ────────────────
    $stmt = $db->prepare('
        INSERT INTO upvotes (user_id, thread_id, reply_id)
        VALUES (?, ?, ?)
    ');
    $stmt->execute([$user_id, $thread_id, $reply_id]);
    $action = 'added';
}

// ── Get updated vote count ────────────────────────────────────
if ($type === 'thread') {
    $stmt = $db->prepare('
        SELECT COUNT(*) AS cnt FROM upvotes
        WHERE thread_id = ? AND reply_id IS NULL
    ');
    $stmt->execute([$thread_id]);
} else {
    $stmt = $db->prepare('
        SELECT COUNT(*) AS cnt FROM upvotes
        WHERE reply_id = ? AND thread_id IS NULL
    ');
    $stmt->execute([$reply_id]);
}

$new_count = $stmt->fetch()['cnt'];

// ── Return JSON response ──────────────────────────────────────
echo json_encode([
    'success'   => true,
    'action'    => $action,
    'new_count' => (int)$new_count,
]);
