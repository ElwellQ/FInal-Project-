<?php
/**
 * iProGSMS Endpoint Discovery
 * Systematically tests all possible iProGSMS API endpoints
 */

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Admin access required');
}

// Your credentials
$API_TOKEN = 'c2cd365b1761722d7de88bc70fd9915d53b4f929';
$PHONE = '09976017360';

?>
<!DOCTYPE html>
<html>
<head>
    <title>iProGSMS Endpoint Discovery</title>
    <style>
        body { font-family: Arial; background: #f5f7fa; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #333; text-align: center; }
        .endpoint-group { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .endpoint { padding: 15px; margin: 10px 0; border-left: 4px solid #999; border-radius: 4px; background: #f9f9f9; }
        .endpoint.success { background: #d4edda; border-left-color: #28a745; }
        .endpoint.error { background: #f8d7da; border-left-color: #dc3545; }
        .endpoint.unknown { background: #fff3cd; border-left-color: #ffc107; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; word-break: break-all; }
        .status { font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; max-height: 150px; margin-top: 10px; border-radius: 4px; }
        h2 { color: #555; font-size: 18px; margin-bottom: 15px; }
        .legend { background: #f0f0f0; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .found { background: #d4edda; padding: 20px; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔍 iProGSMS Endpoint Discovery</h1>
    
    <div class="legend">
        <strong>Legend:</strong>
        <ul>
            <li><span style="color: #28a745;">✓ SUCCESS (200-299)</span> - Endpoint works!</li>
            <li><span style="color: #ffc107;">⚠ UNKNOWN (400-499)</span> - Endpoint exists but request failed (auth/params issue)</li>
            <li><span style="color: #dc3545;">✗ NOT FOUND (404)</span> - Endpoint doesn't exist</li>
        </ul>
    </div>

    <?php

    // Comprehensive list of possible iProGSMS endpoints
    $endpoints = array(
        // Standard API patterns
        array('https://www.iprogsms.com/api/send', 'Standard /api/send'),
        array('https://www.iprogsms.com/api/sms/send', 'Standard /api/sms/send'),
        array('https://www.iprogsms.com/sms/send', 'Root /sms/send'),
        array('https://www.iprogsms.com/send', 'Root /send'),
        
        // Versioned endpoints
        array('https://www.iprogsms.com/api/v1/send', 'Versioned /api/v1/send'),
        array('https://www.iprogsms.com/api/v2/send', 'Versioned /api/v2/send'),
        array('https://www.iprogsms.com/api/v1/message', 'Versioned /api/v1/message'),
        array('https://www.iprogsms.com/api/v1/message/send', 'Versioned /api/v1/message/send'),
        
        // With different domain variations
        array('https://api.iprogsms.com/send', 'Subdomain api.iprogsms.com/send'),
        array('https://api.iprogsms.com/v1/send', 'Subdomain api.iprogsms.com/v1/send'),
        array('https://sms.iprogsms.com/send', 'Subdomain sms.iprogsms.com/send'),
        
        // Without www
        array('https://iprogsms.com/api/send', 'No www /api/send'),
        array('https://iprogsms.com/send', 'No www /send'),
        
        // HTTP variants
        array('http://www.iprogsms.com/api/send', 'HTTP /api/send'),
        array('http://iprogsms.com/api/send', 'HTTP no www'),
        
        // SMS routing endpoints
        array('https://www.iprogsms.com/api/sms', 'SMS route /api/sms'),
        array('https://www.iprogsms.com/api/message', 'Message route /api/message'),
        array('https://www.iprogsms.com/api/broadcast', 'Broadcast /api/broadcast'),
        
        // Queue/batch endpoints
        array('https://www.iprogsms.com/api/queue', 'Queue endpoint'),
        array('https://www.iprogsms.com/api/batch', 'Batch endpoint'),
    );

    $working_endpoints = array();
    $auth_failed_endpoints = array();
    $not_found_endpoints = array();

    echo '<div class="endpoint-group">';
    echo '<h2>Testing ' . count($endpoints) . ' possible endpoints...</h2>';

    foreach ($endpoints as $endpoint_info) {
        $url = $endpoint_info[0];
        $label = $endpoint_info[1];
        
        // Prepare test payload
        $payload = array(
            'api_token' => $API_TOKEN,
            'token' => $API_TOKEN,
            'apikey' => $API_TOKEN,
            'key' => $API_TOKEN,
            'to' => $PHONE,
            'number' => $PHONE,
            'phone' => $PHONE,
            'recipient' => $PHONE,
            'message' => 'TEST',
            'text' => 'TEST',
            'msg' => 'TEST',
            'sms' => 'TEST'
        );
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // Classify result
        if ($http_code >= 200 && $http_code < 300) {
            $class = 'success';
            $working_endpoints[] = array('url' => $url, 'label' => $label);
        } elseif ($http_code >= 400 && $http_code < 500 && $http_code != 404) {
            $class = 'unknown';
            $auth_failed_endpoints[] = array('url' => $url, 'label' => $label, 'code' => $http_code);
        } else {
            $class = 'error';
            $not_found_endpoints[] = array('url' => $url, 'label' => $label, 'code' => $http_code);
        }
        
        echo '<div class="endpoint ' . $class . '">';
        echo '<strong>' . $label . '</strong><br>';
        echo '<code>' . $url . '</code><br>';
        echo 'HTTP Code: <span class="status">' . $http_code . '</span>';
        
        if ($response && strlen($response) < 200) {
            echo '<pre>' . htmlspecialchars($response) . '</pre>';
        }
        
        if ($curl_error) {
            echo '<p style="color: #666; font-size: 12px;">Error: ' . $curl_error . '</p>';
        }
        
        echo '</div>';
    }

    echo '</div>';

    // Summary
    echo '<div class="endpoint-group">';
    echo '<h2>Results Summary</h2>';
    
    if (count($working_endpoints) > 0) {
        echo '<div class="found">';
        echo '<h3 style="color: #28a745;">✓ Working Endpoints Found!</h3>';
        foreach ($working_endpoints as $ep) {
            echo '<p><code>' . $ep['url'] . '</code><br><small>' . $ep['label'] . '</small></p>';
        }
        echo '<p style="margin-top: 15px; font-weight: bold;">Update your SMS handler to use this endpoint.</p>';
        echo '</div>';
    } elseif (count($auth_failed_endpoints) > 0) {
        echo '<div class="found" style="background: #fff3cd; border-left: 4px solid #ffc107;">';
        echo '<h3 style="color: #ffc107;">⚠ Endpoints That Responded to Request</h3>';
        echo '<p>These endpoints exist but returned authentication/parameter errors (which might be good!):</p>';
        foreach ($auth_failed_endpoints as $ep) {
            echo '<p><code>' . $ep['url'] . '</code> - HTTP ' . $ep['code'] . '<br><small>' . $ep['label'] . '</small></p>';
        }
        echo '<p style="margin-top: 15px;">These endpoints likely exist. The errors are probably due to invalid test parameters, not the endpoint being missing.</p>';
        echo '</div>';
    } else {
        echo '<div class="found" style="background: #f8d7da; border-left: 4px solid #dc3545;">';
        echo '<h3 style="color: #dc3545;">✗ No Working Endpoints Found</h3>';
        echo '<p>All tested endpoints returned 404 or connection errors. This suggests:</p>';
        echo '<ol>';
        echo '<li><strong>iProGSMS API structure may have completely changed</strong></li>';
        echo '<li><strong>The service may be down or restructured</strong></li>';
        echo '<li><strong>Your API token may not be valid for the current API version</strong></li>';
        echo '</ol>';
        echo '<p style="margin-top: 15px; font-weight: bold;">Recommendation: Contact iProGSMS support or check their latest API documentation.</p>';
        echo '</div>';
    }

    echo '</div>';

    ?>

</div>

</body>
</html>
