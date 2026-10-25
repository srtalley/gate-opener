<?php

/**
 * Crontab API for Gate Opener
 * v1.1 - 2026.01.23
 * Uses /etc/cron.d/ for system-level cron
 * Writes via sudo tee to maintain root ownership
 *
 * GET  /crontab-api.php          - Returns current schedule as JSON
 * POST /crontab-api.php          - Updates the schedule
 *
 * Expected POST JSON:
 * {
 *   "enabled": true,
 *   "hour": 7,
 *   "minute": 30
 * }
 *
 * SETUP REQUIRED:
 * sudo visudo
 * Add: www-data ALL=(ALL) NOPASSWD: /usr/bin/tee /etc/cron.d/gate-opener
 *
 * Then create the file:
 * sudo touch /etc/cron.d/gate-opener
 * sudo chmod 644 /etc/cron.d/gate-opener
 */

header('Content-Type: application/json');
// No CORS headers: only this Pi's page and the house control panel (server
// side) call this, so other sites get no access.
require __DIR__ . '/control_auth.php';

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Configuration
$cronFile = '/etc/cron.d/gate-opener';
$scriptPath = __DIR__ . '/scripts/activate_gate.sh';
$runAsUser = 'root';

/**
 * Read current schedule from cron file
 */
function getSchedule($cronFile, $scriptPath) {
    $schedule = [
        'enabled' => false,
        'hour' => 7,
        'minute' => 30,
        'raw' => ''
    ];

    $meta = [
        'fileExists' => file_exists($cronFile),
        'fileReadable' => is_readable($cronFile)
    ];

    if (!$meta['fileExists'] || !$meta['fileReadable']) {
        return ['schedule' => $schedule, 'meta' => $meta];
    }

    $contents = file_get_contents($cronFile);
    if ($contents === false) {
        return ['schedule' => $schedule, 'meta' => $meta];
    }

    $lines = explode("\n", $contents);

    foreach ($lines as $line) {
        $line = trim($line);

        if (empty($line)) {
            continue;
        }

        // Check if this is our gate opener line (active or commented)
        if (strpos($line, $scriptPath) !== false || strpos($line, 'activate_gate') !== false) {
            $schedule['raw'] = $line;

            // Check if disabled (starts with #, but not a label comment)
            $isDisabled = (strpos($line, '#') === 0 && strpos($line, '# GATE') !== 0);
            $schedule['enabled'] = !$isDisabled;

            // Parse time from line
            $parseLine = $isDisabled ? ltrim($line, '# ') : $line;

            if (preg_match('/^(\d+)\s+(\d+)\s+/', $parseLine, $matches)) {
                $schedule['minute'] = (int)$matches[1];
                $schedule['hour'] = (int)$matches[2];
            }
            break;
        }
    }

    return ['schedule' => $schedule, 'meta' => $meta];
}

/**
 * Write schedule to cron file using sudo tee
 */
function updateSchedule($enabled, $hour, $minute, $cronFile, $scriptPath, $runAsUser) {
    // Build the cron line
    $cronLine = sprintf('%d %d * * * %s %s', $minute, $hour, $runAsUser, $scriptPath);

    if (!$enabled) {
        $cronLine = '# ' . $cronLine;
    }

    // Build file contents
    $contents = "# Gate Opener Schedule\n";
    $contents .= "# Managed by gate-opener web interface\n";
    $contents .= "# Do not edit manually - 2026.01.23\n";
    $contents .= "SHELL=/bin/bash\n";
    $contents .= "PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin\n\n";
    $contents .= "# GATE\n";
    $contents .= $cronLine . "\n";

    // Write via sudo tee
    $descriptors = [
        0 => ['pipe', 'r'],  // stdin
        1 => ['pipe', 'w'],  // stdout
        2 => ['pipe', 'w']   // stderr
    ];

    $process = proc_open("sudo /usr/bin/tee $cronFile", $descriptors, $pipes);

    if (is_resource($process)) {
        fwrite($pipes[0], $contents);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);

        return $returnCode === 0;
    }

    return false;
}

// Handle requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $data = getSchedule($cronFile, $scriptPath);

    // Add debug info
    $data['meta']['cronFile'] = $cronFile;
    $data['meta']['scriptPath'] = $scriptPath;
    $data['meta']['webUser'] = exec('whoami');

    echo json_encode([
        'success' => true,
        'schedule' => $data['schedule'],
        'meta' => $data['meta']
    ]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!control_allowed()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Missing or wrong control token.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['hour']) || !isset($input['minute'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing hour or minute']);
        exit;
    }

    // Check if file exists
    if (!file_exists($cronFile)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Cron file does not exist. Run setup commands first.',
            'setup' => [
                'sudo touch ' . $cronFile,
                'sudo chmod 644 ' . $cronFile,
                'sudo visudo  # Add: www-data ALL=(ALL) NOPASSWD: /usr/bin/tee ' . $cronFile
            ]
        ]);
        exit;
    }

    $enabled = isset($input['enabled']) ? (bool)$input['enabled'] : true;
    $hour = max(0, min(23, (int)$input['hour']));
    $minute = max(0, min(59, (int)$input['minute']));

    $success = updateSchedule($enabled, $hour, $minute, $cronFile, $scriptPath, $runAsUser);

    if ($success) {
        $data = getSchedule($cronFile, $scriptPath);
        echo json_encode([
            'success' => true,
            'schedule' => $data['schedule']
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to write cron file. Check sudo permissions.',
            'setup' => [
                'sudo visudo  # Add: www-data ALL=(ALL) NOPASSWD: /usr/bin/tee ' . $cronFile
            ]
        ]);
    }
}
