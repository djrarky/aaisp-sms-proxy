<?php
if ($_GET["token"] !== getenv("SMS_TOKEN")) {
    http_response_code(403);
    exit("Forbidden");
}
$db = new PDO("sqlite:/var/www/data/messages.db");
$db->exec("CREATE TABLE IF NOT EXISTS messages (id INTEGER PRIMARY KEY AUTOINCREMENT, da TEXT, oa TEXT, ud TEXT, scts TEXT, received_at TEXT)");
$rows = $db->query("SELECT * FROM messages ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
header("Content-Type: application/json");
echo json_encode($rows, JSON_PRETTY_PRINT);
