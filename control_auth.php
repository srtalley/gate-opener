<?php
// Requests that pulse the relay or change the schedule must send the
// X-Control-Token header. This Pi's own page sends it, and so does the house
// control panel (http://arcreactor.home:8095). Other web sites can't: a browser
// won't add a custom header to a cross-site request without a CORS preflight,
// and nothing on this Pi answers one. That stops a link or image on some web
// page from opening the door. Cron runs scripts/ directly and isn't affected.
//
// The token lives in control_token.php next to this file (not in git; see
// control_token.example.php). If that file is missing, all control is refused.
// Written for PHP 5.6 as well, because luna still runs Jessie.

function control_token() {
	static $token = null;
	if ($token === null) {
		$file = __DIR__ . '/control_token.php';
		$token = is_readable($file) ? (string) include $file : '';
	}
	return $token;
}

function control_allowed() {
	$sent = isset($_SERVER['HTTP_X_CONTROL_TOKEN']) ? (string) $_SERVER['HTTP_X_CONTROL_TOKEN'] : '';
	$token = control_token();
	return $token !== '' && hash_equals($token, $sent);
}

function deny_control() {
	http_response_code(403);
	header('Content-Type: text/plain');
	echo "Missing or wrong control token.\n";
	exit;
}
