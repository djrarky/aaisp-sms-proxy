<?php
$token = $_GET['token'] ?? '';
if ($token !== getenv('RECEIVE_TOKEN')) {
    http_response_code(403);
    exit('Forbidden');
}

// AAISP posts: oa (sender), da (our number), ud (message), scts (timestamp)
$oa   = $_POST['oa']   ?? $_GET['oa']   ?? '';
$da   = $_POST['da']   ?? $_GET['da']   ?? '';
$ud   = $_POST['ud']   ?? $_GET['ud']   ?? '';
$scts = $_POST['scts'] ?? $_GET['scts'] ?? date('c');

if (!$oa || !$da || !$ud) {
    http_response_code(400);
    exit('Bad Request');
}

$db = new PDO('sqlite:/var/www/data/messages.db');
$db->exec('CREATE TABLE IF NOT EXISTS messages (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    da          TEXT,
    oa          TEXT,
    ud          TEXT,
    scts        TEXT,
    received_at TEXT
)');

$stmt = $db->prepare('INSERT INTO messages (da, oa, ud, scts, received_at) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$da, $oa, $ud, $scts, date('c')]);

http_response_code(200);
echo 'OK';
