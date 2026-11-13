<?php
// v2026.11.13 - Today's expected deliveries, read from the house control panel
//
// People tick "delivery coming today" on the house panel
// (https://controlpanel.relay.dustysun.com). This page shows the same list and
// warns before the gate is activated. The browser can't ask the panel itself
// (another origin, and the panel needs a token), so this file asks it server
// side, sending this Pi's control token, which the panel accepts for this one
// read only. Read only: ticking happens on the panel.
//
// If the panel is down this answers 502 and the page just shows nothing, so
// the gate works exactly as before.

header('Content-Type: application/json');
header('Cache-Control: no-store');
require __DIR__ . '/control_auth.php';

$panel = 'http://10.0.1.84:8095'; // arcreactor, straight to the container

$context = stream_context_create(['http' => [
    'timeout' => 3,
    'header' => 'X-Control-Token: ' . control_token() . "\r\n",
    'ignore_errors' => true,
]]);
$body = @file_get_contents($panel . '/api.php?action=deliveries', false, $context);
$data = $body === false ? null : json_decode($body, true);

if (!is_array($data) || !isset($data['people'])) {
    http_response_code(502);
    echo json_encode(['error' => 'House panel not answering']);
    exit;
}

$expected = [];
foreach ($data['people'] as $person) {
    if (!empty($person['expected'])) {
        $expected[] = ['name' => (string) $person['name'], 'note' => (string) $person['note']];
    }
}
echo json_encode(['date' => $data['date'], 'expected' => $expected]);
