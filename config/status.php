<?php
if (($_GET['token'] ?? '') !== getenv('SMS_TOKEN')) {
    http_response_code(403);
    exit('Forbidden');
}

$db = new PDO('sqlite:/var/www/data/messages.db');
$db->exec('PRAGMA journal_mode=WAL');
$db->exec('CREATE TABLE IF NOT EXISTS messages (id INTEGER PRIMARY KEY AUTOINCREMENT, da TEXT, oa TEXT, ud TEXT, scts TEXT, received_at TEXT)');
$db->exec('CREATE TABLE IF NOT EXISTS rate_limit (ip TEXT PRIMARY KEY, count INTEGER, window_start INTEGER)');

// Use X-Forwarded-For (set by Caddy) so rate limiting is per real client, not per proxy IP
$forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
$ip        = $forwarded ? trim(explode(',', $forwarded)[0]) : $_SERVER['REMOTE_ADDR'];

// Rate limit: max 10 requests per IP per minute
$now  = time();
$stmt = $db->prepare('SELECT count, window_start FROM rate_limit WHERE ip = ?');
$stmt->execute([$ip]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || ($now - $row['window_start']) > 60) {
    $db->prepare('INSERT OR REPLACE INTO rate_limit (ip, count, window_start) VALUES (?, 1, ?)')->execute([$ip, $now]);
} elseif ($row['count'] >= 10) {
    http_response_code(429);
    exit('Too Many Requests');
} else {
    $db->prepare('UPDATE rate_limit SET count = count + 1 WHERE ip = ?')->execute([$ip]);
}

$rows = $db->query('SELECT * FROM messages ORDER BY id DESC LIMIT 20')->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($rows, JSON_PRETTY_PRINT);
