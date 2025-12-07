<?php
/**
 * SMS Alert Handler (iProGSMS)
 * Sends SMS to the phone number specified in the admin settings
 */

require_once __DIR__ . '/sms_config.php';

// Function to send SMS via iProGSMS API
function sendSMS_iProGSMS($message, $api_token, $phone) {
    $url = "https://www.iprogsms.com/api/v1/sms_messages"; // Keep as-is for now; needs working endpoint

    $payload = [
        'api_token'    => $api_token,
        'phone_number' => $phone,
        'message'      => $message
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    return [
        'response'  => $response,
        'http_code' => $http_code,
        'error'     => $curl_error
    ];
}

// Main handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = $input['message'] ?? 'Loud noise alert detected!';
    $noise_level = $input['noise_level'] ?? 0;

    // Get settings from DB
    $sms_settings = getSMSSettings();
    $api_token = $sms_settings['api_key'] ?? '';
    $phone     = $sms_settings['phone_number'] ?? '';

    // Prepend +63 Philippines country code if not already present
    if (!empty($phone) && strpos($phone, '+63') !== 0 && strpos($phone, '63') !== 0) {
        $phone = '+63' . $phone;
    } elseif (!empty($phone) && strpos($phone, '63') === 0 && strpos($phone, '+63') !== 0) {
        $phone = '+' . $phone;
    }

    // Validate required fields
    if (empty($api_token) || empty($phone)) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'API token or recipient phone number not set in admin settings.'
        ]);
        exit;
    }

    // Log attempt
    $log_file = __DIR__ . '/sms_alerts.log';
    $log_entry = date('Y-m-d H:i:s') . " - Noise: {$noise_level}dB - Sending to: {$phone} - Msg: {$message}\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);

    // Send SMS
    $result = sendSMS_iProGSMS($message, $api_token, $phone);

    // Log result
    $log_result = date('Y-m-d H:i:s') . " - API Response Code: {$result['http_code']} - Body: {$result['response']}\n";
    file_put_contents($log_file, $log_result, FILE_APPEND);

    // Return JSON
    header('Content-Type: application/json');
    echo json_encode([
        'status'    => ($result['http_code'] >= 200 && $result['http_code'] < 300) ? 'sent' : 'error',
        'provider'  => 'iprogsms',
        'phone'     => $phone,
        'result'    => $result,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
