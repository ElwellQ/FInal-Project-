<?php
/**
 * SMS Alert Handler - iProGSMS V1 API
 * Endpoint: https://sms.iprogtech.com/api/v1/sms_messages
 */

require_once __DIR__ . '/sms_config.php';

// Start session to get logged-in user's phone
session_start();

// ---------------------------------------------------------
// Default fallback values (used only if database is empty)
// ---------------------------------------------------------
const FIXED_API_TOKEN = 'c2cd365b1761722d7de88bc70fd9915d53b4f929';

// Function to send SMS via iProGSMS V1 API
function sendSMS_iProGSMS($message, $api_token, $phone) {
    // CORRECT ENDPOINT
    $url = "https://sms.iprogtech.com/api/v1/sms_messages";
    
    // Prepare JSON Payload
    $payload = array(
        'api_token' => $api_token,
        'phone_number' => $phone,
        'message' => $message
    );
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    return array(
        'response' => $response,
        'http_code' => $http_code,
        'error' => $curl_error
    );
}

// Main handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Get Input
    $input = json_decode(file_get_contents('php://input'), true);
    $message = $input['message'] ?? 'Loud noise alert detected!';
    $noise_level = $input['noise_level'] ?? 0;
    
    // 2. Get SMS settings from database
    $sms_settings = getSMSSettings();
    
    // Use database settings, fallback to constant for API token only
    $api_token = !empty($sms_settings['api_key']) ? $sms_settings['api_key'] : FIXED_API_TOKEN;
    
    // Get phone number: prioritize logged-in user's phone, fallback to admin SMS settings
    $phone = '';
    if (isset($_SESSION['user_phone']) && !empty($_SESSION['user_phone'])) {
        $phone = $_SESSION['user_phone'];
    } else {
        $phone = !empty($sms_settings['phone_number']) ? $sms_settings['phone_number'] : '';
    }
    
    // Prepend +63 Philippines country code if not already present
    if (!empty($phone) && strpos($phone, '+63') !== 0 && strpos($phone, '63') !== 0) {
        $phone = '+63' . $phone;
    } elseif (!empty($phone) && strpos($phone, '63') === 0 && strpos($phone, '+63') !== 0) {
        $phone = '+' . $phone;
    }
    
    // 3. Validate phone number is configured
    if (empty($phone)) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Phone number not configured in SMS Settings',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    // 4. Log the attempt
    $log_file = __DIR__ . '/sms_alerts.log';
    $log_entry = date('Y-m-d H:i:s') . " - Noise: {$noise_level}dB - Sending to: {$phone} - Msg: {$message}\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    // 5. Send the SMS
    $result = sendSMS_iProGSMS($message, $api_token, $phone);
    
    // 6. Log the result for debugging
    $log_result = date('Y-m-d H:i:s') . " - HTTP Code: " . $result['http_code'] . " - Error: " . $result['error'] . " - Response: " . substr($result['response'], 0, 500) . "\n";
    file_put_contents($log_file, $log_result, FILE_APPEND);

    // 7. Return JSON response to the Dashboard
    header('Content-Type: application/json');
    echo json_encode([
        'status' => ($result['http_code'] >= 200 && $result['http_code'] < 300) ? 'sent' : 'error', 
        'provider' => 'iprogsms_v1', 
        'phone' => $phone,
        'http_code' => $result['http_code'],
        'message' => ($result['http_code'] >= 200 && $result['http_code'] < 300) ? 'SMS sent successfully' : 'Failed to send SMS',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    // Handle invalid request method
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>