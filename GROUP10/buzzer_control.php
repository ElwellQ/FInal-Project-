<?php
/**
 * Buzzer Control Proxy
 * Routes buzzer commands from dashboard to ESP32
 */

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$state = $_GET['state'] ?? '';

if (!in_array($state, ['on', 'off'])) {
    echo json_encode(['error' => 'Invalid state']);
    exit;
}

// Call ESP32 buzzer endpoint
$url = "http://192.168.1.27/buzzer?state=" . $state;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200) {
    echo json_encode([
        'success' => true,
        'state' => $state,
        'message' => 'Buzzer ' . strtoupper($state)
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to control buzzer'
    ]);
}
?>
