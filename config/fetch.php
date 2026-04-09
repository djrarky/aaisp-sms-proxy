<?php
$token   = $_GET['token']            ?? '';
$account = $_GET['account']          ?? '';
$last_id = (int)($_GET['last_known_sms_id'] ?? 0);

header('Content-Type: application/json');

if ($token !== getenv('SMS_TOKEN')) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Strip non-digits then re-add + to match how AAISP stores da
$da_match = '+' . preg_replace('/[^0-9]/', '', $account);

$db = new PDO('sqlite:/var/www/data/messages.db');
$db->exec('CREATE TABLE IF NOT EXISTS messages (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    da          TEXT,
    oa          TEXT,
    ud          TEXT,
    scts        TEXT,
    received_at TEXT
)');

$stmt = $db->prepare('SELECT * FROM messages WHERE id > ? AND da = ? ORDER BY id ASC');
$stmt->execute([$last_id, $da_match]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$received = [];
$ids      = [];
foreach ($rows as $row) {
    $received[] = [
        'sms_id'       => (string)$row['id'],
        'sending_date' => date('c', strtotime($row['scts'] ?: $row['received_at'])),
        'sender'       => $row['oa'],
        'sms_text'     => $row['ud'],
    ];
    $ids[] = $row['id'];
}

echo json_encode([
    'date'          => date('c'),
    'received_smss' => $received,
    'sent_smss'     => [],
]);

// Delete delivered messages
if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $db->prepare("DELETE FROM messages WHERE id IN ($placeholders)")->execute($ids);
}
