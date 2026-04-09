<?php
// Allow CLI execution (cron) or authenticated HTTP requests
$is_cli = php_sapi_name() === 'cli';
if (!$is_cli && ($_GET['token'] ?? '') !== getenv('SMS_TOKEN')) {
    http_response_code(403);
    exit('Forbidden');
}

$db = new PDO('sqlite:/var/www/data/messages.db');
$db->exec('PRAGMA journal_mode=WAL');
$cutoff = date('c', strtotime('-14 days'));

$stmt = $db->prepare('DELETE FROM messages WHERE received_at < ?');
$stmt->execute([$cutoff]);
$pruned = $db->query('SELECT changes()')->fetchColumn();

// Also prune stale rate_limit entries (older than 1 day)
$db->exec('DELETE FROM rate_limit WHERE window_start < ' . (time() - 86400));
$pruned_rl = $db->query('SELECT changes()')->fetchColumn();

echo date('c') . " — pruned $pruned message(s) older than 14 days, $pruned_rl rate_limit row(s)\n";
