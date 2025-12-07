<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['status' => 'error', 'message' => 'Admin access required']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log_file = __DIR__ . '/sms_alerts.log';
    if (file_exists($log_file)) {
        unlink($log_file);
    }
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Logs cleared']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'POST method required']);
}
?>
