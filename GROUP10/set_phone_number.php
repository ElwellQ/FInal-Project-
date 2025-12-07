<?php
session_start();
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/sms_config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Admin access required');
}

// Set phone number to 09948136097
$phone = '09948136097';
$api_key = 'c2cd365b1761722d7de88bc70fd9915d53b4f929';  // Your token
$sender_id = 'ALERT';
$enabled = 1;

$result = updateSMSSettings($phone, $api_key, $sender_id, $enabled);

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Phone number updated to: ' . $phone,
        'phone' => $phone,
        'api_key' => substr($api_key, 0, 10) . '...' . substr($api_key, -5)
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update phone number'
    ]);
}
