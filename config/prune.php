<?php
// Allow CLI execution (cron) or authenticated HTTP requests
$is_cli = php_sapi_name() === 'cli';
if (!$is_cli && ($_GET['token'] ?? '') !== getenv('SMS_TOKEN')) {
    http_response_code(403);
    exit('Forbidden');
}

$db     = new PDO('sqlite:/var/www/data/messages.db');
$cutoff = date('c', strtotime('-14 days'));

$stmt = $db->prepare('DELETE FROM messages WHERE received_at < ?');
$stmt->execute([$cutoff]);
$pruned = $db->query('SELECT changes()')->fetchColumn();

echo date('c') . " — pruned $pruned message(s) older than 14 days\n";
