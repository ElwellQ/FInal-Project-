<?php
// save_noise.php
require_once __DIR__ . '/functions.php';

// Accept JSON or form
$input = $_POST;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) $input = array_merge($input, $json);
}

$zone = $input['zone'] ?? 'Unknown';
$db = isset($input['db']) ? floatval($input['db']) : null;

if ($db === null) {
    http_response_code(400);
    echo json_encode(['error' => 'db value required']);
    exit();
}

$rms = isset($input['rms']) ? floatval($input['rms']) : null;
$sent_by = $input['sent_by'] ?? null;
$meta = isset($input['meta']) ? json_encode($input['meta']) : null;

try {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO noise_logs (zone, db_value, rms, sent_by, meta) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$zone, $db, $rms, $sent_by, $meta]);
    $logId = $pdo->lastInsertId();

    $alertId = create_alert_if_needed($zone, $db);
    if ($alertId) {
        $u = $pdo->prepare("UPDATE noise_logs SET alert_id = ? WHERE id = ?");
        $u->execute([$alertId, $logId]);
        audit($sent_by ?? 'sensor', "alert_created", "alert_id=$alertId zone=$zone db=$db");
    } else {
        audit($sent_by ?? 'sensor', "log_saved", "log_id=$logId zone=$zone db=$db");
    }

    echo json_encode(['ok' => true, 'log_id' => $logId, 'alert_id' => $alertId]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
