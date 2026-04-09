<?php
$token   = $_GET["token"] ?? "";
$account = $_GET["account"] ?? "";
$da      = $_GET["da"] ?? "";
$ud      = $_GET["ud"] ?? "";

header("Content-Type: text/xml");

if ($token !== getenv("SMS_TOKEN")) {
    echo "<response><error>1</error><description>Unauthorized</description></response>";
    exit;
}

$key      = preg_replace("/[^a-zA-Z0-9_]/", "", $account);
$username = getenv("AAISP_{$key}_USERNAME");
$password = getenv("AAISP_{$key}_PASSWORD");

if (!$username || !$password) {
    echo "<response><error>1</error><description>Unknown account</description></response>";
    exit;
}

if (empty($da) || empty($ud)) {
    echo "<response><error>1</error><description>Missing parameters</description></response>";
    exit;
}

$params = http_build_query([
    "username" => $username,
    "password" => $password,
    "da"       => $da,
    "ud"       => $ud,
]);

$response = @file_get_contents("https://sms.aa.net.uk/?" . $params);

if ($response !== false && str_starts_with(trim($response), "OK")) {
    echo "<response><error>0</error><description>Success</description></response>";
} else {
    echo "<response><error>1</error><description>" . htmlspecialchars($response ?: "Request failed") . "</description></response>";
}
